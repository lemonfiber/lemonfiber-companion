package app.lemonfiber.native

/**
 * Whether anything is reachable from this device right now.
 *
 * Two answers and no third. The kind of link, whether it is metered, whether
 * Low Data Mode is on: the platform reports all of it and none of it is read.
 * Nothing about the operator's device is reported, and a value held but not
 * sent is one commit away from being sent.
 *
 * Deliberately mirrors `LinkRule.swift` line for line.
 */
public enum class WhetherAnythingIsReachable(
    /** What this answer is called on the wire. */
    public val word: String,
) {
    /** Something is reachable from here. The app may try. */
    REACHABLE("reachable"),

    /** Nothing is. A stack that does not answer is not the stack's fault. */
    UNREACHABLE("unreachable"),
}

/**
 * What the platform's account of the link means.
 *
 * The distinction this capability exists for is between *this phone has no
 * network* and *that machine is not answering* — two sentences with two
 * different remedies. Telling somebody to go and check their machine when they
 * are in a lift is the kind of wrong that makes an app feel stupid.
 *
 * **Whether the internet answered is deliberately not one of the facts.**
 * Android can say whether a link has been validated, which means a probe
 * reached the internet through it. That is the wrong question here: this
 * application talks to a machine in the operator's house, and a phone on a wifi
 * network with no uplink can still reach the stack in the next room. A rule
 * reading validation would report *no network* to somebody standing five metres
 * from their own stack.
 *
 * No Android framework in sight. Everything is a reading of three facts a
 * caller passes in, which is what lets it be run on a JVM in two seconds rather
 * than demonstrated on a handset.
 *
 * Deliberately mirrors `LinkRule.swift` line for line.
 */
public data class LinkRule(
    /**
     * Whether the platform could be asked at all.
     *
     * Its own fact rather than folded into the other two, because it answers
     * the opposite way: a device that could not be asked is treated as having a
     * link, and the two that describe a link are treated as not having one when
     * they are false.
     */
    public val linkWasReadable: Boolean,
    /** Whether any network interface is up and joined to something. */
    public val hasAnActiveLink: Boolean,
    /** Whether that link is one traffic can go over. */
    public val carriesTraffic: Boolean,
) {
    /**
     * What to tell the app, read in this order.
     *
     * The order is load-bearing. *Could not be asked* is answered first and
     * answered as **reachable**, which is the opposite of the cautious reading
     * and is the right one here: a launch that cannot ask would otherwise land
     * on every desktop and every test run in a state whose remedy is *turn your
     * wifi on*. Reading it as reachable means the app tries, and a stack it
     * cannot reach is reported the way it always was — one attempt wasted, and
     * the honest answer.
     */
    public val said: WhetherAnythingIsReachable
        get() =
            when {
                !linkWasReadable -> WhetherAnythingIsReachable.REACHABLE
                hasAnActiveLink && carriesTraffic -> WhetherAnythingIsReachable.REACHABLE
                else -> WhetherAnythingIsReachable.UNREACHABLE
            }

    /** Where the starting states live, named so they read at a call site. */
    public companion object {
        /** A device on a link that works: what a phone in a house is. */
        public val CONNECTED: LinkRule =
            LinkRule(linkWasReadable = true, hasAnActiveLink = true, carriesTraffic = true)

        /** A device whose platform would not answer, which answers reachable. */
        public val UNREADABLE: LinkRule =
            LinkRule(linkWasReadable = false, hasAnActiveLink = false, carriesTraffic = false)
    }
}
