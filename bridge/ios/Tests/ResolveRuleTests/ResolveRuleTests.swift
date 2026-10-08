import Testing

@testable import LemonfiberNative

// Which addresses a name resolved to the app may send to.
//
// The same six cases as `ResolveRuleTest.kt`, in the same order.

@Test("a look-up that found nothing says so, with no address")
func nothingFound() {
    #expect(ResolveRule.nothingFound.said == .nothing)
    #expect(ResolveRule.nothingFound.usable == [])
}

@Test("an IPv4 address is found and usable")
func anIPv4AddressIsUsable() {
    let found = ResolveRule([AnAddressFound("192.168.1.42", isVersion4: true, needsAnInterface: false)])

    #expect(found.said == .found)
    #expect(found.usable == ["192.168.1.42"])
}

@Test("IPv4 comes before IPv6, each in the order the platform gave")
func ipv4ComesFirst() {
    let found = ResolveRule([
        AnAddressFound("2001:db8::2", isVersion4: false, needsAnInterface: false),
        AnAddressFound("192.168.1.42", isVersion4: true, needsAnInterface: false),
        AnAddressFound("2001:db8::1", isVersion4: false, needsAnInterface: false),
        AnAddressFound("10.0.0.7", isVersion4: true, needsAnInterface: false),
    ])

    #expect(found.usable == ["192.168.1.42", "10.0.0.7", "2001:db8::2", "2001:db8::1"])
}

@Test("an address that needs its interface is dropped")
func anAddressThatNeedsItsInterfaceIsDropped() {
    let found = ResolveRule([
        AnAddressFound("fe80::1%en0", isVersion4: false, needsAnInterface: true),
        AnAddressFound("192.168.1.42", isVersion4: true, needsAnInterface: false),
    ])

    #expect(found.usable == ["192.168.1.42"])
}

@Test("only unusable addresses are nothing found")
func onlyUnusableIsNothing() {
    let found = ResolveRule([
        AnAddressFound("fe80::1%en0", isVersion4: false, needsAnInterface: true),
        AnAddressFound("", isVersion4: true, needsAnInterface: false),
    ])

    #expect(found.said == .nothing)
    #expect(found.usable == [])
}

@Test("an address given twice is sent to once, and each answer is one word on the wire")
func eachAddressOnceAndEachAnswerOneWord() {
    let found = ResolveRule([
        AnAddressFound("192.168.1.42", isVersion4: true, needsAnInterface: false),
        AnAddressFound("192.168.1.42", isVersion4: true, needsAnInterface: false),
    ])

    #expect(found.usable == ["192.168.1.42"])
    #expect(WhatTheLookupFound.found.word == "found")
    #expect(WhatTheLookupFound.nothing.word == "nothing")
}
