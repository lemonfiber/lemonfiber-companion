package app.lemonfiber.native

import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertFalse
import kotlin.test.assertTrue

/**
 * What telling somebody adds to the answer every permission gives.
 *
 * The three states and the order they are read in belong to
 * `WhatTheOperatorSaidTest`, because the camera asks the same question and two
 * copies of that decision would be two capabilities disagreeing quietly. What
 * is asked here is the part that is this capability's: that the four facts a
 * notification is judged on reach that reading unshuffled, and that the answer
 * is readable in the words a caller with something to show uses.
 *
 * The same six cases as `NotificationRuleTests.swift`, in the same order.
 */
class NotificationRuleTest {
    @Test
    fun `a notification that would appear may be shown`() {
        val allowed = NotificationRule.UNASKED.copy(wouldAppear = true)

        assertEquals(WhatTheOperatorSaid.GRANTED, allowed.said)
        assertTrue(allowed.mayShow)
        assertFalse(allowed.mayAsk)
    }

    @Test
    fun `a refusal is not a reason to show anything`() {
        val refused = NotificationRule.UNASKED.copy(everAsked = true)

        assertEquals(WhatTheOperatorSaid.DENIED, refused.said)
        assertFalse(refused.mayShow)
        assertFalse(refused.mayAsk)
    }

    @Test
    fun `nobody asked is a question still open and nothing to show yet`() {
        // The one state that must answer "ask me". Without it the rule could
        // report a refusal unconditionally, every other case here would still
        // pass, and an application that never asks anybody anything would ship.
        assertEquals(WhatTheOperatorSaid.NOT_DETERMINED, NotificationRule.UNASKED.said)
        assertTrue(NotificationRule.UNASKED.mayAsk)
        assertFalse(NotificationRule.UNASKED.mayShow)
    }

    @Test
    fun `each fact reaches the reading it belongs to`() {
        // The shuffle this catches: four booleans gathered in one place and
        // handed on in another is an argument order that compiles whichever way
        // round it is written. Each fact is moved on its own, and each one
        // changes the answer differently.
        assertEquals(
            WhatTheOperatorSaid.GRANTED,
            NotificationRule.UNASKED.copy(wouldAppear = true).said,
        )
        assertEquals(
            WhatTheOperatorSaid.DENIED,
            NotificationRule.UNASKED.copy(permissionIsAsked = false).said,
        )
        assertEquals(
            WhatTheOperatorSaid.DENIED,
            NotificationRule.UNASKED.copy(wouldExplain = true).said,
        )
        assertEquals(
            WhatTheOperatorSaid.DENIED,
            NotificationRule.UNASKED.copy(everAsked = true).said,
        )
    }

    @Test
    fun `a first launch on a platform that asks has asked nothing`() {
        assertEquals(
            NotificationRule(
                wouldAppear = false,
                permissionIsAsked = true,
                wouldExplain = false,
                everAsked = false,
            ),
            NotificationRule.UNASKED,
        )
    }

    @Test
    fun `an older platform has been answered rather than left open`() {
        // Below Android 33 there is no notification permission to prompt for,
        // so notifications off is a decision taken in settings. Carried as an
        // input rather than a version check inside the rule, which is what lets
        // the case be asked at all off a handset.
        val old = NotificationRule.UNASKED.copy(permissionIsAsked = false)

        assertFalse(old.mayAsk)
        assertFalse(old.mayShow)
    }
}
