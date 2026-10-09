package app.lemonfiber.native

import kotlin.test.Test
import kotlin.test.assertFalse
import kotlin.test.assertNotNull
import kotlin.test.assertTrue

/**
 * Whether a connection the player opened reached the door the core stated.
 *
 * The same cases as `DoorTrustTests.swift`, in the same order.
 */
class DoorTrustTest {
    private val theDoorsCertificate = "the door's certificate".toByteArray()
    private val theDoorsFingerprint = "13ee3a6685a324f7ebbfebb922980ba07a74c0e54cf2243b2f199dc81912e9f5"

    private fun trust(): DoorTrust =
        DoorTrust(
            assertNotNull(Door.of("https://door.home:8443/library")),
            assertNotNull(DoorPin.of(theDoorsFingerprint)),
        )

    @Test
    fun `the promised certificate at the door is admitted`() {
        assertTrue(trust().admits(theDoorsCertificate, "Door.Home", 8443))
    }

    @Test
    fun `the promised certificate at another host or port is refused`() {
        assertFalse(trust().admits(theDoorsCertificate, "elsewhere.example", 8443))
        assertFalse(trust().admits(theDoorsCertificate, "door.home", 443))
    }

    @Test
    fun `another certificate at the door is refused`() {
        assertFalse(trust().admits("another machine's certificate".toByteArray(), "door.home", 8443))
    }

    @Test
    fun `a connection that presented no certificate is refused`() {
        assertFalse(trust().admits(null, "door.home", 8443))
    }
}
