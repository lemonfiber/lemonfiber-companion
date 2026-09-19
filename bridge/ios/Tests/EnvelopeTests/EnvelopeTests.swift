import Testing

@testable import LemonfiberNative

// The shape every answer this bridge gives has.
//
// The same six cases as `EnvelopeTest.kt`, in the same order. What is asked is
// the promise the wire makes rather than any one capability's words: a reason
// never appears without a refusal, a refusal never carries anything, and what a
// call was asked for sits beside the outcome rather than inside it.

@Test("an outcome on its own is the whole answer")
func anOutcomeAlone() {
    let answered = Envelope.of("kept").asAnswer()

    #expect(answered.count == 1)
    #expect(answered["outcome"] as? String == "kept")
}

@Test("a refusal says why and the word is not fixed here")
func aRefusalSaysWhy() {
    // Each capability has its own word for a refusal — a write is refused, a
    // notification is withheld, a scan that found nothing is nothing — and what
    // is fixed is that a reason never appears without one.
    let store = Envelope.refusing("refused", because: "no_store_on_this_device").asAnswer()
    let telling = Envelope.refusing("withheld", because: "not_permitted").asAnswer()

    #expect(store["outcome"] as? String == "refused")
    #expect(store["because"] as? String == "no_store_on_this_device")
    #expect(telling["outcome"] as? String == "withheld")
    #expect(telling["because"] as? String == "not_permitted")
}

@Test("nothing says why unless it refused")
func noReasonWithoutARefusal() {
    // The half a hand-written dictionary gets wrong in the other direction: a
    // success carrying a leftover reason reads to a caller as a refusal that
    // also worked.
    #expect(Envelope.of("kept").asAnswer()["because"] == nil)
    #expect(Envelope.of("read", carrying: ["pending": ["a"]]).asAnswer()["because"] == nil)
}

@Test("what was asked for rides beside the outcome")
func thePayloadRidesBeside() {
    let answered = Envelope.of("read", carrying: ["pending": ["one", "two"]]).asAnswer()

    #expect(answered["outcome"] as? String == "read")
    #expect(answered["pending"] as? [String] == ["one", "two"])
}

@Test("a payload cannot take the outcome's place")
func thePayloadCannotDisplaceTheOutcome() {
    // The envelope's own keys are written after what is carried, so a payload
    // named `outcome` cannot become the one word a caller reads. Not
    // hypothetical: every payload key here is chosen by whoever wrote the
    // handler, and one of them is `value`.
    let answered = Envelope.of("read", carrying: ["outcome": "whatever I like"]).asAnswer()

    #expect(answered["outcome"] as? String == "read")
}

@Test("a refusal carries nothing a caller handed in")
func aRefusalCarriesNothingHandedIn() {
    // Structural rather than asserted: no factory takes a reason and an
    // arbitrary payload together, so a refusal that handed over the value it
    // refused to hand over is not something anybody can write. This is the
    // reading of that, so the day somebody adds a factory that would allow it,
    // it fails here rather than on a handset.
    let refused = Envelope.refusing("refused", because: "store_would_not_open").asAnswer()

    #expect(Set(refused.keys) == ["outcome", "because"])

    // The one exception, and it is an exception by being named here rather than
    // by being a payload. `may_ask_again` is a fact about the refusal — closed,
    // decided by the rule, and the only thing a screen can choose between "try
    // again" and "open Settings" on. Asserted as the whole key set so that a
    // second field smuggled in beside it fails.
    let declined = Envelope.refusing("nothing", because: "not_permitted", mayAskAgain: true).asAnswer()

    #expect(Set(declined.keys) == ["outcome", "because", "may_ask_again"])
    #expect(declined["may_ask_again"] as? Bool == true)
}
