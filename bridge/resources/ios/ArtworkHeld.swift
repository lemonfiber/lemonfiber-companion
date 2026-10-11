import Foundation

/// Artwork a screen draws, held in memory for as long as the app runs and nowhere else.
///
/// PHP fetches each picture through the SDK, pinned and on the session it is
/// drawn for, and hands the bytes here under an opaque key. An image element
/// whose source is `lemonfiber-art:<key>` is drawn from what is held. Nothing
/// here writes a file, reaches `URLCache` or survives the process.
///
/// Each picture is held for the stack it came from, so removing the stack or
/// ending its session lets go of that stack's pictures, and the lock lets go of
/// every one. A bound on the bytes held lets go of the oldest first.
///
/// No UIKit in sight, so SwiftPM runs it on a laptop. Decoding is
/// `ArtworkImages.swift`'s. Deliberately mirrors `ArtworkHeld.kt`.
public final class ArtworkHeld: @unchecked Sendable {
    /// What every key's source starts with.
    public static let scheme = "lemonfiber-art:"

    /// The most bytes held at once.
    public static let mostBytes = 48 * 1024 * 1024

    /// The most bytes one picture may be.
    public static let mostBytesOfOne = 4 * 1024 * 1024

    /// The one store the app draws from.
    public static let shared = ArtworkHeld(mostBytes: mostBytes)

    private struct One {
        let stack: String
        let bytes: Data
    }

    private let guarding = NSLock()
    private let bound: Int
    private var held: [String: One] = [:]
    private var order: [String] = []
    private var total = 0

    /// A store holding at most that many bytes.
    public init(mostBytes: Int) {
        bound = mostBytes
    }

    /// Hold a picture under a key for a stack, replacing what the key held.
    ///
    /// Answers whether it is held: a key that is not one, a stack named by
    /// nothing, and bytes that are none or too many are refused.
    @discardableResult
    public func hold(key: String, stack: String, bytes: Data) -> Bool {
        guard Self.isAKey(key), !stack.isEmpty, !bytes.isEmpty, bytes.count <= Self.mostBytesOfOne else {
            return false
        }

        guarding.lock()
        defer { guarding.unlock() }

        drop(key)
        held[key] = One(stack: stack, bytes: bytes)
        order.append(key)
        total += bytes.count

        while total > bound, let oldest = order.first {
            drop(oldest)
        }

        return held[key] != nil
    }

    /// The bytes held under a key, where they are.
    public func bytes(forKey key: String) -> Data? {
        guarding.lock()
        defer { guarding.unlock() }

        return held[key]?.bytes
    }

    /// Whether a key's picture is held.
    public func holds(_ key: String) -> Bool {
        bytes(forKey: key) != nil
    }

    /// Let go of every picture held for a stack, answering the keys let go of.
    @discardableResult
    public func letGoOf(stack: String) -> [String] {
        guarding.lock()
        defer { guarding.unlock() }

        let keys = held.filter { $0.value.stack == stack }.map(\.key)
        keys.forEach(drop)

        return keys
    }

    /// Let go of every picture held, answering the keys let go of.
    @discardableResult
    public func letGoOfEvery() -> [String] {
        guarding.lock()
        defer { guarding.unlock() }

        let keys = Array(held.keys)
        held = [:]
        order = []
        total = 0

        return keys
    }

    /// Whether a source is one this store answers, carrying a key after the scheme.
    public static func isAKey(_ source: String) -> Bool {
        guard source.hasPrefix(scheme) else {
            return false
        }

        let rest = source.dropFirst(scheme.count)

        return (16...64).contains(rest.count) && rest.allSatisfy { $0.isHexDigit && !$0.isUppercase }
    }

    private func drop(_ key: String) {
        guard let one = held.removeValue(forKey: key) else {
            return
        }

        order.removeAll { $0 == key }
        total -= one.bytes.count
    }
}
