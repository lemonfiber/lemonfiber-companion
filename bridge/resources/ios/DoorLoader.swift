import AVFoundation
import Foundation
import UniformTypeIdentifiers

/// Every byte the player plays, fetched from the door over a pinned connection.
///
/// AVPlayer is never handed an `https` address. Every address it reads is
/// under the private `lfdoor` scheme, which it cannot fetch by itself, so it
/// asks this loader for each one — the playlists, the segments, the keys, the
/// initialisation sections, the subtitles and, for direct play, every range of
/// the file. This fetches the real address with a session whose challenge
/// handler admits only the certificate the core stated (`DoorTrust`), and a
/// playlist is rewritten (`PlaylistRule`) before AVPlayer reads it, so the
/// addresses inside it come back here too. There is no listening socket and no
/// exception in the platform's transport security: the player cannot reach
/// anything this loader did not fetch.
///
/// **The grant goes in a header and nowhere else**, and the session keeps
/// nothing: no cache, no cookies, no credential store. A redirect is followed
/// only to the same door.
///
/// What went wrong is kept for the screen to say: the pin refused, an address
/// off the door, or the door's status.
final class DoorLoader: NSObject, AVAssetResourceLoaderDelegate, URLSessionDataDelegate {
    /// The private scheme every address is handed to AVPlayer under.
    static let scheme = "lfdoor"

    /// The header the grant is carried in.
    private static let grantHeader = "Authorization"

    /// How the grant is written in it.
    private static let grantPrefix = "Bearer "

    /// The playlist type, read off the address or the answer's type.
    private static let playlistTypes: Set<String> = [
        "application/vnd.apple.mpegurl", "application/x-mpegurl", "audio/mpegurl", "audio/x-mpegurl",
    ]

    /// What the member asked to play.
    private let asked: WhatToPlay

    /// Whether a connection reached the door.
    private let trust: DoorTrust

    /// How a playlist is rewritten before AVPlayer reads it.
    private let playlists: PlaylistRule

    /// The queue every callback here runs on, and the one the loader is handed.
    let queue = DispatchQueue(label: "app.lemonfiber.player.door")

    /// The session every fetch goes through.
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

    /// Each loading request AVPlayer is waiting on, by the task fetching it.
    private var waiting: [Int: Waiting] = [:]

    /// What went wrong first, for the screen to say. Read and written on `queue`.
    private var whyItStopped: WhyPlaybackStopped?

    /// What went wrong first, read safely from any thread.
    var why: WhyPlaybackStopped? {
        queue.sync { whyItStopped }
    }

    /// One loading request, and what has come back for it so far.
    private final class Waiting {
        let request: AVAssetResourceLoadingRequest
        let address: String
        let askedForPart: Bool
        var isPlaylist = false
        var playlist = Data()

        init(request: AVAssetResourceLoadingRequest, address: String, askedForPart: Bool) {
            self.request = request
            self.address = address
            self.askedForPart = askedForPart
        }
    }

    init(asked: WhatToPlay) {
        self.asked = asked
        self.trust = DoorTrust(door: asked.door, pin: asked.pin)
        self.playlists = PlaylistRule(door: asked.door, scheme: Self.scheme)
    }

    /// An address at the door, as AVPlayer is handed it.
    static func handed(_ address: String) -> URL? {
        URL(string: scheme + address.dropFirst(Door.scheme.count))
    }

    /// Stop every fetch and forget every request.
    func close() {
        session.invalidateAndCancel()
        waiting.removeAll()
    }

    // MARK: - What AVPlayer asks for

