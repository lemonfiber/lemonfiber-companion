import Testing

@testable import LemonfiberNative

// The same cases as `ReorderingTest.kt`, in the same order.

private let three = ["loft", "attic", "shed"]

@Test("moves a row down the list")
func movesARowDown() {
    #expect(Reordering.moved(three, from: 0, to: 2) == ["attic", "shed", "loft"])
}

@Test("moves a row up the list")
func movesARowUp() {
    #expect(Reordering.moved(three, from: 2, to: 0) == ["shed", "loft", "attic"])
}

@Test("moves nothing to its own place or outside the list")
func movesNothingOutside() {
    #expect(Reordering.moved(three, from: 1, to: 1) == three)
    #expect(Reordering.moved(three, from: -1, to: 1) == three)
    #expect(Reordering.moved(three, from: 1, to: 3) == three)
}

@Test("lands on the nearest place")
func landsOnTheNearestPlace() {
    #expect(Reordering.landing(from: 0, offset: 60, rowHeight: 100, count: 3) == 1)
    #expect(Reordering.landing(from: 0, offset: 40, rowHeight: 100, count: 3) == 0)
    #expect(Reordering.landing(from: 2, offset: -180, rowHeight: 100, count: 3) == 0)
}

@Test("lands never past either end")
func landsNeverPastEitherEnd() {
    #expect(Reordering.landing(from: 1, offset: 900, rowHeight: 100, count: 3) == 2)
    #expect(Reordering.landing(from: 1, offset: -900, rowHeight: 100, count: 3) == 0)
}

@Test("lands where it was with no rows to measure by")
func landsWhereItWas() {
    #expect(Reordering.landing(from: 1, offset: 60, rowHeight: 0, count: 3) == 1)
    #expect(Reordering.landing(from: 1, offset: 60, rowHeight: 100, count: 0) == 1)
}

@Test("sends the keys on a line each")
func sendsTheKeys() {
    #expect(Reordering.sent(three) == "loft\nattic\nshed")
}
