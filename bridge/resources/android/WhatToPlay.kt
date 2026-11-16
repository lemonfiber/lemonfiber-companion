package app.lemonfiber.native

/**
 * What the app asked the player to play, read and checked before anything is fetched.
 *
 * Everything here came across the bridge from the app, which had it from the
 * core: where the title streams from, the fingerprint of the door that serves
 * it, the member's grant, and where to start. Each is checked as it is read,
 * and **anything that does not hold refuses the whole request with a closed
 * word**, because a player that guessed at a missing pin would play from a
 * door nobody vouched for.
 *
 * **The grant travels in a header and never in an address.** One that is
 * already written into the location is refused rather than used, because an
 * address is what ends up in a log, a cache or a crash report. **A grant is
 * 32 lowercase hexadecimal digits**, as the media server issues one, and it
 * is sent exactly so, as `Authorization: Bearer <grant>`: the door accepts
 * that form and nothing else, and turns it into what the media server reads.
 * The player names no media server's own header, token form or query key.
 *
 * **A channel is the one exception, and it is read apart** ([readChannel]):
 * the core opens its live stream and answers where it is served with the
 * pass the door lets it through on written into each address, which is how
 * the door asks for it. So a channel carries no grant and no header, both
 * its addresses are held to one door and pinned, and they are never logged,
 * cached or put in an intent. The tuner's own stream is tried first and the
 * transcoded playlist is the [fallback] where the device cannot decode it.
 *
 * Nothing here is ever logged. A refusal is one of the closed words below.
 *
 * Deliberately mirrors `WhatToPlay.swift` line for line.
 */
