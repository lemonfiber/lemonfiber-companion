import Testing

@testable import LemonfiberNative

// Every extension the player was built with, and what they decide together.
//
// The same cases as `PlayerExtensionsTest.kt`, in the same order.

private let theFingerprint = "13ee3a6685a324f7ebbfebb922980ba07a74c0e54cf2243b2f199dc81912e9f5"

private func asked(_ location: String = "https://door.home:8443/library/1/main.m3u8") -> WhatToPlay? {
    let request: [String: Any] = [
        "location": location, "fingerprint": theFingerprint, "grant": "0123456789abcdef0123456789abcdef",
        "start_at": 0,
    ]

    if case .toPlay(let asked) = WhatToPlay.read(request) {
        return asked
    }

    return nil
}

private final class AResolver: SourceResolver {
    let claiming: Bool
    let address: String

    init(claiming: Bool, address: String) {
        self.claiming = claiming
        self.address = address
    }

    func claims(_ asked: WhatToPlay) -> Bool { claiming }

    func source(for asked: WhatToPlay) -> PlayableSource {
        PlayableSource(address: address, kind: .progressive)
    }
}

private final class AProvider: TrackProvider {
    func tracks(for asked: WhatToPlay) -> [ExtraTrack] {
        [
            ExtraTrack(
                id: "s1", kind: .subtitle, language: "en", label: "English",
                address: "https://door.home:8443/library/1/en.vtt"),
            ExtraTrack(
                id: "s2", kind: .subtitle, language: "nl", label: "Nederlands",
                address: "https://elsewhere.example/nl.vtt"),
        ]
    }
}

private final class AnObserver: PlaybackObserver {
    let name: String
    let heard: TheLog

    init(name: String, heard: TheLog) {
        self.name = name
        self.heard = heard
    }

    func observe(_ happening: PlayerHappening) {
        heard.lines.append("\(name): \(happening)")
    }
}

private final class TheLog {
    var lines: [String] = []
}

private final class ARouter: RouteProvider {
    func routes() -> [PlaybackRoute] {
        [PlaybackRoute(id: "living-room", label: "Living room")]
    }
}

@Test("a request no extension claims is played from where the core said")
func anUnclaimedRequestIsPlayedFromTheLocation() throws {
    let extensions = PlayerExtensions()
    extensions.register(source: AResolver(claiming: false, address: "https://door.home:8443/other.mp4"))

    let request = try #require(asked())

    #expect(
        extensions.source(for: request)
            == PlayableSource(address: "https://door.home:8443/library/1/main.m3u8", kind: .hls))
}

@Test("the first extension to claim a request resolves it")
func theFirstClaimantResolves() throws {
    let extensions = PlayerExtensions()
    extensions.register(source: AResolver(claiming: true, address: "https://door.home:8443/first.mp4"))
    extensions.register(source: AResolver(claiming: true, address: "https://door.home:8443/second.mp4"))

    let request = try #require(asked())

    #expect(extensions.source(for: request)?.address == "https://door.home:8443/first.mp4")
}

@Test("an extension that resolves off the door resolves nothing")
func aResolutionOffTheDoorIsRefused() throws {
    let extensions = PlayerExtensions()
    extensions.register(source: AResolver(claiming: true, address: "https://elsewhere.example/a.mp4"))

    let request = try #require(asked())

    #expect(extensions.source(for: request) == nil)
}

@Test("extra tracks off the door are not offered")
func extraTracksOffTheDoorAreNotOffered() throws {
    let extensions = PlayerExtensions()
    extensions.register(tracks: AProvider())

    let request = try #require(asked())

    #expect(extensions.extraTracks(for: request).map(\.id) == ["s1"])
}

@Test("every observer is told, in the order it was added")
func everyObserverIsToldInOrder() {
    let heard = TheLog()
    let extensions = PlayerExtensions()
    extensions.register(observer: AnObserver(name: "first", heard: heard))
    extensions.register(observer: AnObserver(name: "second", heard: heard))

    extensions.tell(.ended)

    #expect(heard.lines == ["first: ended", "second: ended"])
}

@Test("every happening reaches an observer with what it carries")
func everyHappeningCarriesItsFacts() {
    let heard = TheLog()
    let extensions = PlayerExtensions()
    extensions.register(observer: AnObserver(name: "one", heard: heard))

    for happening: PlayerHappening in [
        .ready(duration: 5400), .progressed(position: 10), .paused(position: 12), .ended,
        .stopped(.pinMismatch), .closed(position: 12),
    ] {
        extensions.tell(happening)
    }

    #expect(
        heard.lines == [
            "one: ready(duration: 5400.0)", "one: progressed(position: 10.0)", "one: paused(position: 12.0)",
            "one: ended", "one: stopped(LemonfiberNative.WhyPlaybackStopped.pinMismatch)",
            "one: closed(position: 12.0)",
        ])
}

@Test("every route on offer is listed")
func everyRouteIsListed() {
    let extensions = PlayerExtensions()
    extensions.register(routes: ARouter())

    #expect(extensions.routes() == [PlaybackRoute(id: "living-room", label: "Living room")])
}

@Test("how a stream is packaged is read from the end of its path")
func packagingIsReadFromThePath() {
    #expect(PlayerExtensions.kind(of: "https://door.home/a/main.M3U8?x=1") == .hls)
    #expect(PlayerExtensions.kind(of: "https://door.home/a/manifest.mpd#t=1") == .dash)
    #expect(PlayerExtensions.kind(of: "https://door.home/a/film.mkv") == .progressive)
}
