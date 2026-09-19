import Foundation
import Security

/// Keeping a secret, on iOS.
///
/// Namespace: `Lemonfiber.Storage.*`
///
/// Every session this application holds and every stack it is paired with lives
/// in the Keychain and nowhere else — not in `UserDefaults`, not in a file, not
/// in an unencrypted backup. Those alternatives are absent from this file
/// rather than guarded against, which is the only way to be sure: a fallback
/// written for the device that has no store is the line that writes a token to
/// a file.
///
/// The decision worth testing is not here. `StorageRule` holds the two
/// distinctions a caller cannot make for itself — a device with no store told
/// from a store that would not open, and a key that is not there told from a
/// store that cannot be asked — and it runs on a laptop with no device in
/// sight.
///
/// **Nothing secret reaches a log line, a breadcrumb or a cache file.** Not the
/// key, not the value, not a stack's identifier. The log lines here carry the
/// outcome word, the reason word and a numeric `OSStatus`, none of which is
/// derived from anything a caller passed in.
///
/// **Every platform failure becomes a word.** `SecCopyErrorMessageString` can
/// carry the item it failed on, so it is never called; the status code is
/// reported as a number and nothing else.
enum StorageFunctions {
    /// What this application's Keychain items are filed under.
    private static let service = "app.lemonfiber.companion"

    /// The word for a value that was written.
    private static let kept = "kept"

    /// The word for a value that was read.
    private static let found = "found"

    /// The word for a key the store does not hold.
    private static let nothing = "nothing"

    /// The word for a value that was taken out, or was never in.
    private static let forgotten = "forgotten"

    /// The word every refusal this capability makes is called.
    private static let refused = "refused"

    /// The query that names one item, without saying anything about its value.
    ///
    /// - Parameter key: the caller's name for the value.
    /// - Returns: the attributes that identify exactly that item.
    private static func item(_ key: String) -> [String: Any] {
        [
            kSecClass as String: kSecClassGenericPassword,
            kSecAttrService as String: service,
            kSecAttrAccount as String: key,
        ]
    }

    /// What the platform's own constant is for one of our choices.
    ///
    /// Both are `ThisDeviceOnly`, which is not one of the choices on the wire
    /// because it is not a choice: an item that syncs to iCloud is a copy of a
    /// session in somebody else's datacentre, and this application has nowhere
    /// that would be acceptable. The wire carries the part that is genuinely a
    /// decision and this pins the part that is not.
    ///
    /// - Parameter when: the moment the caller asked for.
    /// - Returns: the Keychain accessibility constant for it.
    private static func accessibility(_ when: WhenAValueMayBeRead) -> CFString {
        switch when {
        case .whileUnlocked: kSecAttrAccessibleWhenUnlockedThisDeviceOnly
        case .afterFirstUnlock: kSecAttrAccessibleAfterFirstUnlockThisDeviceOnly
        }
    }

    /// What one `OSStatus` means in the terms the rule reads.
    ///
    /// `errSecNotAvailable` is the Keychain being unreachable — no store to
    /// talk to — and everything else that is not success is a store that is
    /// there and would not do as it was asked. The two are the refusals a
    /// screen says different sentences about.
    ///
    /// - Parameter status: what the platform answered.
    /// - Returns: the facts `StorageRule` is built from.
    private static func rule(after status: OSStatus) -> StorageRule {
        StorageRule(storeExists: status != errSecNotAvailable, storeOpened: false)
    }

    /// The refusal for a store that would not do as it was asked.
    ///
    /// The reason comes off the rule rather than from the status directly, so
    /// that the word a caller reads and the word the rule decided are the same
    /// word by construction.
    ///
    /// - Parameters:
    ///   - status: what the platform answered.
    ///   - doing: which operation was being attempted, as a closed word.
    /// - Returns: the answer, ready to hand back.
    private static func refusing(_ status: OSStatus, doing: String) -> [String: Any] {
        let why = rule(after: status).whyNot ?? .storeWouldNotOpen

        NSLog("Lemonfiber storage: %@ refused, %@ (status %d)", doing, why.word, Int(status))

        return Envelope.refusing(refused, because: why.word).asAnswer()
    }

