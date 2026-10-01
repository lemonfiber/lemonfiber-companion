package app.lemonfiber.native

import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertFalse
import kotlin.test.assertTrue

/**
 * When the app is locked, and what the glass may show.
 *
 * The same cases as `LockRuleTests.swift`; a CI job compares the two files case
 * for case, so the halves cannot quietly disagree about when an app locks.
 */
class LockRuleTest {
    private val later = 1_000L

    private fun openAt(now: Long): LockRule =
        LockRule.coldStart().asking().answered(
            succeeded = true,
        ).left(now)

    @Test
    fun `a cold start stands, whatever Lock after says`() {
        assertTrue(LockRule.coldStart().standsAt(now = 0, canAsk = true))
        assertTrue(LockRule.coldStart().awayFor(seconds = 3600).standsAt(now = later, canAsk = true))
    }

    @Test
    fun `only the device's success opens it`() {
        val failed = LockRule.coldStart().asking().answered(succeeded = false)

        assertTrue(failed.held)
        assertFalse(failed.prompting)
        assertFalse(LockRule.coldStart().asking().answered(succeeded = true).held)
    }

    @Test
    fun `coming back inside Lock after leaves it open`() {
        val rule = openAt(later).awayFor(seconds = 60).returned(now = later + 59, canAsk = true)

        assertFalse(rule.held)
        assertFalse(rule.mustCover)
    }

    @Test
    fun `coming back once Lock after has passed stands it again`() {
        val rule = openAt(later).awayFor(seconds = 60).returned(now = later + 60, canAsk = true)

        assertTrue(rule.held)
        assertTrue(rule.mustCover)
    }

    @Test
    fun `immediately stands it on every return`() {
        assertTrue(openAt(later).returned(now = later, canAsk = true).held)
    }

    @Test
    fun `a time away counts before the return`() {
        val away = openAt(later).awayFor(seconds = 60)

        assertFalse(away.standsAt(now = later + 59, canAsk = true))
        assertTrue(away.standsAt(now = later + 60, canAsk = true))
    }

    @Test
    fun `the device's own prompt going up is not leaving`() {
        val rule = LockRule.coldStart().asking().answered(succeeded = true).asking().left(later)

        assertFalse(rule.away)
        assertFalse(rule.returned(now = later + 3600, canAsk = true).held)
    }

    @Test
    fun `the glass stays covered from leaving until the lock is drawn`() {
        val back = openAt(later).returned(now = later, canAsk = true)

        assertTrue(openAt(later).mustCover)
        assertTrue(back.mustCover)
        assertFalse(back.drawn().mustCover)
    }

    @Test
    fun `opening it uncovers the glass`() {
        val back = openAt(later).returned(now = later, canAsk = true)

        assertFalse(back.asking().answered(succeeded = true).mustCover)
        assertTrue(back.asking().answered(succeeded = false).mustCover)
    }

    @Test
    fun `it asks by itself once per standing`() {
        val asked =
            openAt(
                later,
            ).returned(now = later, canAsk = true).askingByItself().answered(succeeded = false)

        assertFalse(asked.mayAskByItself)
        assertTrue(asked.left(later).returned(now = later, canAsk = true).held)
        assertFalse(asked.left(later).returned(now = later, canAsk = true).mayAskByItself)
        assertTrue(
            asked.asking().answered(
                succeeded = true,
            ).left(later).returned(later, canAsk = true).mayAskByItself,
        )
    }

    @Test
    fun `it never asks over a prompt that is already up`() {
        assertFalse(LockRule.coldStart().asking().mayAskByItself)
    }

    @Test
    fun `waiving opens it and uncovers the glass`() {
        val waived = openAt(later).returned(now = later, canAsk = true).waived()

        assertFalse(waived.held)
        assertFalse(waived.mustCover)
    }

    @Test
    fun `a device with no screen lock has nobody to ask`() {
        assertFalse(LockRule.coldStart().standsAt(now = 0, canAsk = false))
        assertFalse(openAt(later).returned(now = later, canAsk = false).mustCover)
    }

    @Test
    fun `the passcode stands behind the biometrics`() {
        assertEquals(WhatUnlocks.DEVICE_CREDENTIAL, WhatUnlocks.ACCEPTED and WhatUnlocks.DEVICE_CREDENTIAL)
        assertEquals(WhatUnlocks.BIOMETRIC_STRONG, WhatUnlocks.ACCEPTED and WhatUnlocks.BIOMETRIC_STRONG)
    }
}
