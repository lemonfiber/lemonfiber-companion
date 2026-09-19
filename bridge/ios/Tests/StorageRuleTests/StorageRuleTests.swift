import Testing

@testable import LemonfiberNative

// The distinction keeping a secret turns on.
//
// Not whether a write succeeded — whether a device that cannot be asked is told
// apart from a device that has nothing to tell. They are opposite answers and
// they arrive as the same emptiness, which is why the facts are carried rather
// than inferred from a failure.
//
// The same six cases as `StorageRuleTest.kt`, in the same order.

@Test("a store that exists and opens refuses nothing")
func aWorkingStoreRefusesNothing() {
    #expect(StorageRule.working.mayProceed)
    #expect(StorageRule.working.whyNot == nil)
}

@Test("no store on the device is not a store that would not open")
func noStoreIsNotAShutStore() {
    // Both false, which is the combination that proves the order. A rule asking
    // about opening first would tell somebody with no store at all to try
    // again, for ever.
    let absent = StorageRule(storeExists: false, storeOpened: false)

    #expect(!absent.mayProceed)
    #expect(absent.whyNot == .noStoreOnThisDevice)
}

@Test("a store that would not open is worth trying again")
func aShutStoreIsWorthRetrying() {
    let shut = StorageRule(storeExists: true, storeOpened: false)

    #expect(!shut.mayProceed)
    #expect(shut.whyNot == .storeWouldNotOpen)
}

@Test("each refusal has its own word on the wire")
func everyRefusalHasAWord() {
    #expect(WhyNothingWasKept.noStoreOnThisDevice.word == "no_store_on_this_device")
    #expect(WhyNothingWasKept.storeWouldNotOpen.word == "store_would_not_open")
}

@Test("each moment a value may be read has its own word")
func everyMomentHasAWord() {
    #expect(WhenAValueMayBeRead.whileUnlocked.word == "while_unlocked")
    #expect(WhenAValueMayBeRead.afterFirstUnlock.word == "after_first_unlock")
    #expect(WhenAValueMayBeRead.readFrom("while_unlocked") == .whileUnlocked)
    #expect(WhenAValueMayBeRead.readFrom("after_first_unlock") == .afterFirstUnlock)
}

@Test("a moment nobody named is the narrowest one")
func anUnnamedMomentIsTheNarrowest() {
    // The direction that matters. A word this does not know comes from a caller
    // newer than the shim, and reading it as the widest choice is how a session
    // becomes readable on a locked phone because of a typo.
    #expect(WhenAValueMayBeRead.readFrom(nil) == .whileUnlocked)
    #expect(WhenAValueMayBeRead.readFrom("") == .whileUnlocked)
    #expect(WhenAValueMayBeRead.readFrom("whenever_you_like") == .whileUnlocked)
}
