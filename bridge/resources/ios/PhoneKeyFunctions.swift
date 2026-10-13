import CryptoKit
import DeviceCheck
import Foundation
import LocalAuthentication
import Security

/// The key that proves this phone's operator to a stack, on iOS.
///
/// Namespace: `Lemonfiber.PhoneKey.*`
///
/// A P-256 key pair made in the Secure Enclave, usable only after the face or fingerprint
/// enrolled when it was made: a new one makes it unusable, and the passcode is no way past
/// it. App Attest vouches that this genuine app placed it there, by attesting the SHA-256 of
/// its public half; App Attest vouches for the app, not for the key. The private half never
/// leaves the enclave: what crosses the bridge is the public half, the attestation and a
/// signature.
///
/// The decisions are `PhoneKeyRule`'s, held on a laptop. **Nothing a caller passed reaches a
/// log line**: not the alias, which names a stack, nor the text signed. The log carries the
/// outcome word, the reason word and a numeric `OSStatus`.
enum PhoneKeyFunctions {
    /// The word for where a key may live.
    private static let can = "can"

    /// The word for a phone that may not keep one.
    private static let cannot = "cannot"

    /// The word for a key that was made.
    private static let made = "made"

    /// The word for a key that was not.
    private static let notMade = "not_made"

    /// The word for a signature that was made.
    private static let signed = "signed"

    /// The word for one that was not.
    private static let notSigned = "not_signed"

    /// What the prompt is captioned with where a caller sends no reason.
    private static let proveIt = "Prove it is you"

    /// The word for a key that is held.
    private static let held = "held"

    /// The word for this phone's name.
    private static let named = "named"

    /// How long the bridge thread waits for App Attest or the operator.
    private static let patience: TimeInterval = 300

    /// Where a key may live on this phone, or why it may not.
    private static func mayLive() -> PhoneKeyRule.Either {
        PhoneKeyRule.mayLive(
            enclave: SecureEnclave.isAvailable && DCAppAttestService.shared.isSupported,
            biometric: LAContext().canEvaluatePolicy(.deviceOwnerAuthenticationWithBiometrics, error: nil)
        )
    }

    /// The query that names the private half under this alias.
    private static func item(_ alias: String) -> [String: Any] {
        [
            kSecClass as String: kSecClassKey,
            kSecAttrApplicationTag as String: Data(alias.utf8),
            kSecAttrKeyType as String: kSecAttrKeyTypeECSECPrimeRandom,
        ]
    }

    /// Throw the key under this alias away, whether or not it was there.
    ///
    /// - Returns: thrown away, never there, or a key that may still be there.
    @discardableResult
    private static func thrownAway(_ alias: String) -> ThrownAway {
        let status = SecItemDelete(item(alias) as CFDictionary)
        let came = PhoneKeyRule.thrownAway(status: status)

        if came == .notForgotten {
            NSLog("Lemonfiber phone key: not thrown away (status %d)", Int(status))
        }

        return came
    }

    /// Make the pair in the Secure Enclave, guarded by the face or fingerprint enrolled now.
    private static func generated(_ alias: String) -> SecKey? {
        guard
            let access = SecAccessControlCreateWithFlags(
                nil,
                kSecAttrAccessibleWhenUnlockedThisDeviceOnly,
                [.privateKeyUsage, .biometryCurrentSet],
                nil
            )
        else {
            return nil
        }

        let attributes: [String: Any] = [
            kSecAttrKeyType as String: kSecAttrKeyTypeECSECPrimeRandom,
            kSecAttrKeySizeInBits as String: 256,
            kSecAttrTokenID as String: kSecAttrTokenIDSecureEnclave,
            kSecPrivateKeyAttrs as String: [
                kSecAttrIsPermanent as String: true,
                kSecAttrApplicationTag as String: Data(alias.utf8),
                kSecAttrAccessControl as String: access,
            ],
        ]

        return SecKeyCreateRandomKey(attributes as CFDictionary, nil)
    }

