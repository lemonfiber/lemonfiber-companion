package app.lemonfiber.native

import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertFalse

/**
 * The shape every answer this bridge gives has.
 *
 * The same six cases as `EnvelopeTests.swift`, in the same order. What is
 * asked is the promise the wire makes rather than any one capability's words: a
 * reason never appears without a refusal, a refusal never carries anything, and
 * what a call was asked for sits beside the outcome rather than inside it.
 */
class EnvelopeTest {
    @Test
    fun `an outcome on its own is the whole answer`() {
        assertEquals(mapOf("outcome" to "kept"), Envelope.of("kept").asAnswer())
    }

    @Test
    fun `a refusal says why and the word is not fixed here`() {
        // Each capability has its own word for a refusal — a write is refused,
        // a notification is withheld, a scan that found nothing is nothing —
        // and what is fixed is that a reason never appears without one.
        assertEquals(
            mapOf("outcome" to "refused", "because" to "no_store_on_this_device"),
            Envelope.refusing("refused", because = "no_store_on_this_device").asAnswer(),
        )
        assertEquals(
            mapOf("outcome" to "withheld", "because" to "not_permitted"),
            Envelope.refusing("withheld", because = "not_permitted").asAnswer(),
        )
    }

    @Test
    fun `nothing says why unless it refused`() {
        // The half a hand-written map gets wrong in the other direction: a
        // success carrying a leftover reason reads to a caller as a refusal
        // that also worked.
        assertFalse(Envelope.of("kept").asAnswer().containsKey("because"))
        assertFalse(
            Envelope.of("read", carrying = mapOf("pending" to listOf("a")))
                .asAnswer()
                .containsKey("because"),
        )
    }

    @Test
    fun `what was asked for rides beside the outcome`() {
        val answered = Envelope.of("read", carrying = mapOf("pending" to listOf("one", "two"))).asAnswer()

        assertEquals("read", answered["outcome"])
        assertEquals(listOf("one", "two"), answered["pending"])
    }

    @Test
    fun `a payload cannot take the outcome's place`() {
        // The envelope's own keys are written after what is carried, so a
        // payload named `outcome` cannot become the one word a caller reads.
        // Not hypothetical: every payload key here is chosen by whoever wrote
        // the handler, and one of them is `value`.
        val answered = Envelope.of("read", carrying = mapOf("outcome" to "whatever I like")).asAnswer()

        assertEquals("read", answered["outcome"])
    }

    @Test
    fun `a refusal carries nothing a caller handed in`() {
        // Structural rather than asserted: no factory takes a reason and an
        // arbitrary payload together, so a refusal that handed over the value it
        // refused to hand over is not something anybody can write. This is the
        // reading of that, so the day somebody adds a factory that would allow
        // it, it fails here rather than on a handset.
        val refused = Envelope.refusing("refused", because = "store_would_not_open").asAnswer()

        assertEquals(setOf("outcome", "because"), refused.keys)

        // The one exception, and it is an exception by being named here rather
        // than by being a payload. `may_ask_again` is a fact about the refusal —
        // closed, decided by the rule, and the only thing a screen can choose
        // between "try again" and "open Settings" on. Asserted as the whole key
        // set so that a second field smuggled in beside it fails.
        val declined = Envelope.refusing("nothing", because = "not_permitted", mayAskAgain = true).asAnswer()

        assertEquals(setOf("outcome", "because", "may_ask_again"), declined.keys)
        assertEquals(true, declined["may_ask_again"])
    }
}
