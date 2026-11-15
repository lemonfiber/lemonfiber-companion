import Foundation

/// Being woken through a UnifiedPush distributor, which an iPhone cannot be.
///
/// Namespace: `Lemonfiber.Waking.*`
///
/// Each function answers the same question its Android half does, with the
/// answer for a phone that cannot be woken this way: no distributor is listed,
/// no endpoint is given, and there is nothing to let go of.
enum WakingFunctions {
    /// `Lemonfiber.Waking.Distributors` — this phone cannot be woken through one.
    class Distributors: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            Envelope.of("not_here").asAnswer()
        }
    }

    /// `Lemonfiber.Waking.Register` — no endpoint is given here.
    class Register: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            Envelope.refusing("not_given", because: "not_here").asAnswer()
        }
    }

    /// `Lemonfiber.Waking.Endpoint` — no distributor gives one here.
    class Endpoint: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            Envelope.refusing("not_given", because: "not_here").asAnswer()
        }
    }

    /// `Lemonfiber.Waking.LetGo` — nothing is held here to let go of.
    class LetGo: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            Envelope.of("let_go").asAnswer()
        }
    }

    /// `Lemonfiber.Waking.LetGoOfEvery` — nothing is held here to let go of.
    class LetGoOfEvery: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            Envelope.of("let_go").asAnswer()
        }
    }
}
