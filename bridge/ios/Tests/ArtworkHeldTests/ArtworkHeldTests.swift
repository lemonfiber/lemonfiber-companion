import Foundation
import Testing

@testable import LemonfiberNative

// Artwork held in memory: what is held, for which stack, and what lets go of it.
//
// The same cases as `ArtworkHeldTest.kt`, in the same order.

private let aKey = "lemonfiber-art:0123456789abcdef0123456789abcdef"
private let anotherKey = "lemonfiber-art:fedcba9876543210fedcba9876543210"

private func bytes(_ count: Int) -> Data {
    Data(repeating: 7, count: count)
}

@Test("a picture is held under its key, byte for byte")
func aPictureIsHeld() {
    let store = ArtworkHeld(mostBytes: 1024)

    #expect(store.hold(key: aKey, stack: "attic", bytes: bytes(10)))
    #expect(store.bytes(forKey: aKey) == bytes(10))
    #expect(store.holds(aKey))
    #expect(!store.holds(anotherKey))
}

@Test("a key that is not one, a stack named by nothing, no bytes and too many bytes are refused")
func whatIsRefused() {
    let store = ArtworkHeld(mostBytes: ArtworkHeld.mostBytes)

    #expect(!store.hold(key: "https://example.org/poster.jpg", stack: "attic", bytes: bytes(10)))
    #expect(!store.hold(key: "lemonfiber-art:SHOUTING0123456789", stack: "attic", bytes: bytes(10)))
    #expect(!store.hold(key: "lemonfiber-art:abc", stack: "attic", bytes: bytes(10)))
    #expect(!store.hold(key: aKey, stack: "", bytes: bytes(10)))
    #expect(!store.hold(key: aKey, stack: "attic", bytes: Data()))
    #expect(!store.hold(key: aKey, stack: "attic", bytes: bytes(ArtworkHeld.mostBytesOfOne + 1)))
    #expect(!store.holds(aKey))
}

@Test("letting go of a stack lets go of its pictures and no other's")
func lettingGoOfAStack() {
    let store = ArtworkHeld(mostBytes: 1024)
    store.hold(key: aKey, stack: "attic", bytes: bytes(10))
    store.hold(key: anotherKey, stack: "loft", bytes: bytes(10))

    #expect(store.letGoOf(stack: "attic") == [aKey])
    #expect(!store.holds(aKey))
    #expect(store.holds(anotherKey))
}

@Test("letting go of every picture leaves nothing held")
func lettingGoOfEvery() {
    let store = ArtworkHeld(mostBytes: 1024)
    store.hold(key: aKey, stack: "attic", bytes: bytes(10))
    store.hold(key: anotherKey, stack: "loft", bytes: bytes(10))

    #expect(Set(store.letGoOfEvery()) == [aKey, anotherKey])
    #expect(!store.holds(aKey))
    #expect(!store.holds(anotherKey))
}

@Test("past the bound the oldest picture is let go of first, and a key held again counts once")
func theBoundLetsGoOfTheOldest() {
    let store = ArtworkHeld(mostBytes: 25)
    store.hold(key: aKey, stack: "attic", bytes: bytes(10))
    store.hold(key: aKey, stack: "attic", bytes: bytes(10))
    store.hold(key: anotherKey, stack: "attic", bytes: bytes(10))

    #expect(store.holds(aKey))

    store.hold(key: "lemonfiber-art:00000000000000000000000000000000", stack: "attic", bytes: bytes(10))

    #expect(!store.holds(aKey))
    #expect(store.holds(anotherKey))
}

@Test("the app draws from one store, bounded by the most bytes held at once")
func theAppDrawsFromOneStore() {
    ArtworkHeld.shared.letGoOfEvery()

    #expect(ArtworkHeld.shared.hold(key: aKey, stack: "attic", bytes: bytes(10)))
    #expect(
        !ArtworkHeld.shared.hold(
            key: anotherKey, stack: "attic", bytes: bytes(ArtworkHeld.mostBytesOfOne + 1)))
    #expect(ArtworkHeld.shared.letGoOfEvery() == [aKey])
}
