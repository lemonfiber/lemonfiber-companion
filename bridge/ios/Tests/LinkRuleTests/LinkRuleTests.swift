import Testing

@testable import LemonfiberNative

// The distinction whether there is a link turns on.
//
// Not whether a request succeeded — whether *this phone has no network* is told
// apart from *that machine is not answering*. They are two sentences with two
// different remedies, and a socket timing out looks the same in both.
//
// The same six cases as `LinkRuleTest.kt`, in the same order.

@Test("a link that works is something the app may try")
func aWorkingLinkMayBeTried() {
    #expect(LinkRule.connected.said == .reachable)
}

@Test("a device with no active link reaches nothing")
func noActiveLinkReachesNothing() {
    let adrift = LinkRule(linkWasReadable: true, hasAnActiveLink: false, carriesTraffic: false)

    #expect(adrift.said == .unreachable)
}

@Test("a link that carries no traffic reaches nothing")
func aLinkCarryingNothingReachesNothing() {
    // Joined to something that is not a way out: the state a phone is in while
    // it is associating, and the one a restricted interface stays in.
    let joined = LinkRule(linkWasReadable: true, hasAnActiveLink: true, carriesTraffic: false)

    #expect(joined.said == .unreachable)
}

@Test("a platform that cannot be asked is read as a link that works")
func anUnaskablePlatformIsReadAsWorking() {
    // Every machine that is not a handset, and the direction that matters: the
    // app tries and reports what it finds, rather than opening on a screen
    // whose remedy is *turn your wifi on*.
    #expect(LinkRule.unreadable.said == .reachable)
}

@Test("being unable to ask wins over everything else the platform said")
func beingUnableToAskWins() {
    // The combination that proves the order. Both link facts say nothing is
    // reachable, and they are not read at all, because a platform that could
    // not be asked did not supply them.
    let unasked = LinkRule(linkWasReadable: false, hasAnActiveLink: false, carriesTraffic: false)

    #expect(unasked.said == .reachable)
}

@Test("each answer is one word on the wire")
func eachAnswerIsOneWord() {
    #expect(WhetherAnythingIsReachable.reachable.word == "reachable")
    #expect(WhetherAnythingIsReachable.unreachable.word == "unreachable")
}