public class WhatToPlay private constructor(
    /** Where the title streams from, as the core stated it. */
    public val location: String,
    /** The door it streams from. */
    public val door: Door,
    /** The certificate that door promised. */
    public val pin: DoorPin,
    /** Where to start, in seconds from the beginning. */
    public val startAt: Double,
    /** What the member is shown and heard while it plays. */
    public val shown: HowToShowIt,
    /** What lets it through the door: a title's grant, or a channel's pass in its address. */
    public val admission: Admission,
) {
    /** What lets one playback through the door. */
    public sealed class Admission {
        /** A title's grant, sent with every request and kept nowhere else. */
        public data class Grant(
            /** The grant, as the media server issued it. */
            public val grant: String,
        ) : Admission()

        /** A channel's pass, already in its address, and where to play from instead. */
        public data class Pass(
            /** Its transcoded playlist, played where the tuner's own stream will not decode, or empty. */
            public val fallback: String,
        ) : Admission()
    }

    /** The member's grant, or empty for a channel, whose pass is in its address. */
    public val grant: String
        get() = (admission as? Admission.Grant)?.grant ?: ""

    /** How the grant is written in its header: exactly as it was issued, after one fixed word. */
    public val grantHeaderValue: String
        get() = "Bearer $grant"

    /** Whether a grant goes with every request: a title's does, and a channel's pass is in its address. */
    public val carriesAGrant: Boolean
        get() = admission is Admission.Grant

    /** Whether this is a channel's live stream, which reports while paused and has no place to keep. */
    public val isLive: Boolean
        get() = admission is Admission.Pass

    /** Where to play from instead where the device cannot decode [location], or empty. */
    public val fallback: String
        get() = (admission as? Admission.Pass)?.fallback ?: ""

    /** The same channel, played from its fallback, or null where there is none to fall back to. */
    public fun fallenBack(): WhatToPlay? =
        if (fallback.isEmpty()) {
            null
        } else {
            WhatToPlay(fallback, door, pin, startAt, shown, Admission.Pass(fallback = ""))
        }

    /** What the member is shown and heard while it plays. */
    public data class HowToShowIt(
        /** What the title is called, for the lock screen and the picture-in-picture window. */
        public val title: String,
        /** The language to play the sound in, where the member has a preference. */
        public val audio: String,
        /** The language to show subtitles in, or empty for none. */
        public val subtitle: String,
    )

    /** The closed words a request is refused with. */
    public enum class WhyNot(
        /** What this refusal is called on the wire. */
        public val word: String,
    ) {
        /** The location is not an `https` address at a door. */
        NOT_AT_A_DOOR("not_at_a_door"),

        /** The fingerprint is not one. */
        UNPINNED("unpinned"),

        /** There is no grant, or one a header cannot carry. */
        NO_GRANT("no_grant"),

        /** The grant is written into the address. */
        GRANT_IN_THE_ADDRESS("grant_in_the_address"),

        /** Where to start is not a time. */
        NO_STARTING_POINT("no_starting_point"),

        /** A channel came with neither the tuner's own stream nor a transcoded playlist. */
        NOWHERE_TO_PLAY("nowhere_to_play"),
    }

    /** What became of reading a request. */
    public sealed class Read {
        /** It holds, and this is what to play. */
        public class ToPlay(
            /** What to play. */
            public val asked: WhatToPlay,
        ) : Read()

        /** It does not, and this is why. */
        public class Refused(
            /** Why not. */
            public val why: WhyNot,
        ) : Read()
    }

    /** How a request is read. */
    public companion object {
        /** The header every request carries the grant in. */
        public const val GRANT_HEADER: String = "Authorization"

        /** How many digits a grant is, as the media server issues one. */
        private const val GRANT_DIGITS = 32

        /**
         * The request the bridge carried, checked.
         *
         * @param parameters what the app sent.
         * @return what to play, or why not.
         */
        public fun read(parameters: Map<String, Any>): Read {
            val location = text(parameters, "location")
            val grant = text(parameters, "grant")
            val door = Door.of(location)
            val pin = DoorPin.of(text(parameters, "fingerprint"))
            val startAt = (parameters["start_at"] as? Number)?.toDouble()?.takeIf { it >= 0 }
            val why = whyNot(location, grant, door, pin, startAt)
            val held = door?.let { at -> pin?.let { promised -> startAt?.let { Triple(at, promised, it) } } }

            return if (why != null || held == null) {
                Read.Refused(why ?: WhyNot.NOT_AT_A_DOOR)
            } else {
                Read.ToPlay(
                    WhatToPlay(
                        location = location,
                        door = held.first,
                        pin = held.second,
                        startAt = held.third,
                        shown =
                            HowToShowIt(
                                title = text(parameters, "title"),
                                audio = text(parameters, "audio"),
                                subtitle = text(parameters, "subtitle"),
                            ),
                        admission = Admission.Grant(grant),
                    ),
                )
            }
        }

        /**
         * A channel the bridge carried, checked: the tuner's own stream first, then its transcoded playlist.
         *
         * @param parameters what the app sent.
         * @return what to play, or why not.
         */
        public fun readChannel(parameters: Map<String, Any>): Read {
            val direct = text(parameters, "direct")
            val transcoded = text(parameters, "transcoded")
            val location = direct.ifEmpty { transcoded }
            val door = Door.of(location)
            val pin = DoorPin.of(text(parameters, "fingerprint"))
            val why =
                when {
                    location.isEmpty() -> WhyNot.NOWHERE_TO_PLAY
                    door == null || listOf(direct, transcoded).any { atAnotherDoor(it, door) } ->
                        WhyNot.NOT_AT_A_DOOR
                    pin == null -> WhyNot.UNPINNED
                    else -> null
                }

            return if (why != null || door == null || pin == null) {
                Read.Refused(why ?: WhyNot.NOT_AT_A_DOOR)
            } else {
                Read.ToPlay(
                    WhatToPlay(
                        location = location,
                        door = door,
                        pin = pin,
                        startAt = 0.0,
                        shown =
                            HowToShowIt(
                                title = text(parameters, "title"),
                                audio = "",
                                subtitle = "",
                            ),
                        admission = Admission.Pass(fallback = if (direct.isEmpty()) "" else transcoded),
                    ),
                )
            }
        }

        /** Whether an address a channel was sent is at a door other than the one it plays from. */
        private fun atAnotherDoor(
            address: String,
            door: Door,
        ): Boolean = address.isNotEmpty() && Door.of(address) != door

        /** The first thing about a request that does not hold, in the order they are checked, or null. */
        private fun whyNot(
            location: String,
            grant: String,
            door: Door?,
            pin: DoorPin?,
            startAt: Double?,
        ): WhyNot? =
            when {
                door == null -> WhyNot.NOT_AT_A_DOOR
                pin == null -> WhyNot.UNPINNED
                !isAGrant(grant) -> WhyNot.NO_GRANT
                location.contains(grant) -> WhyNot.GRANT_IN_THE_ADDRESS
                startAt == null -> WhyNot.NO_STARTING_POINT
                else -> null
            }

        /** One value the request carried as text, or empty where it carried none. */
        private fun text(
            parameters: Map<String, Any>,
            key: String,
        ): String = parameters[key] as? String ?: ""

        /** Whether a grant is one as the media server issues it: 32 lowercase hexadecimal digits. */
        private fun isAGrant(grant: String): Boolean =
            grant.length == GRANT_DIGITS && grant.all { it in '0'..'9' || it in 'a'..'f' }
    }
}
