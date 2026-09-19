package app.lemonfiber.native

import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertFalse
import kotlin.test.assertNull
import kotlin.test.assertTrue

/**
 * What reading a code adds to the answer every permission gives.
 *
 * The three states and the order they are read in belong to
 * `WhatTheOperatorSaidTest`, which is shared with telling somebody. What is
 * asked here is the part that is this capability's: that a missing lens is told
 * from a refused one, and that a refusal the platform would still reconsider is
 * told from a settled one — which are two different sentences on a screen and
 * the reason this rule exists at all.
 *
 * The same seven cases as `CameraRuleTests.swift`, in the same order.
 */
class CameraRuleTest {
    @Test
    fun `a granted camera may be opened without asking anybody`() {
        val allowed = CameraRule.UNASKED.copy(permissionIsGranted = true)

        assertEquals(WhatTheOperatorSaid.GRANTED, allowed.said)
        assertTrue(allowed.mayOpen)
        assertFalse(allowed.mayAsk)
        assertFalse(allowed.mayAskAgain)
        assertNull(allowed.whyNot)
    }

    @Test
    fun `no camera beats a permission that was granted`() {
        // Granted *and* absent, which is the combination that proves the lens is
        // read first. A rule that asked about the permission first would report
        // a device with no camera as ready to scan.
        val absent = CameraRule.UNASKED.copy(cameraExists = false, permissionIsGranted = true)

        assertFalse(absent.mayOpen)
        assertFalse(absent.mayAsk)
        assertFalse(absent.mayAskAgain)
        assertEquals(WhyNothingWasRead.THERE_IS_NO_CAMERA, absent.whyNot)
    }

    @Test
    fun `nobody asked is a question still open rather than a refusal`() {
        // The one state that must answer "ask me". Without it the rule could
        // report a refusal unconditionally, every other case here would still
        // pass, and a scanner that never asks anybody anything would ship.
        assertEquals(WhatTheOperatorSaid.NOT_DETERMINED, CameraRule.UNASKED.said)
        assertTrue(CameraRule.UNASKED.mayAsk)
        assertTrue(CameraRule.UNASKED.mayAskAgain)
        assertFalse(CameraRule.UNASKED.mayOpen)
        assertNull(CameraRule.UNASKED.whyNot)
    }

    @Test
    fun `a refusal the platform would still explain can be put again`() {
        val declined = CameraRule.UNASKED.copy(wouldExplain = true, everAsked = true)

        assertEquals(WhatTheOperatorSaid.DENIED, declined.said)
        assertFalse(declined.mayOpen)
        assertFalse(declined.mayAsk)
        assertTrue(declined.mayAskAgain)
        assertEquals(WhyNothingWasRead.THE_CAMERA_IS_NOT_PERMITTED, declined.whyNot)
    }

    @Test
    fun `a settled refusal sends the operator to settings instead`() {
        // The same refusal word and the opposite advice, which is the whole
        // reason `mayAskAgain` is a separate reading: a screen choosing between
        // "try again" and "open Settings" has only this to choose on.
        val settled = CameraRule.UNASKED.copy(everAsked = true)

        assertEquals(WhatTheOperatorSaid.DENIED, settled.said)
        assertFalse(settled.mayAskAgain)
        assertEquals(WhyNothingWasRead.THE_CAMERA_IS_NOT_PERMITTED, settled.whyNot)
    }

    @Test
    fun `the operator closing it is never a permission's answer`() {
        // Over the whole grid rather than over one case. Closing the scanner is
        // what happened while it was open, and a rule that could answer it from
        // four booleans would be reporting a dismissal nobody performed.
        val grid = everyCombination()

        assertEquals(16, grid.size)

        for (rule in grid) {
            assertTrue(rule.whyNot != WhyNothingWasRead.THE_OPERATOR_CLOSED_IT)
        }
    }

    /**
     * Every combination of the four facts, as sixteen rules.
     *
     * Built from pairs rather than from four nested loops, which the analyser
     * reads as the tangle it is — and it is right: four loops deep, the reader
     * has to hold which of the four a name belongs to.
     */
    private fun everyCombination(): List<CameraRule> {
        val both = listOf(false, true)
        val pairs = both.flatMap { first -> both.map { second -> first to second } }

        return pairs.flatMap { (exists, granted) ->
            pairs.map { (explain, asked) -> CameraRule(exists, granted, explain, asked) }
        }
    }

    @Test
    fun `each way of getting nothing has its own word on the wire`() {
        assertEquals("the_operator_closed_it", WhyNothingWasRead.THE_OPERATOR_CLOSED_IT.word)
        assertEquals("the_camera_is_not_permitted", WhyNothingWasRead.THE_CAMERA_IS_NOT_PERMITTED.word)
        assertEquals("there_is_no_camera", WhyNothingWasRead.THERE_IS_NO_CAMERA.word)
    }
}
