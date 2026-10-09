/// How a stream is packaged, which decides how the platform's player reads it.
///
/// Deliberately mirrors `PlayerExtensions.kt` line for line.
public enum KindOfSource: String, Sendable {
    /// HTTP Live Streaming: a playlist of segments, with renditions to adapt between.
    case hls = "hls"

    /// MPEG-DASH: a manifest of segments, with representations to adapt between.
    case dash = "dash"

    /// One file, read in ranges from wherever the viewer seeks to.
    case progressive = "progressive"
}

/// What the player is to open: an address at the door, and how it is packaged.
public struct PlayableSource: Equatable, Sendable {
    /// Where it is.
    public let address: String

    /// How it is packaged.
    public let kind: KindOfSource

    /// A source at an address, packaged one way.
    public init(address: String, kind: KindOfSource) {
        self.address = address
        self.kind = kind
    }
}

/// Whether a track is sound or subtitles.
public enum KindOfTrack: String, Sendable {
    /// What is heard.
    case audio = "audio"

    /// What is read.
    case subtitle = "subtitle"
}

/// A track the stream does not carry, offered from elsewhere at the door.
public struct ExtraTrack: Equatable, Sendable {
    /// What the player calls it.
    public let id: String

    /// Sound or subtitles.
    public let kind: KindOfTrack

    /// Its language, as a tag.
    public let language: String

    /// What to label it.
    public let label: String

    /// Where it is.
    public let address: String

    /// A track offered from an address.
    public init(id: String, kind: KindOfTrack, language: String, label: String, address: String) {
        self.id = id
        self.kind = kind
        self.language = language
        self.label = label
        self.address = address
    }
}

/// Something that happened while playing, as observers are told it.
public enum PlayerHappening: Equatable, Sendable {
    /// The stream opened and is this long, in seconds.
    case ready(duration: Double)

    /// The picture got this far.
    case progressed(position: Double)

    /// The viewer paused here.
    case paused(position: Double)

    /// It played to the end.
    case ended

    /// It stopped and could not go on, for this reason.
    case stopped(WhyPlaybackStopped)

    /// The viewer closed the player here.
    case closed(position: Double)
}

/// A place the picture can be sent to instead of this screen.
public struct PlaybackRoute: Equatable, Sendable {
    /// What the route is called by whatever offers it.
    public let id: String

    /// What the member is shown.
    public let label: String

    /// A route as it is offered.
    public init(id: String, label: String) {
        self.id = id
        self.label = label
    }
}

/// Decides where a request is played from, where it is one this extension knows.
public protocol SourceResolver: AnyObject {
    /// Whether this extension resolves this request.
    func claims(_ asked: WhatToPlay) -> Bool

    /// The source to play, which must be at the request's door.
    func source(for asked: WhatToPlay) -> PlayableSource
}

/// Offers tracks the stream itself does not carry.
public protocol TrackProvider: AnyObject {
    /// Every track this extension offers for this request.
    func tracks(for asked: WhatToPlay) -> [ExtraTrack]
}

/// Hears what happens while playing. It is told, and it sends nothing anywhere of its own.
public protocol PlaybackObserver: AnyObject {
    /// One thing that happened.
    func observe(_ happening: PlayerHappening)
}

/// Offers places to send the picture to.
public protocol RouteProvider: AnyObject {
    /// Every route this extension can send the picture to right now.
    func routes() -> [PlaybackRoute]
}

/// Every extension the player was built with, and what they decide together.
///
/// **Compiled in, never loaded.** Extensions are registered when the app
/// starts, from code that shipped in it; nothing reaches the player at run
/// time from anywhere else.
///
/// **Every address an extension names is held to the door.** A resolver whose
/// source is elsewhere resolves nothing, and a track elsewhere is not offered,
/// because the pin covers the door and nothing else — an extension is not a
/// way round it.
///
/// Extensions are asked in the order they were registered. Where none claims a
/// request, it is played from the location the core stated, packaged as its
/// address says.
public final class PlayerExtensions {
    private var resolvers: [any SourceResolver] = []
    private var providers: [any TrackProvider] = []
    private var observers: [any PlaybackObserver] = []
    private var routers: [any RouteProvider] = []

    /// A player with no extensions yet.
    public init() {}

    /// Add a resolver, asked after every one added before it.
    public func register(source resolver: any SourceResolver) {
        resolvers.append(resolver)
    }

    /// Add a track provider.
    public func register(tracks provider: any TrackProvider) {
        providers.append(provider)
    }

    /// Add an observer.
    public func register(observer: any PlaybackObserver) {
        observers.append(observer)
    }

    /// Add a route provider.
    public func register(routes provider: any RouteProvider) {
        routers.append(provider)
    }

    /// The source to play, or nil where the extension that claimed the request named one off its door.
    ///
    /// - Parameter asked: what the app asked to play.
    /// - Returns: the source, or nil.
    public func source(for asked: WhatToPlay) -> PlayableSource? {
        guard let resolver = resolvers.first(where: { $0.claims(asked) }) else {
            return PlayableSource(address: asked.location, kind: Self.kind(of: asked.location))
        }

        let source = resolver.source(for: asked)

        return asked.door.holds(source.address) ? source : nil
    }

    /// Every extra track on offer for a request, leaving out any off its door.
    public func extraTracks(for asked: WhatToPlay) -> [ExtraTrack] {
        providers.flatMap { $0.tracks(for: asked) }.filter { asked.door.holds($0.address) }
    }

    /// Tell every observer, in the order they were added.
    public func tell(_ happening: PlayerHappening) {
        for observer in observers {
            observer.observe(happening)
        }
    }

    /// Every route on offer from every provider.
    public func routes() -> [PlaybackRoute] {
        routers.flatMap { $0.routes() }
    }

    /// How an address is packaged, read from the end of its path.
    ///
    /// - Parameter address: where the stream is.
    /// - Returns: its packaging.
    public static func kind(of address: String) -> KindOfSource {
        let path = String(address.prefix { $0 != "?" && $0 != "#" }).lowercased()

        if path.hasSuffix(".m3u8") {
            return .hls
        }

        return path.hasSuffix(".mpd") ? .dash : .progressive
    }
}
