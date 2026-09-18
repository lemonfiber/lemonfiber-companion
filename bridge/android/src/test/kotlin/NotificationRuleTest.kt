package app.lemonfiber.native

import kotlin.test.Test
import kotlin.test.assertEquals

/**
 * Telling *nobody has been asked* from *the operator said no*.
 *
 * The same cases as `NotificationRuleTests.swift`, in the same order. Keeping
 * them aligned is the point: the failure these two files exist to catch is the
 * platforms quietly disagreeing about whether somebody has already refused, and
 * a disagreement is only visible if the questions are the same.
 */
class NotificationRuleTest {
    private fun rule(
        wouldAppear: Boolean = false,
        permissionIsAsked: Boolean = true,
        wouldExplain: Boolean = false,
        everAsked: Boolean = false,
    ) = NotificationRule(wouldAppear, permissionIsAsked, wouldExplain, everAsked)

    @Test
    fun `a notification that would appear is granted, whatever else is true`() {
        // First question and usually the last. It covers the runtime
        // permission, the per-channel switch and the app-level switch at once,
        // and any of them being off means the same thing to somebody waiting.
        assertEquals("granted", rule(wouldAppear = true).said())
        assertEquals("granted", rule(wouldAppear = true, everAsked = true, wouldExplain = true).said())
    }

    @Test
    fun `nobody asked yet is not a refusal`() {
        // The state that must not read as denied. Without it the app would
        // never raise its first prompt, and every other case here would still
        // pass — an app that silently never asks looks exactly like an app
        // whose operator refused.
        assertEquals("not_determined", rule().said())
    }

    @Test
    fun `an explanation the platform would offer is evidence of a refusal`() {
        // True only after a refusal, which is what makes it evidence of one.
        assertEquals("denied", rule(wouldExplain = true).said())
    }

    @Test
    fun `asked once and still silent is a refusal`() {
        // The permanent refusal. Android answers this identically to never
        // having asked, which is the whole reason the asking is written down.
        assertEquals("denied", rule(everAsked = true).said())
    }

    @Test
    fun `silence where there is nothing to ask for is a refusal already given`() {
        // Below API 33 there is no runtime permission. Notifications off means
        // the operator turned them off in settings — a refusal already given,
        // and prompting for a permission the platform does not have would do
        // nothing at all.
        assertEquals("denied", rule(permissionIsAsked = false).said())
        assertEquals("denied", rule(permissionIsAsked = false, everAsked = false).said())
    }
}
