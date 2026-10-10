import Foundation
import Network
import Security

/// What the player fetches through: a listener on loopback that forwards each request to the door, over the pin and with the grant.
final class DoorRelay: NSObject, URLSessionDataDelegate {
    private static let playlistTypes: Set<String> = [
        "application/vnd.apple.mpegurl", "application/x-mpegurl", "audio/mpegurl", "audio/x-mpegurl",
    ]

    private static let playlistType = "application/vnd.apple.mpegurl"

    private static let headAtMost = 16_384

    private static let readyWithin: DispatchTimeInterval = .seconds(2)

    private static let tokenBytes = 16

    private let asked: WhatToPlay

    private let trust: DoorTrust

    private let top: String?

    private let extras: ExtraSubtitles

    private let queue = DispatchQueue(label: "app.lemonfiber.player.relay")

    private var listener: NWListener?

    private var rule: RelayRule?

    private var waiting: [Int: Waiting] = [:]

    private var whyItStopped: WhyPlaybackStopped?

    private var lastFetchFailed = false

    private lazy var session: URLSession = {
        let configuration = URLSessionConfiguration.ephemeral
        configuration.urlCache = nil
        configuration.httpCookieStorage = nil
        configuration.urlCredentialStorage = nil
        configuration.requestCachePolicy = .reloadIgnoringLocalCacheData

        let delegates = OperationQueue()
        delegates.underlyingQueue = queue
        delegates.maxConcurrentOperationCount = 1

        return URLSession(configuration: configuration, delegate: self, delegateQueue: delegates)
    }()

    var why: WhyPlaybackStopped? {
        queue.sync { whyItStopped }
    }

    /// Whether the last fetch through the relay failed, which a fetch that succeeds since puts right.
    var isFailing: Bool {
        queue.sync { lastFetchFailed }
    }

    private final class Waiting {
        let connection: NWConnection
        let address: String
        let target: String
        let askedForPart: Bool
        let onlyTheHead: Bool
        var isPlaylist = false
        var playlist = Data()

        init(connection: NWConnection, address: String, target: String, askedForPart: Bool, onlyTheHead: Bool)
        {
            self.connection = connection
            self.address = address
            self.target = target
            self.askedForPart = askedForPart
            self.onlyTheHead = onlyTheHead
        }
    }

    init(asked: WhatToPlay, top: String?, extras: ExtraSubtitles) {
        self.asked = asked
        self.trust = DoorTrust(door: asked.door, pin: asked.pin)
        self.top = top
        self.extras = extras
    }

    /// Start listening, and say where the player is handed `address`, or nil where the relay could not open.
    func open(_ address: String) -> URL? {
        guard let token = Self.token() else {
            return nil
        }

        let parameters = NWParameters.tcp
        parameters.acceptLocalOnly = true
        parameters.requiredLocalEndpoint = NWEndpoint.hostPort(
            host: NWEndpoint.Host(RelayRule.host), port: .any)

        guard let listening = try? NWListener(using: parameters) else {
            return nil
        }

        let ready = DispatchSemaphore(value: 0)

        listening.stateUpdateHandler = { state in
            switch state {
            case .ready, .failed, .cancelled:
                ready.signal()
            default:
                return
            }
        }
        listening.newConnectionHandler = { [weak self] connection in
            self?.accept(connection)
        }
        listening.start(queue: queue)

        guard ready.wait(timeout: .now() + Self.readyWithin) == .success, let port = listening.port?.rawValue
        else {
            listening.cancel()

            return nil
        }

        let opened = RelayRule(door: asked.door, token: token, port: port)

        queue.sync {
            listener = listening
            rule = opened
        }

        return opened.handed(address).flatMap(URL.init(string:))
    }

    func close() {
        queue.sync {
            listener?.cancel()
            listener = nil
            rule = nil

            for one in waiting.values {
                one.connection.cancel()
            }

            waiting.removeAll()
        }

        session.invalidateAndCancel()
    }

