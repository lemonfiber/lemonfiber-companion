package app.lemonfiber.native

/**
 * How a stream is packaged, which decides how the platform's player reads it.
 *
 * Deliberately mirrors `PlayerExtensions.swift` line for line.
 */
public enum class KindOfSource(
    /** What this packaging is called on the wire. */
    public val word: String,
) {
    /** HTTP Live Streaming: a playlist of segments, with renditions to adapt between. */
    HLS("hls"),

    /** MPEG-DASH: a manifest of segments, with representations to adapt between. */
    DASH("dash"),

    /** One file, read in ranges from wherever the viewer seeks to. */
    PROGRESSIVE("progressive"),
}

/** What the player is to open: an address at the door, and how it is packaged. */
public data class PlayableSource(
    /** Where it is. */
    public val address: String,
    /** How it is packaged. */
    public val kind: KindOfSource,
)

/** Whether a track is sound or subtitles. */
public enum class KindOfTrack(
    /** What this kind is called on the wire. */
    public val word: String,
) {
    /** What is heard. */
    AUDIO("audio"),

    /** What is read. */
    SUBTITLE("subtitle"),
}

/** A track the stream does not carry, offered from elsewhere at the door. */
public data class ExtraTrack(
    /** What the player calls it. */
    public val id: String,
    /** Sound or subtitles. */
    public val kind: KindOfTrack,
    /** Its language, as a tag. */
    public val language: String,
    /** What to label it. */
    public val label: String,
    /** Where it is. */
    public val address: String,
)

/** Something that happened while playing, as observers are told it. */
public sealed class PlayerHappening {
    /** The stream opened and is this long, in seconds. */
    public data class Ready(
        /** How long the stream is, in seconds. */
        public val duration: Double,
    ) : PlayerHappening()

    /** The picture got this far. */
    public data class Progressed(
        /** How far, in seconds. */
        public val position: Double,
    ) : PlayerHappening()

    /** The viewer paused here. */
    public data class Paused(
        /** Where, in seconds. */
        public val position: Double,
    ) : PlayerHappening()

    /** It played to the end. */
    public data object Ended : PlayerHappening()

    /** It stopped and could not go on, for this reason. */
    public data class Stopped(
        /** Why it could not go on. */
        public val why: WhyPlaybackStopped,
    ) : PlayerHappening()

    /** The viewer closed the player here. */
    public data class Closed(
        /** Where, in seconds. */
        public val position: Double,
    ) : PlayerHappening()
}

/** A place the picture can be sent to instead of this screen. */
public data class PlaybackRoute(
    /** What the route is called by whatever offers it. */
    public val id: String,
    /** What the member is shown. */
    public val label: String,
)

/** Decides where a request is played from, where it is one this extension knows. */
public interface SourceResolver {
    /** Whether this extension resolves this request. */
    public fun claims(asked: WhatToPlay): Boolean

    /** The source to play, which must be at the request's door. */
    public fun source(asked: WhatToPlay): PlayableSource
}

/** Offers tracks the stream itself does not carry. */
public fun interface TrackProvider {
    /** Every track this extension offers for this request. */
    public fun tracks(asked: WhatToPlay): List<ExtraTrack>
}

/** Hears what happens while playing. It is told, and it sends nothing anywhere of its own. */
public fun interface PlaybackObserver {
    /** One thing that happened. */
    public fun observe(happening: PlayerHappening)
}

/** Offers places to send the picture to. */
public fun interface RouteProvider {
    /** Every route this extension can send the picture to right now. */
    public fun routes(): List<PlaybackRoute>
}

/**
 * Every extension the player was built with, and what they decide together.
 *
 * **Compiled in, never loaded.** Extensions are registered when the app
 * starts, from code that shipped in it; nothing reaches the player at run
 * time from anywhere else.
 *
 * **Every address an extension names is held to the door.** A resolver whose
 * source is elsewhere resolves nothing, and a track elsewhere is not offered,
 * because the pin covers the door and nothing else — an extension is not a
 * way round it.
 *
 * Extensions are asked in the order they were registered. Where none claims a
 * request, it is played from the location the core stated, packaged as its
 * address says.
 */
public class PlayerExtensions {
    private val resolvers = mutableListOf<SourceResolver>()
    private val providers = mutableListOf<TrackProvider>()
    private val observers = mutableListOf<PlaybackObserver>()
    private val routers = mutableListOf<RouteProvider>()

    /** Add a resolver, asked after every one added before it. */
    public fun registerSource(resolver: SourceResolver) {
        resolvers.add(resolver)
    }

    /** Add a track provider. */
    public fun registerTracks(provider: TrackProvider) {
        providers.add(provider)
    }

    /** Add an observer. */
    public fun registerObserver(observer: PlaybackObserver) {
        observers.add(observer)
    }

    /** Add a route provider. */
    public fun registerRoutes(provider: RouteProvider) {
        routers.add(provider)
    }

    /**
     * The source to play, or null where the extension that claimed the request named one off its door.
     *
     * @param asked what the app asked to play.
     * @return the source, or null.
     */
    public fun source(asked: WhatToPlay): PlayableSource? {
        val resolver =
            resolvers.firstOrNull { it.claims(asked) }
                ?: return PlayableSource(asked.location, kind(asked.location))
        val source = resolver.source(asked)

        return if (asked.door.holds(source.address)) source else null
    }

    /** Every extra track on offer for a request, leaving out any off its door. */
    public fun extraTracks(asked: WhatToPlay): List<ExtraTrack> =
        providers.flatMap { it.tracks(asked) }.filter { asked.door.holds(it.address) }

    /** Tell every observer, in the order they were added. */
    public fun tell(happening: PlayerHappening) {
        for (observer in observers) {
            observer.observe(happening)
        }
    }

    /** Every route on offer from every provider. */
    public fun routes(): List<PlaybackRoute> = routers.flatMap { it.routes() }

    /** How a stream's packaging is read. */
    public companion object {
        /**
         * How an address is packaged, read from the end of its path.
         *
         * @param address where the stream is.
         * @return its packaging.
         */
        public fun kind(address: String): KindOfSource {
            val path = address.takeWhile { it != '?' && it != '#' }.lowercase()

            if (path.endsWith(".m3u8")) {
                return KindOfSource.HLS
            }

            return if (path.endsWith(".mpd")) KindOfSource.DASH else KindOfSource.PROGRESSIVE
        }
    }
}
