import Foundation

/// Where this phone may keep the key that proves its operator to a stack, and the
/// words for what became of one.
///
/// The decisions `PhoneKeyFunctions` turns on, written apart from it so a laptop with no
/// device in sight can hold them: whether the phone can keep a key at all, how the
/// public half the Secure Enclave hands back is spelt for the stack, and which failed
/// prompt is somebody declining rather than something going wrong.
///
/// Deliberately mirrors `PhoneKeyRule.kt` word for word.
public enum PhoneKeyRule {
    /// `errSecSuccess`.
    public static let success: Int32 = 0

    /// `errSecUserCanceled`.
    public static let userCanceled: Int32 = -128

    /// `errSecAuthFailed`.
    public static let authFailed: Int32 = -25293

    /// `errSecItemNotFound`.
    public static let itemNotFound: Int32 = -25300

    /// `LAErrorDomain`, where the face or fingerprint prompt reports how it ended.
    public static let promptDomain = "com.apple.LocalAuthentication"

    /// `LAError.userCancel`.
    public static let promptUserCancel = -2

    /// `LAError.systemCancel`: the system took the prompt down.
    public static let promptSystemCancel = -4

    /// `LAError.appCancel`.
    public static let promptAppCancel = -9

    /// The DER that comes before a P-256 point in its `SubjectPublicKeyInfo`: a sequence holding
    /// the EC public key and prime256v1 identifiers, and a bit string of 66 bytes.
    public static let spkiHeader: [UInt8] = [
        0x30, 0x59, 0x30, 0x13, 0x06, 0x07, 0x2A, 0x86, 0x48, 0xCE, 0x3D, 0x02, 0x01,
        0x06, 0x08, 0x2A, 0x86, 0x48, 0xCE, 0x3D, 0x03, 0x01, 0x07, 0x03, 0x42, 0x00,
    ]

    /// The bytes of an uncompressed P-256 point, as the Secure Enclave exports one.
    public static let pointLength = 65

    /// Where a key may live on a phone that answers these, or why it may not.
    ///
    /// A face or fingerprint is required before anything else, because a key no biometric
    /// guards is a key this app does not make: the passcode is not a way past it.
    ///
    /// - Parameters:
    ///   - enclave: whether the phone has a Secure Enclave and App Attest answers.
    ///   - biometric: whether a face or fingerprint is enrolled.
    /// - Returns: where it may live, or why it may not.
    public static func mayLive(enclave: Bool, biometric: Bool) -> Either {
        if !enclave {
            return .not(.noHardware)
        }

        return biometric ? .lives(.enclave) : .not(.noBiometric)
    }

    /// The public half as the stack reads it, from the point the Secure Enclave exports.
    ///
    /// - Parameter point: the exported point.
    /// - Returns: its `SubjectPublicKeyInfo` DER, or nothing where it is not an uncompressed P-256 point.
    public static func publicKeyInfo(point: [UInt8]) -> [UInt8]? {
        guard point.count == pointLength, point.first == 0x04 else {
            return nil
        }

        return spkiHeader + point
    }

    /// Why a signature that failed with this error was not made.
    ///
    /// Read in the error's own domain: the prompt and the keychain number their codes apart,
    /// and the same number means something else in each.
    ///
    /// - Parameters:
    ///   - domain: the error's domain.
    ///   - code: its code there.
    /// - Returns: somebody declining, a key that is gone, or a failure.
    public static func whyNotSigned(domain: String, code: Int) -> WhyNothingWasSigned {
        switch (domain, code) {
        case (promptDomain, promptUserCancel), (promptDomain, promptSystemCancel),
            (promptDomain, promptAppCancel),
            (NSOSStatusErrorDomain, Int(userCanceled)):
            .cancelled
        case (NSOSStatusErrorDomain, Int(itemNotFound)):
            .gone
        default:
            .failed
        }
    }

    /// What this phone is called where the hardware identifier names no model this table knows.
    public static let anyIPhone = "iPhone"