    private static func token() -> String? {
        var bytes = [UInt8](repeating: 0, count: tokenBytes)

        guard SecRandomCopyBytes(kSecRandomDefault, bytes.count, &bytes) == errSecSuccess else {
            return nil
        }

        return bytes.map { String(format: "%02x", $0) }.joined()
    }

    // MARK: - Answering the player

    private func accept(_ connection: NWConnection) {
        connection.start(queue: queue)
        readHead(of: connection, after: Data())
    }

    private func readHead(of connection: NWConnection, after buffered: Data) {
        connection.receive(minimumIncompleteLength: 1, maximumLength: Self.headAtMost) {
            [weak self] data, _, isComplete, error in
            guard let self else {
                connection.cancel()

                return
            }

            let read = buffered + (data ?? Data())

            if let end = read.range(of: Data("\r\n\r\n".utf8)) {
                self.answer(connection, String(decoding: read[..<end.lowerBound], as: UTF8.self))
            } else if isComplete || error != nil || read.count > Self.headAtMost {
                connection.cancel()
            } else {
                self.readHead(of: connection, after: read)
            }
        }
    }

    private func answer(_ connection: NWConnection, _ head: String) {
        guard let rule, let request = RelayRule.read(head) else {
            finish(connection, status: 400)

            return
        }

        if let index = rule.extra(request.target) {
            answerExtra(connection, index: index, on: rule, onlyTheHead: request.method == "HEAD")

            return
        }

        guard let url = rule.asked(request.target) else {
            finish(connection, status: 404)

            return
        }

        var forwarded = URLRequest(url: url)
        forwarded.httpMethod = request.method
        forwarded.setValue(asked.grantHeaderValue, forHTTPHeaderField: WhatToPlay.grantHeader)

        if let range = request.range {
            forwarded.setValue(range, forHTTPHeaderField: "Range")
        }

        let task = session.dataTask(with: forwarded)
        waiting[task.taskIdentifier] = Waiting(
            connection: connection, address: url.absoluteString, target: request.target,
            askedForPart: request.range != nil, onlyTheHead: request.method == "HEAD")
        task.resume()
    }

    private func answerExtra(_ connection: NWConnection, index: Int, on rule: RelayRule, onlyTheHead: Bool) {
        guard let track = extras.track(at: ExtraSubtitles.address(of: index)),
            let file = rule.handed(track.address)
        else {
            finish(connection, status: 404)

            return
        }

        let playlist = Data(ExtraSubtitles.playlist(playing: file).utf8)

        send(
            connection, status: 200, type: Self.playlistType, body: onlyTheHead ? nil : playlist,
            length: playlist.count)
    }

    // MARK: - Asking the door

    func urlSession(
        _ session: URLSession, didReceive challenge: URLAuthenticationChallenge,
        completionHandler: @escaping (URLSession.AuthChallengeDisposition, URLCredential?) -> Void
    ) {
        let space = challenge.protectionSpace

        guard space.authenticationMethod == NSURLAuthenticationMethodServerTrust,
            let presented = space.serverTrust
        else {
            completionHandler(.cancelAuthenticationChallenge, nil)

            return
        }

        let leaf = (SecTrustCopyCertificateChain(presented) as? [SecCertificate])?.first.map {
            SecCertificateCopyData($0) as Data
        }

        guard trust.admits(leaf: leaf, host: space.host, port: space.port) else {
            whyItStopped = .pinMismatch
            lastFetchFailed = true
            completionHandler(.cancelAuthenticationChallenge, nil)

            return
        }

        completionHandler(.useCredential, URLCredential(trust: presented))
    }

    func urlSession(
        _ session: URLSession, task: URLSessionTask, willPerformHTTPRedirection response: HTTPURLResponse,
        newRequest request: URLRequest, completionHandler: @escaping (URLRequest?) -> Void
    ) {
        guard let next = request.url, let admitted = asked.door.admitted(next.absoluteString),
            admitted.host == next.host, admitted.port == next.port, next.user == nil, next.password == nil
        else {
            whyItStopped = .refused
            lastFetchFailed = true
            completionHandler(nil)

            return
        }

        var followed = request
        followed.url = admitted
        followed.setValue(asked.grantHeaderValue, forHTTPHeaderField: WhatToPlay.grantHeader)
        completionHandler(followed)
    }

