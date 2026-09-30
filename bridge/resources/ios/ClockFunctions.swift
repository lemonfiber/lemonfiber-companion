import Foundation

/// Which time zone the phone's clock is set to, on iOS.
///
/// Namespace: `Lemonfiber.Clock.*`
///
/// One question with one answer the platform always has: the zone's name in the
/// time zone database, such as `Europe/Amsterdam`. PHP on a phone is not told
/// it, and a time shown to somebody holding the phone is owed their own clock.
///
/// The name is read now rather than once at launch, so a phone carried across a
/// border answers with where it is.
enum ClockFunctions {
    private static let tag = "Lemonfiber"

    class Zone: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            let zone = TimeZone.current.identifier

            NSLog("%@ clock: zone read", tag)

            return Envelope.of("known", carrying: ["zone": zone]).asAnswer()
        }
    }
}
