/// Whether anything is reachable from this device right now.
///
/// Two answers and no third. The kind of link, whether it is metered, whether
/// Low Data Mode is on: the platform reports all of it and none of it is read.
/// Nothing about the operator's device is reported, and a value held but not
/// sent is one commit away from being sent.
///
/// Deliberately mirrors `LinkRule.kt` line for line.
public enum WhetherAnythingIsReachable: String, Sendable {
    /// Something is reachable from here. The app may try.
    case reachable = "reachable"

    /// Nothing is. A stack that does not answer is not the stack's fault.
    case unreachable = "unreachable"

    /// What this answer is called on the wire.
    public var word: String { rawValue }
}

/// What the platform's account of the link means.
///
/// The distinction this capability exists for is between *this phone has no
/// network* and *that machine is not answering* — two sentences with two
/// different remedies. Telling somebody to go and check their machine when they
/// are in a lift is the kind of wrong that makes an app feel stupid.
///
/// **Whether the internet answered is deliberately not one of the facts.**
/// Android can say whether a link has been validated, which means a probe
/// reached the internet through it. That is the wrong question here: this
/// application talks to a machine in the operator's house, and a phone on a wifi
/// network with no uplink can still reach the stack in the next room. A rule
/// reading validation would report *no network* to somebody standing five metres
/// from their own stack.
///
/// No Apple framework in sight. Everything is a reading of three facts a
/// caller passes in, which is what lets it be run in SwiftPM in one second
/// rather than demonstrated on a handset.
///
/// Deliberately mirrors `LinkRule.kt` line for line.
public struct LinkRule: Equatable, Sendable {
    /// Whether the platform could be asked at all.
    ///
    /// Its own fact rather than folded into the other two, because it answers
    /// the opposite way: a device that could not be asked is treated as having a
    /// link, and the two that describe a link are treated as not having one when
    /// they are false.
    public let linkWasReadable: Bool

    /// Whether any network interface is up and joined to something.
    public let hasAnActiveLink: Bool

    /// Whether that link is one traffic can go over.
    public let carriesTraffic: Bool

    /// Built from what the platform said, or from it having said nothing.
    public init(linkWasReadable: Bool, hasAnActiveLink: Bool, carriesTraffic: Bool) {
        self.linkWasReadable = linkWasReadable
        self.hasAnActiveLink = hasAnActiveLink
        self.carriesTraffic = carriesTraffic
    }

    /// What to tell the app, read in this order.
    ///
    /// The order is load-bearing. *Could not be asked* is answered first and
    /// answered as **reachable**, which is the opposite of the cautious reading
    /// and is the right one here: a launch that cannot ask would otherwise land
    /// on every desktop and every test run in a state whose remedy is *turn your
    /// wifi on*. Reading it as reachable means the app tries, and a stack it
    /// cannot reach is reported the way it always was — one attempt wasted, and
    /// the honest answer.
    public var said: WhetherAnythingIsReachable {
        if !linkWasReadable {
            return .reachable
        }

        return hasAnActiveLink && carriesTraffic ? .reachable : .unreachable
    }

    /// A device on a link that works: what a phone in a house is.
    public static let connected = LinkRule(
        linkWasReadable: true, hasAnActiveLink: true, carriesTraffic: true)

    /// A device whose platform would not answer, which answers reachable.
    public static let unreadable = LinkRule(
        linkWasReadable: false, hasAnActiveLink: false, carriesTraffic: false)
}