    /// Write one value, replacing whatever was under that key.
    ///
    /// Deleted and added rather than updated, because re-keeping an existing
    /// item with a different accessibility has to migrate it in place — and
    /// `SecItemUpdate` will not change `kSecAttrAccessible` on every version.
    /// The delete is unchecked on purpose: a key that was not there is the
    /// ordinary case, and its absence is not a reason to refuse a write.
    ///
    /// - Parameters:
    ///   - key: the caller's name for the value.
    ///   - value: what to keep.
    ///   - when: the moment it may be read again.
    /// - Returns: the answer, ready to hand back.
    private static func write(key: String, value: String, when: WhenAValueMayBeRead) -> [String: Any] {
        SecItemDelete(item(key) as CFDictionary)

        var adding = item(key)

        adding[kSecValueData as String] = Data(value.utf8)
        adding[kSecAttrAccessible as String] = accessibility(when)

        let status = SecItemAdd(adding as CFDictionary, nil)

        guard status == errSecSuccess else {
            return refusing(status, doing: kept)
        }

        NSLog("Lemonfiber storage: kept, readable %@", when.word)

        return Envelope.of(kept, carrying: ["readable": when.word]).asAnswer()
    }

    /// `Lemonfiber.Storage.Keep` — write one value, or say why not.
    class Keep: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            guard
                let key = parameters["key"] as? String,
                let value = parameters["value"] as? String
            else {
                throw BridgeError.invalidParameters("key and value are required")
            }

            let when = WhenAValueMayBeRead.readFrom(parameters["readable"] as? String)

            return BridgeResponse.success(data: write(key: key, value: value, when: when))
        }
    }

    /// `Lemonfiber.Storage.Read` — read one value, or say there is none.
    ///
    /// *There is none* and *nobody could be asked* are different answers and
    /// this is the function where the difference costs the most: a launch
    /// reading a refusal as an empty store offers to pair a machine that is
    /// already paired.
    class Read: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            guard let key = parameters["key"] as? String else {
                throw BridgeError.invalidParameters("key is required")
            }

            var asking = item(key)

            asking[kSecReturnData as String] = true
            asking[kSecMatchLimit as String] = kSecMatchLimitOne

            var held: CFTypeRef?
            let status = SecItemCopyMatching(asking as CFDictionary, &held)

            if status == errSecItemNotFound {
                NSLog("Lemonfiber storage: nothing under that key")

                return BridgeResponse.success(data: Envelope.of(nothing).asAnswer())
            }

            guard status == errSecSuccess, let data = held as? Data else {
                return BridgeResponse.success(data: refusing(status, doing: found))
            }

            NSLog("Lemonfiber storage: found")

            let value = String(decoding: data, as: UTF8.self)

            return BridgeResponse.success(data: Envelope.of(found, carrying: ["value": value]).asAnswer())
        }
    }

    /// `Lemonfiber.Storage.Forget` — take one value out.
    ///
    /// Forgetting a key that was never kept is `forgotten` rather than an
    /// error. It is the ordinary case after a refused write, and getting rid of
    /// a session is the one operation that must always work — including on the
    /// device where keeping it did not.
    class Forget: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            guard let key = parameters["key"] as? String else {
                throw BridgeError.invalidParameters("key is required")
            }

            let status = SecItemDelete(item(key) as CFDictionary)

            guard status == errSecSuccess || status == errSecItemNotFound else {
                return BridgeResponse.success(data: refusing(status, doing: forgotten))
            }

            NSLog("Lemonfiber storage: forgotten")

            return BridgeResponse.success(data: Envelope.of(forgotten).asAnswer())
        }
    }
}