    /// The public half's `SubjectPublicKeyInfo` DER, or nothing where it will not export.
    private static func publicKeyInfo(of key: SecKey) -> Data? {
        guard
            let half = SecKeyCopyPublicKey(key),
            let point = SecKeyCopyExternalRepresentation(half, nil) as Data?,
            let spelt = PhoneKeyRule.publicKeyInfo(point: [UInt8](point))
        else {
            return nil
        }

        return Data(spelt)
    }

    /// App Attest's attestation that this app asked for this public half to be registered.
    private static func attested(_ publicKeyInfo: Data) -> Data? {
        let waiting = DispatchSemaphore(value: 0)
        let answered = Handed<Data>()
        let service = DCAppAttestService.shared
        let hash = Data(SHA256.hash(data: publicKeyInfo))

        service.generateKey { keyId, failed in
            guard let keyId, failed == nil else {
                waiting.signal()

                return
            }

            service.attestKey(keyId, clientDataHash: hash) { attestation, _ in
                answered.set(attestation)
                waiting.signal()
            }
        }

        _ = waiting.wait(timeout: .now() + patience)

        return answered.get()
    }

    /// One value handed from a callback's queue to the waiting thread.
    private final class Handed<Value>: @unchecked Sendable {
        private let guarding = NSLock()
        private var value: Value?

        func set(_ new: Value?) {
            guarding.lock()
            value = new
            guarding.unlock()
        }

        func get() -> Value? {
            guarding.lock()
            defer { guarding.unlock() }

            return value
        }
    }

    /// A refusal, answered.
    private static func refusing(_ outcome: String, _ because: String) -> [String: Any] {
        NSLog("Lemonfiber phone key: %@, %@", outcome, because)

        return BridgeResponse.success(data: Envelope.refusing(outcome, because: because).asAnswer())
    }

    /// The alias a call named, or a refusal to guess at one.
    private static func alias(in parameters: [String: Any]) throws -> String {
        guard let alias = parameters["alias"] as? String else {
            throw BridgeError.invalidParameters("alias is required")
        }

        return alias
    }

