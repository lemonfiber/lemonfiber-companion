import Foundation

/// Artwork held in memory, on iOS.
///
/// Namespace: `Lemonfiber.Artwork.*`
///
/// PHP hands each picture over as base64 under its key and the stack it came
/// from, asks whether a key is still held, and lets go of a stack's pictures
/// when the stack is removed or its session ends. The lock lets go of every
/// picture itself (`TheLock.swift`). No key, stack or byte reaches a log line.
enum ArtworkFunctions {
    private static let held = "in-memory"
    private static let refused = "refused"
    private static let letGo = "let-go"

    class Hold: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            guard
                let key = parameters["key"] as? String,
                let stack = parameters["stack"] as? String,
                let encoded = parameters["bytes"] as? String,
                let bytes = Data(base64Encoded: encoded),
                ArtworkHeld.shared.hold(key: key, stack: stack, bytes: bytes)
            else {
                return Envelope.refusing(refused, because: "not-a-picture").asAnswer()
            }

            ArtworkImages.forget([key])

            return Envelope.of(held).asAnswer()
        }
    }

    class Holds: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            let key = parameters["key"] as? String ?? ""

            return Envelope.of(held, carrying: ["holds": ArtworkHeld.shared.holds(key)]).asAnswer()
        }
    }

    class LetGo: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            let stack = parameters["stack"] as? String ?? ""

            ArtworkImages.forget(ArtworkHeld.shared.letGoOf(stack: stack))

            return Envelope.of(letGo).asAnswer()
        }
    }
}
