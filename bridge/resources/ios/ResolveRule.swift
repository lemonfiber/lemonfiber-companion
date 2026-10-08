/// What a look-up of a machine's name found.
///
/// Two answers and no third. Which addresses were found travels beside the
/// word, and only where it is `found`.
///
/// Deliberately mirrors `ResolveRule.kt` line for line.
public enum WhatTheLookupFound: String, Sendable {
    /// The name turned into at least one address the app can send to.
    case found = "found"

    /// It turned into none: not found, not in time, or only addresses the app cannot use.
    case nothing = "nothing"

    /// What this answer is called on the wire.
    public var word: String { rawValue }
}

/// One address the platform's resolver gave for a name.
///
/// The facts the rule reads, as plain values, so the rule runs with no
/// resolver in sight.
public struct AnAddressFound: Equatable, Sendable {
    /// The address in its numeric form, as the platform wrote it.
    public let numeric: String

    /// Whether it is an IPv4 address, which every transport the app has can send to.
    public let isVersion4: Bool

    /// Whether it is an IPv6 address that means something only beside the
    /// interface it was found on, which a URL can carry and a TLS connection
    /// opened from PHP cannot.
    public let needsAnInterface: Bool

    /// Built from what the resolver gave.
    public init(_ numeric: String, isVersion4: Bool, needsAnInterface: Bool) {
        self.numeric = numeric
        self.isVersion4 = isVersion4
        self.needsAnInterface = needsAnInterface
    }
}

/// Which of the addresses a name resolved to the app may send to, and in what order.
///
/// The phone's own resolver answers names the app's runtime cannot, a `.local`
/// name above all, and the app sends to what it found while keeping the name it
/// was paired with. Whatever is not usable as it stands is dropped here, so the
/// app never has to guess about an address it was handed.
///
/// **IPv4 first.** A machine on a home network answers on IPv4 wherever it
/// answers at all, while an IPv6 address the phone sees may be one the machine
/// does not listen on. The order within each family is the platform's.
///
/// **An address that needs its interface is dropped.** It is unusable without
/// a name for the interface, and that name is the phone's, not the machine's.
///
/// No Apple framework in sight, for `LinkRule`'s reason.
///
/// Deliberately mirrors `ResolveRule.kt` line for line.
public struct ResolveRule: Equatable, Sendable {
    /// Every address the platform gave, in the order it gave them, or none.
    public let found: [AnAddressFound]

    /// Built from what the resolver gave, or from it having given nothing.
    public init(_ found: [AnAddressFound]) {
        self.found = found
    }

    /// The addresses the app may send to, IPv4 first, each once.
    public var usable: [String] {
        let sendable = found.filter { !$0.needsAnInterface && !$0.numeric.isEmpty }
        let ordered = sendable.filter(\.isVersion4) + sendable.filter { !$0.isVersion4 }
        var seen = Set<String>()

        return ordered.map(\.numeric).filter { seen.insert($0).inserted }
    }

    /// What to tell the app: found where anything is usable, and nothing otherwise.
    public var said: WhatTheLookupFound {
        usable.isEmpty ? .nothing : .found
    }

    /// A look-up that found nothing, or was never made.
    public static let nothingFound = ResolveRule([])
}
