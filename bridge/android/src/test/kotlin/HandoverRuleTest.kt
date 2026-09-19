package app.lemonfiber.native

import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertFalse
import kotlin.test.assertNull
import kotlin.test.assertTrue

/**
 * The distinction handing something over turns on.
 *
 * Not whether the operator sent the report — this cannot know that and must not
 * — but whether the sheet was ever put in front of them, and why not where it
 * was not. The two refusals have different remedies and arrive as the same
 * nothing.
 *
 * The same five cases as `HandoverRuleTests.swift`, in the same order.
 */
class HandoverRuleTest {
    @Test
    fun `a report that is there and a sheet that opened refuses nothing`() {
        assertTrue(HandoverRule.OFFERED.wasOffered)
        assertNull(HandoverRule.OFFERED.whyNot)
    }

    @Test
    fun `nothing to hand over is not a platform that would not`() {
        // Both false, which is the combination that proves the order. A rule
        // asking about the sheet first would send somebody to try again at a
        // thing that fails the same way every time.
        val missing = HandoverRule(thereIsSomethingToHandOver = false, theSheetWasPresented = false)

        assertFalse(missing.wasOffered)
        assertEquals(WhyNothingWasHandedOver.NOTHING_TO_HAND_OVER, missing.whyNot)
    }

    @Test
    fun `a report that is there and a sheet that would not open says so`() {
        val shut = HandoverRule(thereIsSomethingToHandOver = true, theSheetWasPresented = false)

        assertFalse(shut.wasOffered)
        assertEquals(WhyNothingWasHandedOver.THE_PLATFORM_WOULD_NOT, shut.whyNot)
    }

    @Test
    fun `a missing report is refused even where the sheet would have opened`() {
        val missing = HandoverRule(thereIsSomethingToHandOver = false, theSheetWasPresented = true)

        assertFalse(missing.wasOffered)
        assertEquals(WhyNothingWasHandedOver.NOTHING_TO_HAND_OVER, missing.whyNot)
    }

    @Test
    fun `each refusal is one word on the wire`() {
        assertEquals("nothing_to_hand_over", WhyNothingWasHandedOver.NOTHING_TO_HAND_OVER.word)
        assertEquals("the_platform_would_not", WhyNothingWasHandedOver.THE_PLATFORM_WOULD_NOT.word)
    }
}
