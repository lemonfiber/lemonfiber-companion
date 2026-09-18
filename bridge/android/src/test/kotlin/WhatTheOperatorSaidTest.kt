package app.lemonfiber.native

import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertFalse
import kotlin.test.assertTrue

/**
 * Three answers reconstructed from the two a platform is willing to report.
 *
 * The same eight cases as `WhatTheOperatorSaidTests.swift`, in the same order.
 * Keeping them aligned is the point: the failure these two files exist to catch
 * is the platforms quietly disagreeing about whether somebody has been asked,
 * and a disagreement is only visible if the questions are the same.
 *
 * This is the vocabulary every capability that asks for a permission shares, so
 * a case added here is a case both of them answer.
 */
class WhatTheOperatorSaidTest {
    @Test
    fun `something that would work settles it`() {
        // First and unconditional. An operator who refused once and turned it
        // back on in settings has allowed it, and every other input still says
        // otherwise — so a reading in any other order reports a refusal about a
        // device that is working right now.
        val allowedAfterRefusing =
            WhatTheOperatorSaid.readFrom(
                wouldAppear = true,
                permissionIsAsked = true,
                wouldExplain = true,
                everAsked = true,
            )

        assertEquals(WhatTheOperatorSaid.GRANTED, allowedAfterRefusing)
    }

    @Test
    fun `a platform with no permission to ask has been answered in settings`() {
        // Below Android 33 there is no runtime permission for notifications and
        // nothing to prompt for. Off is a decision already taken rather than a
        // question still open, and asking would raise a dialog the platform has
        // never heard of.
        assertEquals(
            WhatTheOperatorSaid.DENIED,
            WhatTheOperatorSaid.readFrom(
                wouldAppear = false,
                permissionIsAsked = false,
                wouldExplain = false,
                everAsked = false,
            ),
        )
    }

    @Test
    fun `an explanation the platform would show is evidence of a refusal`() {
        // `shouldShowRequestPermissionRationale` is true only between a first
        // refusal and a permanent one, which makes it the one positive signal
        // Android gives that somebody has said no.
        assertEquals(
            WhatTheOperatorSaid.DENIED,
            WhatTheOperatorSaid.readFrom(
                wouldAppear = false,
                permissionIsAsked = true,
                wouldExplain = true,
                everAsked = false,
            ),
        )
    }

    @Test
    fun `a prompt this app has already raised was answered no`() {
        // The case nothing in the platform can answer. Once a refusal is
        // permanent the rationale flag goes false again, so *refused for good*
        // and *never asked* are reported identically — and only a record kept
        // where the prompt was raised tells them apart.
        assertEquals(
            WhatTheOperatorSaid.DENIED,
            WhatTheOperatorSaid.readFrom(
                wouldAppear = false,
                permissionIsAsked = true,
                wouldExplain = false,
                everAsked = true,
            ),
        )
    }

    @Test
    fun `nobody has been asked until somebody has`() {
        // The one state that must answer "ask me". Without it the reading could
        // report a refusal unconditionally, every other case here would still
        // pass, and an application that never asks anybody anything would ship.
        assertEquals(
            WhatTheOperatorSaid.NOT_DETERMINED,
            WhatTheOperatorSaid.readFrom(
                wouldAppear = false,
                permissionIsAsked = true,
                wouldExplain = false,
                everAsked = false,
            ),
        )
    }

    @Test
    fun `asking and proceeding are different questions`() {
        // Both false about a refusal, for opposite reasons, which is why
        // neither is the negation of the other. A caller that read one for the
        // other would prompt somebody who has already refused — on every
        // screen, forever.
        assertFalse(WhatTheOperatorSaid.DENIED.mayAsk)
        assertFalse(WhatTheOperatorSaid.DENIED.mayProceed)
    }

    @Test
    fun `a granted answer is not one to ask about again`() {
        assertTrue(WhatTheOperatorSaid.GRANTED.mayProceed)
        assertFalse(WhatTheOperatorSaid.GRANTED.mayAsk)
        assertTrue(WhatTheOperatorSaid.NOT_DETERMINED.mayAsk)
        assertFalse(WhatTheOperatorSaid.NOT_DETERMINED.mayProceed)
    }

    @Test
    fun `every answer carries the word the wire uses`() {
        // The words are the contract. A misspelling here is a `default` arm on
        // the other side of the bridge, which reads a refusal as somebody who
        // has not been asked and re-opens a prompt they already answered.
        assertEquals("granted", WhatTheOperatorSaid.GRANTED.word)
        assertEquals("denied", WhatTheOperatorSaid.DENIED.word)
        assertEquals("not_determined", WhatTheOperatorSaid.NOT_DETERMINED.word)
    }
}