    /// `Lemonfiber.PhoneKey.Where` — where a key may live on this phone, or why it may not.
    ///
    /// Asks nothing of the operator and makes nothing.
    class Where: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            switch mayLive() {
            case .lives(let place):
                return BridgeResponse.success(
                    data: Envelope.of(can, carrying: ["lives": place.word]).asAnswer())
            case .not(let why):
                return refusing(cannot, why.word)
            }
        }
    }

    /// `Lemonfiber.PhoneKey.Make` — make the key under this alias, replacing any there, or say why not.
    ///
    /// Waits on the bridge thread while App Attest answers. A key App Attest will not vouch
    /// for is thrown away: the stack would refuse it.
    class Make: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            let alias = try alias(in: parameters)

            if case .not(let why) = mayLive() {
                return refusing(notMade, why.word)
            }

            if thrownAway(alias) == .notForgotten {
                return refusing(notMade, WhyNoPhoneKey.failed.word)
            }

            guard let key = generated(alias), let spelt = publicKeyInfo(of: key) else {
                thrownAway(alias)

                return refusing(notMade, WhyNoPhoneKey.failed.word)
            }

            guard let attestation = attested(spelt) else {
                thrownAway(alias)

                return refusing(notMade, WhyNoPhoneKey.unattested.word)
            }

            NSLog("Lemonfiber phone key: made, %@", WhereAPhoneKeyLives.enclave.word)

            return BridgeResponse.success(
                data: Envelope.of(
                    made,
                    carrying: [
                        "lives": WhereAPhoneKeyLives.enclave.word,
                        "public_key": spelt.base64EncodedString(),
                        "attestation": attestation.base64EncodedString(),
                    ]
                ).asAnswer()
            )
        }
    }

    /// `Lemonfiber.PhoneKey.Sign` — sign this text with the key under this alias, behind face or fingerprint.
    ///
    /// The Secure Enclave raises the prompt itself, because the key's access control asks
    /// for the face or fingerprint enrolled when it was made, with no passcode to fall back
    /// on. Only a key in the Secure Enclave is looked up. A key that is gone, or that a
    /// new one made unusable, answers `gone`, which the app answers by making another.
    class Sign: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            let alias = try alias(in: parameters)

            guard let text = parameters["text"] as? String else {
                throw BridgeError.invalidParameters("text is required")
            }

            let context = LAContext()

            context.localizedReason = parameters["reason"] as? String ?? proveIt
            context.localizedFallbackTitle = ""

            var asking = item(alias)

            asking[kSecAttrTokenID as String] = kSecAttrTokenIDSecureEnclave
            asking[kSecReturnRef as String] = true
            asking[kSecUseAuthenticationContext as String] = context

            var held: CFTypeRef?
            let found = SecItemCopyMatching(asking as CFDictionary, &held)

            guard found == errSecSuccess, let held, CFGetTypeID(held) == SecKeyGetTypeID() else {
                return refusing(
                    notSigned, PhoneKeyRule.whyNotSigned(domain: NSOSStatusErrorDomain, code: Int(found)).word
                )
            }

            var failed: Unmanaged<CFError>?

            guard
                let signature = SecKeyCreateSignature(
                    held as! SecKey,
                    .ecdsaSignatureMessageX962SHA256,
                    Data(text.utf8) as CFData,
                    &failed
                ) as Data?
            else {
                let error = failed?.takeRetainedValue()
                let why = PhoneKeyRule.whyNotSigned(
                    domain: error.map { CFErrorGetDomain($0) as String } ?? "",
                    code: error.map { CFErrorGetCode($0) } ?? 0
                )

                return refusing(notSigned, why.word)
            }

            NSLog("Lemonfiber phone key: signed")

            return BridgeResponse.success(
                data: Envelope.of(signed, carrying: ["signature": signature.base64EncodedString()]).asAnswer()
            )
        }
    }

    /// `Lemonfiber.PhoneKey.Forget` — throw the key under this alias away.
    ///
    /// A key that was never made answers `none_held` rather than a refusal: removing a stack
    /// from the phone, or a key the stack refused, must always be able to end it. A key the
    /// keychain would not delete is refused as `not_forgotten`, never answered as gone.
    class Forget: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            let came = thrownAway(try alias(in: parameters))

            if came == .notForgotten {
                return refusing(came.word, WhyNoPhoneKey.failed.word)
            }

            NSLog("Lemonfiber phone key: %@", came.word)

            return BridgeResponse.success(data: Envelope.of(came.word).asAnswer())
        }
    }

    /// `Lemonfiber.PhoneKey.Held` — whether the key under this alias is held.
    ///
    /// Asks nothing of the operator: the lookup may not raise the prompt, and a key that would
    /// need it is held all the same.
    class Held: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            let context = LAContext()

            context.interactionNotAllowed = true

            var asking = item(try alias(in: parameters))

            asking[kSecReturnAttributes as String] = true
            asking[kSecUseAuthenticationContext as String] = context

            let found = SecItemCopyMatching(asking as CFDictionary, nil)

            return BridgeResponse.success(
                data: Envelope.of(PhoneKeyRule.held(status: found) ? held : ThrownAway.noneHeld.word)
                    .asAnswer()
            )
        }
    }

    /// `Lemonfiber.PhoneKey.Model` — what this phone is called, for naming its key on a stack.
    ///
    /// The marketing name of the iPhone by its hardware identifier. It is never written to the log.
    class Model: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            var system = utsname()

            uname(&system)

            let identifier = withUnsafeBytes(of: &system.machine) { bytes in
                String(decoding: bytes.prefix { $0 != 0 }, as: UTF8.self)
            }

            return BridgeResponse.success(
                data: Envelope.of(named, carrying: ["model": PhoneKeyRule.modelName(identifier: identifier)])
                    .asAnswer()
            )
        }
    }
}