    func resourceLoader(
        _ resourceLoader: AVAssetResourceLoader,
        shouldWaitForLoadingOfRequestedResource loadingRequest: AVAssetResourceLoadingRequest
    ) -> Bool {
        guard let handed = loadingRequest.request.url?.absoluteString,
            handed.hasPrefix(Self.scheme + ":")
        else {
            return false
        }

        let address = Door.scheme + handed.dropFirst(Self.scheme.count)

        guard asked.door.holds(address), let url = URL(string: address) else {
            refuse(loadingRequest, because: .refused)

            return true
        }

        var request = URLRequest(url: url)
        request.setValue(Self.grantPrefix + asked.grant, forHTTPHeaderField: Self.grantHeader)

        let range = Self.range(of: loadingRequest)

        if let range {
            request.setValue(range, forHTTPHeaderField: "Range")
        }

        let task = session.dataTask(with: request)
        waiting[task.taskIdentifier] = Waiting(
            request: loadingRequest, address: address, askedForPart: range != nil)
        task.resume()

        return true
    }

    func resourceLoader(
        _ resourceLoader: AVAssetResourceLoader, didCancel loadingRequest: AVAssetResourceLoadingRequest
    ) {
        for (identifier, one) in waiting where one.request === loadingRequest {
            waiting[identifier] = nil
        }
    }

    // MARK: - What the door answers

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
            completionHandler(.cancelAuthenticationChallenge, nil)

            return
        }

        completionHandler(.useCredential, URLCredential(trust: presented))
    }

    func urlSession(
        _ session: URLSession, task: URLSessionTask, willPerformHTTPRedirection response: HTTPURLResponse,
        newRequest request: URLRequest, completionHandler: @escaping (URLRequest?) -> Void
    ) {
        guard let next = request.url?.absoluteString, asked.door.holds(next) else {
            whyItStopped = .refused
            completionHandler(nil)

            return
        }

        completionHandler(request)
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
            refuse(one.request, because: PlaybackRule.why(status: answered.statusCode))
            waiting[dataTask.taskIdentifier] = nil
            completionHandler(.cancel)

            return
        }

        let type = answered.mimeType?.lowercased() ?? ""
        one.isPlaylist =
            Self.playlistTypes.contains(type) || PlayerExtensions.kind(of: one.address) == .hls

        if let information = one.request.contentInformationRequest {
            information.contentType =
                one.isPlaylist ? UTType.m3uPlaylist.identifier : UTType(mimeType: type)?.identifier
            information.contentLength =
                RangeRule.total(contentRange: answered.value(forHTTPHeaderField: "Content-Range"))
                ?? answered.expectedContentLength
            information.isByteRangeAccessSupported = !one.isPlaylist
        }

        completionHandler(.allow)
    }

    func urlSession(_ session: URLSession, dataTask: URLSessionDataTask, didReceive data: Data) {
        guard let one = waiting[dataTask.taskIdentifier] else {
            return
        }

        if one.isPlaylist {
            one.playlist.append(data)
        } else {
            one.request.dataRequest?.respond(with: data)
        }
    }

    func urlSession(_ session: URLSession, task: URLSessionTask, didCompleteWithError error: Error?) {
        guard let one = waiting.removeValue(forKey: task.taskIdentifier) else {
            return
        }

        if error != nil {
            refuse(one.request, because: whyItStopped ?? .unreachable)

            return
        }

        guard one.isPlaylist else {
            one.request.finishLoading()

            return
        }

        guard let read = String(data: one.playlist, encoding: .utf8),
            let rewritten = playlists.rewrite(read, at: one.address)
        else {
            refuse(one.request, because: .refused)

            return
        }

        one.request.dataRequest?.respond(with: Data(rewritten.utf8))
        one.request.finishLoading()
    }

    // MARK: - Helpers

    /// The `Range` header for what AVPlayer asked for, or nil for the whole.
    private static func range(of loadingRequest: AVAssetResourceLoadingRequest) -> String? {
        guard let data = loadingRequest.dataRequest else {
            return nil
        }

        return RangeRule.asked(
            offset: data.requestedOffset,
            length: data.requestsAllDataToEndOfResource ? nil : Int64(data.requestedLength))
    }

    /// Fail one request, and keep why for the screen.
    private func refuse(_ loadingRequest: AVAssetResourceLoadingRequest, because why: WhyPlaybackStopped) {
        whyItStopped = whyItStopped ?? why
        loadingRequest.finishLoading(
            with: NSError(domain: "app.lemonfiber.player", code: 1, userInfo: ["why": why.word]))
    }
}
