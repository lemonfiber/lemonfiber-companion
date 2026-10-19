package app.lemonfiber.native

import kotlin.test.Test
import kotlin.test.assertEquals

/**
 * Whether this phone may keep a key, and the words for what became of one.
 *
 * The same six cases as `PhoneKeyRuleTests.swift`. Reading the security level a key was
 * made at, and its attestation chain, are Android's alone and are asked in
 * `PhoneKeyKeystoreTest`.
 */
class PhoneKeyRuleTest {
    @Test
    fun `a key lives in hardware where the phone has some and a face or fingerprint guards it`() {
        assertEquals(
            PhoneKeyRule.Either.Lives(WhereAPhoneKeyLives.STRONGBOX),
            PhoneKeyRule.mayLive(PhoneKeyRule.BIOMETRIC_SUCCESS, strongBox = true),
        )
        assertEquals(
            PhoneKeyRule.Either.Lives(WhereAPhoneKeyLives.TEE),
            PhoneKeyRule.mayLive(PhoneKeyRule.BIOMETRIC_SUCCESS, strongBox = false),
        )
    }

    @Test
    fun `no face or fingerprint is told from a phone that cannot keep a key at all`() {
        assertEquals(
            PhoneKeyRule.Either.Not(WhyNoPhoneKey.NO_BIOMETRIC),
            PhoneKeyRule.mayLive(PhoneKeyRule.BIOMETRIC_ERROR_NONE_ENROLLED, strongBox = true),
        )
        assertEquals(
            PhoneKeyRule.Either.Not(WhyNoPhoneKey.NO_HARDWARE),
            PhoneKeyRule.mayLive(BIOMETRIC_ERROR_NO_HARDWARE, strongBox = true),
        )
    }

    @Test
    fun `somebody declining is not a failure`() {
        listOf(
            PhoneKeyRule.ERROR_CANCELED,
            PhoneKeyRule.ERROR_USER_CANCELED,
            PhoneKeyRule.ERROR_NEGATIVE_BUTTON,
        ).forEach {
            assertEquals(WhyNothingWasSigned.CANCELLED, PhoneKeyRule.whyNotSigned(it))
        }
        assertEquals(WhyNothingWasSigned.FAILED, PhoneKeyRule.whyNotSigned(ERROR_LOCKOUT))
    }

    @Test
    fun `a key still there after it was thrown away is never said to be gone`() {
        assertEquals(ThrownAway.NOT_FORGOTTEN, PhoneKeyRule.thrownAway(heldBefore = true, heldAfter = true))
        assertEquals(ThrownAway.NOT_FORGOTTEN, PhoneKeyRule.thrownAway(heldBefore = false, heldAfter = true))
    }

    @Test
    fun `a key that was never there is told from one thrown away`() {
        assertEquals(ThrownAway.FORGOTTEN, PhoneKeyRule.thrownAway(heldBefore = true, heldAfter = false))
        assertEquals(ThrownAway.NONE_HELD, PhoneKeyRule.thrownAway(heldBefore = false, heldAfter = false))
    }

    @Test
    fun `every word is the one the wire carries`() {
        assertEquals(listOf("strongbox", "tee"), WhereAPhoneKeyLives.entries.map { it.word })
        assertEquals(
            listOf("no_hardware", "no_biometric", "in_software", "unattested", "failed"),
            WhyNoPhoneKey.entries.map { it.word },
        )
        assertEquals(listOf("cancelled", "gone", "failed"), WhyNothingWasSigned.entries.map { it.word })
        assertEquals(listOf("forgotten", "none_held", "not_forgotten"), ThrownAway.entries.map { it.word })
    }

    private companion object {
        /** `BiometricManager.BIOMETRIC_ERROR_NO_HARDWARE`. */
        const val BIOMETRIC_ERROR_NO_HARDWARE = 12

        /** `BiometricPrompt.ERROR_LOCKOUT`: too many attempts, which is not somebody declining. */
        const val ERROR_LOCKOUT = 7
    }
}