    func urlSession(
        _ session: URLSession, dataTask: URLSessionDataTask, didReceive response: URLResponse,
        completionHandler: @escaping (URLSession.ResponseDisposition) -> Void
    ) {
        guard let one = waiting[dataTask.taskIdentifier], let answered = response as? HTTPURLResponse else {
            completionHandler(.cancel)

            return
        }

        guard RangeRule.admits(status: answered.statusCode, askedForPart: one.askedForPart) else {
            failed(PlaybackRule.why(status: answered.statusCode))
            waiting[dataTask.taskIdentifier] = nil
            finish(one.connection, status: 502)
            completionHandler(.cancel)

            return
        }

        lastFetchFailed = false

        let type = answered.mimeType?.lowercased() ?? ""
        one.isPlaylist =
            Self.playlistTypes.contains(type) || PlayerExtensions.kind(of: one.address) == .hls

        if !one.isPlaylist {
            var headers = [("Content-Type", type), ("Accept-Ranges", "bytes")]

            if answered.expectedContentLength >= 0 {
                headers.append(("Content-Length", String(answered.expectedContentLength)))
            }

            if let range = answered.value(forHTTPHeaderField: "Content-Range") {
                headers.append(("Content-Range", range))
            }

            one.connection.send(
                content: Data(RelayRule.head(status: answered.statusCode, headers: headers).utf8),
                completion: .contentProcessed { _ in })
        }

        completionHandler(one.onlyTheHead && !one.isPlaylist ? .cancel : .allow)
    }

    func urlSession(_ session: URLSession, dataTask: URLSessionDataTask, didReceive data: Data) {
        guard let one = waiting[dataTask.taskIdentifier] else {
            return
        }

        if one.isPlaylist {
            one.playlist.append(data)
        } else {
            one.connection.send(content: data, completion: .contentProcessed { _ in })
        }
    }

    func urlSession(_ session: URLSession, task: URLSessionTask, didCompleteWithError error: Error?) {
        guard let one = waiting.removeValue(forKey: task.taskIdentifier) else {
            return
        }

        if error != nil && !(one.onlyTheHead && !one.isPlaylist) {
            failed(.unreachable)
            one.connection.cancel()

            return
        }

        guard one.isPlaylist else {
            close(one.connection)

            return
        }

        guard let rule, let read = String(data: one.playlist, encoding: .utf8),
            let rewritten = PlaylistRule(door: asked.door, handing: rule.handed).rewrite(
                read, at: one.address)
        else {
            failed(.refused)
            finish(one.connection, status: 502)

            return
        }

        let joined =
            one.target == top.flatMap(rule.target(of:))
            ? rule.handingExtras(in: extras.join(rewritten), count: extras.tracks.count) : rewritten
        let body = Data(joined.utf8)

        send(
            one.connection, status: 200, type: Self.playlistType, body: one.onlyTheHead ? nil : body,
            length: body.count)
    }

    /// Record a fetch that failed, keeping the first reason playback met.
    private func failed(_ why: WhyPlaybackStopped) {
        whyItStopped = whyItStopped ?? why
        lastFetchFailed = true
    }

    // MARK: - Writing back

    private func send(_ connection: NWConnection, status: Int, type: String, body: Data?, length: Int) {
        let head = RelayRule.head(
            status: status, headers: [("Content-Type", type), ("Content-Length", String(length))])

        connection.send(content: Data(head.utf8) + (body ?? Data()), completion: .contentProcessed { _ in })
        close(connection)
    }

    private func finish(_ connection: NWConnection, status: Int) {
        send(connection, status: status, type: "text/plain", body: nil, length: 0)
    }

    private func close(_ connection: NWConnection) {
        connection.send(
            content: nil, isComplete: true, completion: .contentProcessed { _ in connection.cancel() })
    }
}