    /// The marketing name of each iPhone by its hardware identifier.
    public static let models: [String: String] = [
        "iPhone10,1": "iPhone 8", "iPhone10,4": "iPhone 8",
        "iPhone10,2": "iPhone 8 Plus", "iPhone10,5": "iPhone 8 Plus",
        "iPhone10,3": "iPhone X", "iPhone10,6": "iPhone X",
        "iPhone11,2": "iPhone XS", "iPhone11,4": "iPhone XS Max", "iPhone11,6": "iPhone XS Max",
        "iPhone11,8": "iPhone XR",
        "iPhone12,1": "iPhone 11", "iPhone12,3": "iPhone 11 Pro", "iPhone12,5": "iPhone 11 Pro Max",
        "iPhone12,8": "iPhone SE",
        "iPhone13,1": "iPhone 12 mini", "iPhone13,2": "iPhone 12",
        "iPhone13,3": "iPhone 12 Pro", "iPhone13,4": "iPhone 12 Pro Max",
        "iPhone14,4": "iPhone 13 mini", "iPhone14,5": "iPhone 13",
        "iPhone14,2": "iPhone 13 Pro", "iPhone14,3": "iPhone 13 Pro Max",
        "iPhone14,6": "iPhone SE",
        "iPhone14,7": "iPhone 14", "iPhone14,8": "iPhone 14 Plus",
        "iPhone15,2": "iPhone 14 Pro", "iPhone15,3": "iPhone 14 Pro Max",
        "iPhone15,4": "iPhone 15", "iPhone15,5": "iPhone 15 Plus",
        "iPhone16,1": "iPhone 15 Pro", "iPhone16,2": "iPhone 15 Pro Max",
        "iPhone17,3": "iPhone 16", "iPhone17,4": "iPhone 16 Plus",
        "iPhone17,1": "iPhone 16 Pro", "iPhone17,2": "iPhone 16 Pro Max", "iPhone17,5": "iPhone 16e",
        "iPhone18,3": "iPhone 17", "iPhone18,4": "iPhone Air",
        "iPhone18,1": "iPhone 17 Pro", "iPhone18,2": "iPhone 17 Pro Max",
    ]

    /// What this phone is called, by its hardware identifier.
    ///
    /// - Parameter identifier: the identifier `uname` reports, such as `iPhone13,1`.
    /// - Returns: the iPhone's marketing name, or `iPhone` where the table names none.
    public static func modelName(identifier: String) -> String {
        models[identifier] ?? anyIPhone
    }

    /// Whether the keychain's answer to a lookup says a key is held.
    ///
    /// A key that needs the operator before it can be read is held; any answer but "not found"
    /// is read as held, so nothing is ever thought gone that may not be.
    ///
    /// - Parameter status: the `OSStatus` of the lookup.
    /// - Returns: whether a key may be held.
    public static func held(status: Int32) -> Bool {
        status != itemNotFound
    }

    /// What deleting a key came to, by the status the keychain answered.
    ///
    /// - Parameter status: the `OSStatus` of the deletion.
    /// - Returns: thrown away, never there, or a key that may still be there.
    public static func thrownAway(status: Int32) -> ThrownAway {
        switch status {
        case success: .forgotten
        case itemNotFound: .noneHeld
        default: .notForgotten
        }
    }

    /// Where a key may live, or why it may not.
    public enum Either: Equatable {
        /// It may live there.
        case lives(WhereAPhoneKeyLives)

        /// It may not live anywhere on this phone, for this reason.
        case not(WhyNoPhoneKey)
    }
}

/// Where a phone key lives, in the word the stack records it under.
public enum WhereAPhoneKeyLives: String, CaseIterable {
    /// The Secure Enclave.
    case enclave

    /// What it is called on the wire.
    public var word: String { rawValue }
}

/// Why no phone key could be made, each answered differently on the screen.
public enum WhyNoPhoneKey: String, CaseIterable {
    /// The phone has nowhere in hardware to keep one, or App Attest will not answer.
    case noHardware = "no_hardware"

    /// No face or fingerprint is set up, so nothing could guard one.
    case noBiometric = "no_biometric"

    /// The phone made it in software, so it was thrown away.
    case inSoftware = "in_software"

    /// The phone would not attest where it made it.
    case unattested

    /// Something else went wrong; trying again is reasonable.
    case failed

    /// What it is called on the wire.
    public var word: String { rawValue }
}

/// Why nothing was signed.
public enum WhyNothingWasSigned: String, CaseIterable {
    /// The operator declined the prompt.
    case cancelled

    /// The key is gone, or a new face or fingerprint made it unusable.
    case gone

    /// Something else went wrong; trying again is reasonable.
    case failed

    /// What it is called on the wire.
    public var word: String { rawValue }
}

/// What throwing a key away came to.
public enum ThrownAway: String, CaseIterable {
    /// It was there, and it is gone.
    case forgotten

    /// There was none to throw away.
    case noneHeld = "none_held"

    /// It could not be thrown away, so it may still be there.
    case notForgotten = "not_forgotten"

    /// What it is called on the wire.
    public var word: String { rawValue }
}
