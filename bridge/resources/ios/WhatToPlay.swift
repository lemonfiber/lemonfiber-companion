/// What the app asked the player to play, read and checked before anything is fetched.
///
/// Everything here came across the bridge from the app, which had it from the
/// core: where the title streams from, the fingerprint of the door that serves
/// it, the member's grant, and where to start. Each is checked as it is read,
/// and **anything that does not hold refuses the whole request with a closed
/// word**, because a player that guessed at a missing pin would play from a
/// door nobody vouched for.
///
/// **The grant travels in a header and never in an address.** One that is
/// already written into the location is refused rather than used, because an
/// address is what ends up in a log, a cache or a crash report. **A grant is
/// 32 lowercase hexadecimal digits**, as the media server issues one, and it
/// is sent exactly so, as `Authorization: Bearer <grant>`: the door accepts
/// that form and nothing else, and turns it into what the media server reads.
/// The player names no media server's own header, token form or query key.
///
/// Nothing here is ever logged. A refusal is one of the closed words below.
///
/// Deliberately mirrors `WhatToPlay.kt` line for line.
public struct WhatToPlay: Sendable {
    /// Where the title streams from, as the core stated it.
    public let location: String

    /// The door it streams from.
    public let door: Door

    /// The certificate that door promised.
    public let pin: DoorPin

    /// The member's grant, sent with every request and kept nowhere else.
    public let grant: String

    /// The header every request carries the grant in.
    public static let grantHeader = "Authorization"

    /// How the grant is written in it: exactly as it was issued, after one fixed word.
    public var grantHeaderValue: String {
        "Bearer " + grant
    }

    /// Where to start, in seconds from the beginning.
    public let startAt: Double

    /// What the member is shown and heard while it plays.
    public let shown: HowToShowIt

    /// What the member is shown and heard while it plays.
    public struct HowToShowIt: Equatable, Sendable {
        /// What the title is called, for the lock screen and the picture-in-picture window.
        public let title: String

        /// The language to play the sound in, where the member has a preference.
        public let audio: String

        /// The language to show subtitles in, or empty for none.
        public let subtitle: String
    }

    /// The closed words a request is refused with.
    public enum WhyNot: String, Sendable {
        /// The location is not an `https` address at a door.
        case notAtADoor = "not_at_a_door"

        /// The fingerprint is not one.
        case unpinned = "unpinned"

        /// There is no grant, or one a header cannot carry.
        case noGrant = "no_grant"

        /// The grant is written into the address.
        case grantInTheAddress = "grant_in_the_address"

        /// Where to start is not a time.
        case noStartingPoint = "no_starting_point"

        /// What this refusal is called on the wire.
        public var word: String { rawValue }
    }

    /// What became of reading a request.
    public enum Read: Sendable {
        /// It holds, and this is what to play.
        case toPlay(WhatToPlay)

        /// It does not, and this is why.
        case refused(WhyNot)
    }

    private init(
        location: String, door: Door, pin: DoorPin, grant: String, startAt: Double, shown: HowToShowIt
    ) {
        self.location = location
        self.door = door
        self.pin = pin
        self.grant = grant
        self.startAt = startAt
        self.shown = shown
    }

    /// The request the bridge carried, checked.
    ///
    /// - Parameter parameters: what the app sent.
    /// - Returns: what to play, or why not.
    public static func read(_ parameters: [String: Any]) -> Read {
        let location = parameters["location"] as? String ?? ""
        let grant = parameters["grant"] as? String ?? ""

        guard let door = Door.of(location) else {
            return .refused(.notAtADoor)
        }

        guard let pin = DoorPin.of(parameters["fingerprint"] as? String ?? "") else {
            return .refused(.unpinned)
        }

        guard isAGrant(grant) else {
            return .refused(.noGrant)
        }

        guard !location.contains(grant) else {
            return .refused(.grantInTheAddress)
        }

        guard let startAt = seconds(parameters["start_at"]), startAt >= 0 else {
            return .refused(.noStartingPoint)
        }

        return .toPlay(
            WhatToPlay(
                location: location, door: door, pin: pin, grant: grant, startAt: startAt,
                shown: HowToShowIt(
                    title: parameters["title"] as? String ?? "",
                    audio: parameters["audio"] as? String ?? "",
                    subtitle: parameters["subtitle"] as? String ?? "")))
    }

    /// Whether a grant is one as the media server issues it: 32 lowercase hexadecimal digits.
    private static func isAGrant(_ grant: String) -> Bool {
        grant.utf8.count == 32
            && grant.utf8.allSatisfy { (0x30...0x39).contains($0) || (0x61...0x66).contains($0) }
    }

    /// A number of seconds, whichever kind of number the bridge carried it as, or nil.
    private static func seconds(_ value: Any?) -> Double? {
        if let whole = value as? Int {
            return Double(whole)
        }

        return value as? Double
    }
}
