import CryptoKit
import Foundation

/// The certificate the household's front door promised to present, and whether one presented is it.
///
/// The door serves the household's library, and the core states its address
/// beside the fingerprint of the certificate it presents. Every byte the player
/// fetches — a manifest, a segment, a subtitle, a key — comes from a connection
/// whose leaf certificate hashes to that fingerprint, the same way the stack
/// itself is pinned. The platform's trust store is not asked and the hostname
/// is not read: the door is often an address on the house's own network, which
/// no public authority vouches for, and a pin is a stronger promise than either.
///
/// **A fingerprint that is not one admits nothing.** Sixty-four hexadecimal
/// characters are a SHA-256 digest; anything else — short, long, a colon in
/// it, a stray space — is refused when the pin is made, so there is no pin
/// that a malformed answer from the core could turn into one that admits every
/// certificate.
///
/// The comparison does not stop at the first difference, so how long it takes
/// says nothing about how much of a certificate matched.
///
/// Deliberately mirrors `DoorPin.kt` line for line.
public struct DoorPin: Sendable {
    /// How many characters a fingerprint has: a SHA-256 digest, in hexadecimal.
    public static let characters = 64

    /// The digest the presented leaf must hash to.
    private let expected: [UInt8]

    /// The pin for this fingerprint, or nil where it is not one.
    ///
    /// Either case of hexadecimal is read, because a digest is the same digest
    /// in both. Nothing else is forgiven.
    ///
    /// - Parameter fingerprint: the fingerprint the core stated for the door.
    /// - Returns: the pin, or nil.
    public static func of(_ fingerprint: String) -> DoorPin? {
        let nibbles = fingerprint.utf8.compactMap(nibble)

        guard fingerprint.utf8.count == characters, nibbles.count == characters else {
            return nil
        }

        return DoorPin(
            expected: stride(from: 0, to: characters, by: 2).map { nibbles[$0] << 4 | nibbles[$0 + 1] })
    }

    /// Whether this is the certificate the door promised.
    ///
    /// - Parameter leaf: the presented leaf certificate, DER-encoded.
    /// - Returns: whether its digest is the pinned one.
    public func admits(leaf: Data) -> Bool {
        let presented = Array(SHA256.hash(data: leaf))
        var difference: UInt8 = 0

        for (one, other) in zip(presented, expected) {
            difference |= one ^ other
        }

        return difference == 0
    }

    /// The value of one hexadecimal character, or nil where it is not one.
    private static func nibble(_ character: UInt8) -> UInt8? {
        switch character {
        case UInt8(ascii: "0")...UInt8(ascii: "9"):
            return character - UInt8(ascii: "0")
        case UInt8(ascii: "a")...UInt8(ascii: "f"):
            return character - UInt8(ascii: "a") + 10
        case UInt8(ascii: "A")...UInt8(ascii: "F"):
            return character - UInt8(ascii: "A") + 10
        default:
            return nil
        }
    }
}
