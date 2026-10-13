package app.lemonfiber.native

import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertFalse
import kotlin.test.assertNull
import kotlin.test.assertTrue

/** Where a key the Keystore made lives, by the security level it reports, and what attests it. */
class PhoneKeyKeystoreTest {
    @Test
    fun `a key made in the chip or the trusted environment lives there`() {
        assertEquals(
            WhereAPhoneKeyLives.STRONGBOX,
            PhoneKeyRule.madeIn(PhoneKeyRule.SECURITY_LEVEL_STRONGBOX),
        )
        assertEquals(
            WhereAPhoneKeyLives.TEE,
            PhoneKeyRule.madeIn(PhoneKeyRule.SECURITY_LEVEL_TRUSTED_ENVIRONMENT),
        )
    }

    @Test
    fun `secure hardware the platform cannot name is the trusted environment`() {
        assertEquals(WhereAPhoneKeyLives.TEE, PhoneKeyRule.madeIn(PhoneKeyRule.SECURITY_LEVEL_UNKNOWN_SECURE))
    }

    @Test
    fun `a key made in software lives nowhere a stack accepts`() {
        assertNull(PhoneKeyRule.madeIn(PhoneKeyRule.SECURITY_LEVEL_SOFTWARE))
    }

    @Test
    fun `a chain of one certificate is the key vouching for itself`() {
        assertFalse(PhoneKeyRule.attested(0))
        assertFalse(PhoneKeyRule.attested(1))
        assertTrue(PhoneKeyRule.attested(PhoneKeyRule.ATTESTED_CHAIN))
    }
}
