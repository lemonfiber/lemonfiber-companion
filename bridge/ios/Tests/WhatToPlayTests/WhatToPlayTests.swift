import Testing

@testable import LemonfiberNative

// What the app asked the player to play, read and checked before anything is fetched.
//
// The same cases as `WhatToPlayTest.kt`, in the same order.

private let theFingerprint = "13ee3a6685a324f7ebbfebb922980ba07a74c0e54cf2243b2f199dc81912e9f5"

private func aRequest(_ changed: [String: Any] = [:]) -> [String: Any] {
    [
        "location": "https://door.home:8443/library/1/main.m3u8",
        "fingerprint": theFingerprint,
        "grant": "0123456789abcdef0123456789abcdef",
        "start_at": 0,
        "title": "Arrival",
        "audio": "en",
        "subtitle": "off",
    ].merging(changed) { _, new in new }
}

private func whyNot(_ read: WhatToPlay.Read) -> WhatToPlay.WhyNot? {
    if case .refused(let why) = read {
        return why
    }

    return nil
}

private func toPlay(_ read: WhatToPlay.Read) -> WhatToPlay? {
    if case .toPlay(let asked) = read {
        return asked
    }

    return nil
}

@Test("a request that holds is read whole")
func aRequestThatHoldsIsRead() {
    let asked = toPlay(WhatToPlay.read(aRequest(["start_at": 61.5])))

    #expect(asked?.location == "https://door.home:8443/library/1/main.m3u8")
    #expect(asked?.door.host == "door.home")
    #expect(asked?.grant == "0123456789abcdef0123456789abcdef")
    #expect(asked?.startAt == 61.5)
    #expect(asked?.shown.title == "Arrival")
    #expect(asked?.shown.audio == "en")
    #expect(asked?.shown.subtitle == "off")
}

@Test("a starting point sent as a whole number is read as seconds")
func aWholeNumberIsReadAsSeconds() {
    #expect(toPlay(WhatToPlay.read(aRequest(["start_at": 90])))?.startAt == 90)
}

@Test("a location that is not at a door is refused")
func aLocationNotAtADoorIsRefused() {
    #expect(
        whyNot(WhatToPlay.read(aRequest(["location": "http://door.home/library/1/main.m3u8"]))) == .notAtADoor
    )
    #expect(whyNot(WhatToPlay.read(aRequest(["location": 7]))) == .notAtADoor)
}

@Test("a fingerprint that is not one is refused")
func aFingerprintThatIsNotOneIsRefused() {
    #expect(whyNot(WhatToPlay.read(aRequest(["fingerprint": "abc"]))) == .unpinned)
    #expect(whyNot(WhatToPlay.read(aRequest(["fingerprint": 7]))) == .unpinned)
}

@Test("a grant that is not 32 lowercase hexadecimal digits is refused")
func aGrantNotAsIssuedIsRefused() {
    #expect(whyNot(WhatToPlay.read(aRequest(["grant": ""]))) == .noGrant)
    #expect(whyNot(WhatToPlay.read(aRequest(["grant": 7]))) == .noGrant)
    #expect(whyNot(WhatToPlay.read(aRequest(["grant": "a grant"]))) == .noGrant)
    #expect(whyNot(WhatToPlay.read(aRequest(["grant": "a-grant\r\nX-Other: 1"]))) == .noGrant)
    #expect(whyNot(WhatToPlay.read(aRequest(["grant": "a-grant-é"]))) == .noGrant)
    #expect(whyNot(WhatToPlay.read(aRequest(["grant": "0123456789ABCDEF0123456789ABCDEF"]))) == .noGrant)
    #expect(whyNot(WhatToPlay.read(aRequest(["grant": "0123456789abcdef0123456789abcde"]))) == .noGrant)
    #expect(whyNot(WhatToPlay.read(aRequest(["grant": "0123456789abcdef0123456789abcdef0"]))) == .noGrant)
    #expect(whyNot(WhatToPlay.read(aRequest(["grant": "\"0123456789abcdef0123456789abcde\""]))) == .noGrant)
    #expect(whyNot(WhatToPlay.read(aRequest(["grant": "0123456789abcdef%30123456789abcdef"]))) == .noGrant)
    #expect(whyNot(WhatToPlay.read(aRequest(["grant": "0123456789abcdeg0123456789abcdef"]))) == .noGrant)
}

@Test("the grant is sent exactly as issued, after Bearer, in the Authorization header")
func theGrantIsSentAsIssued() {
    let asked = toPlay(WhatToPlay.read(aRequest([:])))

    #expect(WhatToPlay.grantHeader == "Authorization")
    #expect(asked?.grantHeaderValue == "Bearer 0123456789abcdef0123456789abcdef")
}

@Test("a grant written into the address is refused")
func aGrantInTheAddressIsRefused() {
    let location = "https://door.home:8443/library/1/0123456789abcdef0123456789abcdef/main.m3u8"

    #expect(whyNot(WhatToPlay.read(aRequest(["location": location]))) == .grantInTheAddress)
}

@Test("a starting point that is not a time is refused")
func aStartingPointThatIsNotATimeIsRefused() {
    #expect(whyNot(WhatToPlay.read(aRequest(["start_at": -1]))) == .noStartingPoint)
    #expect(whyNot(WhatToPlay.read(aRequest(["start_at": "soon"]))) == .noStartingPoint)
}

@Test("each refusal is one word on the wire")
func eachRefusalIsOneWord() {
    #expect(WhatToPlay.WhyNot.notAtADoor.word == "not_at_a_door")
    #expect(WhatToPlay.WhyNot.unpinned.word == "unpinned")
    #expect(WhatToPlay.WhyNot.noGrant.word == "no_grant")
    #expect(WhatToPlay.WhyNot.grantInTheAddress.word == "grant_in_the_address")
    #expect(WhatToPlay.WhyNot.noStartingPoint.word == "no_starting_point")
}
