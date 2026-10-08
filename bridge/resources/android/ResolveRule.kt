package app.lemonfiber.native

/**
 * What a look-up of a machine's name found.
 *
 * Two answers and no third. Which addresses were found travels beside the
 * word, and only where it is [FOUND].
 *
 * Deliberately mirrors `ResolveRule.swift` line for line.
 */
public enum class WhatTheLookupFound(
    /** What this answer is called on the wire. */
    public val word: String,
) {
    /** The name turned into at least one address the app can send to. */
    FOUND("found"),

    /** It turned into none: not found, not in time, or only addresses the app cannot use. */
    NOTHING("nothing"),
}

/**
 * One address the platform's resolver gave for a name.
 *
 * The facts the rule reads, as plain values, so the rule runs with no
 * resolver in sight.
 */
public data class AnAddressFound(
    /** The address in its numeric form, as the platform wrote it. */
    public val numeric: String,
    /** Whether it is an IPv4 address, which every transport the app has can send to. */
    public val isVersion4: Boolean,
    /**
     * Whether it is an IPv6 address that means something only beside the
     * interface it was found on, which a URL can carry and a TLS connection
     * opened from PHP cannot.
     */
    public val needsAnInterface: Boolean,
)

/**
 * Which of the addresses a name resolved to the app may send to, and in what order.
 *
 * The phone's own resolver answers names the app's runtime cannot, a `.local`
 * name above all, and the app sends to what it found while keeping the name it
 * was paired with. Whatever is not usable as it stands is dropped here, so the
 * app never has to guess about an address it was handed.
 *
 * **IPv4 first.** A machine on a home network answers on IPv4 wherever it
 * answers at all, while an IPv6 address the phone sees may be one the machine
 * does not listen on. The order within each family is the platform's.
 *
 * **An address that needs its interface is dropped.** It is unusable without
 * a name for the interface, and that name is the phone's, not the machine's.
 *
 * No Android framework in sight.
 *
 * Deliberately mirrors `ResolveRule.swift` line for line.
 */
public data class ResolveRule(
    /** Every address the platform gave, in the order it gave them, or none. */
    public val found: List<AnAddressFound>,
) {
    /** The addresses the app may send to, IPv4 first, each once. */
    public val usable: List<String>
        get() =
            found
                .filterNot { it.needsAnInterface || it.numeric.isEmpty() }
                .sortedBy { !it.isVersion4 }
                .map { it.numeric }
                .distinct()

    /** What to tell the app: found where anything is usable, and nothing otherwise. */
    public val said: WhatTheLookupFound
        get() = if (usable.isEmpty()) WhatTheLookupFound.NOTHING else WhatTheLookupFound.FOUND

    /** Where the starting states live, named so they read at a call site. */
    public companion object {
        /** A look-up that found nothing, or was never made. */
        public val NOTHING_FOUND: ResolveRule = ResolveRule(found = emptyList())
    }
}
