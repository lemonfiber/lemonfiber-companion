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
 * address is what ends up in a log, a cache or a crash report. A grant with
 * anything in it a header cannot safely carry is refused for the same reason
 * a header with a line break in it is never sent.
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
    /** The member's grant, sent with every request and kept nowhere else. */
    public val grant: String,
    /** Where to start, in seconds from the beginning. */
    public val startAt: Double,
    /** What the member is shown and heard while it plays. */
    public val shown: HowToShowIt,
) {
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
        /** The lowest byte a header can carry: the first visible character. */
        private const val FIRST_VISIBLE = 0x21

        /** The highest. */
        private const val LAST_VISIBLE = 0x7E

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
                        grant = grant,
                        startAt = held.third,
                        shown =
                            HowToShowIt(
                                title = text(parameters, "title"),
                                audio = text(parameters, "audio"),
                                subtitle = text(parameters, "subtitle"),
                            ),
                    ),
                )
            }
        }

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
                !isCarriable(grant) -> WhyNot.NO_GRANT
                location.contains(grant) -> WhyNot.GRANT_IN_THE_ADDRESS
                startAt == null -> WhyNot.NO_STARTING_POINT
                else -> null
            }

        /** One value the request carried as text, or empty where it carried none. */
        private fun text(
            parameters: Map<String, Any>,
            key: String,
        ): String = parameters[key] as? String ?: ""

        /** Whether a grant is one a header can carry: something, and only visible ASCII. */
        private fun isCarriable(grant: String): Boolean =
            grant.isNotEmpty() && grant.all { it.code in FIRST_VISIBLE..LAST_VISIBLE }
    }
}
