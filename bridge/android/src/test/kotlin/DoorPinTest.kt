package app.lemonfiber.native

import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertNull

/**
 * Whether a certificate the door presents is the one the core said it would.
 *
 * The certificates are stand-in bytes: the rule hashes whatever it is handed,
 * and what it decides is whether that hash is the pinned one.
 *
 * The same cases as `DoorPinTests.swift`, in the same order.
 */
class DoorPinTest {
    private val theDoorsCertificate = "the door's certificate".toByteArray()
    private val anotherMachinesCertificate = "another machine's certificate".toByteArray()
    private val theDoorsFingerprint = "13ee3a6685a324f7ebbfebb922980ba07a74c0e54cf2243b2f199dc81912e9f5"

    @Test
    fun `the certificate the door promised is admitted`() {
        assertEquals(true, DoorPin.of(theDoorsFingerprint)?.admits(theDoorsCertificate))
    }

    @Test
    fun `any other certificate is refused`() {
        assertEquals(false, DoorPin.of(theDoorsFingerprint)?.admits(anotherMachinesCertificate))
    }

    @Test
    fun `a fingerprint in capitals pins the same certificate`() {
        assertEquals(true, DoorPin.of(theDoorsFingerprint.uppercase())?.admits(theDoorsCertificate))
    }

    @Test
    fun `a fingerprint that is not a digest pins nothing`() {
        // Short, long, empty, a colon where a pair should be, and a character no
        // hexadecimal digit is: each is a malformed answer, and none may become a
        // pin that admits anything.
        for (malformed in listOf(
            theDoorsFingerprint.dropLast(1),
            theDoorsFingerprint + "0",
            "",
            "13:" + theDoorsFingerprint.drop(3),
            "g" + theDoorsFingerprint.drop(1),
            theDoorsFingerprint.dropLast(1) + "g",
        )) {
            assertNull(DoorPin.of(malformed))
        }
    }

    @Test
    fun `a fingerprint is sixty-four characters`() {
        assertEquals(64, DoorPin.CHARACTERS)
    }
}
