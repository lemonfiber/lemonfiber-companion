package app.lemonfiber.native

import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertFalse
import kotlin.test.assertNull
import kotlin.test.assertTrue

/**
 * The distinction keeping a secret turns on.
 *
 * Not whether a write succeeded — whether a device that cannot be asked is told
 * apart from a device that has nothing to tell. They are opposite answers and
 * they arrive as the same emptiness, which is why the facts are carried rather
 * than inferred from a failure.
 *
 * The same six cases as `StorageRuleTests.swift`, in the same order.
 */
class StorageRuleTest {
    @Test
    fun `a store that exists and opens refuses nothing`() {
        assertTrue(StorageRule.WORKING.mayProceed)
        assertNull(StorageRule.WORKING.whyNot)
    }

    @Test
    fun `no store on the device is not a store that would not open`() {
        // Both false, which is the combination that proves the order. A rule
        // asking about opening first would tell somebody with no store at all to
        // try again, for ever.
        val absent = StorageRule(storeExists = false, storeOpened = false)

        assertFalse(absent.mayProceed)
        assertEquals(WhyNothingWasKept.NO_STORE_ON_THIS_DEVICE, absent.whyNot)
    }

    @Test
    fun `a store that would not open is worth trying again`() {
        val shut = StorageRule(storeExists = true, storeOpened = false)

        assertFalse(shut.mayProceed)
        assertEquals(WhyNothingWasKept.STORE_WOULD_NOT_OPEN, shut.whyNot)
    }

    @Test
    fun `each refusal has its own word on the wire`() {
        assertEquals("no_store_on_this_device", WhyNothingWasKept.NO_STORE_ON_THIS_DEVICE.word)
        assertEquals("store_would_not_open", WhyNothingWasKept.STORE_WOULD_NOT_OPEN.word)
    }

    @Test
    fun `each moment a value may be read has its own word`() {
        assertEquals("while_unlocked", WhenAValueMayBeRead.WHILE_UNLOCKED.word)
        assertEquals("after_first_unlock", WhenAValueMayBeRead.AFTER_FIRST_UNLOCK.word)
        assertEquals(WhenAValueMayBeRead.WHILE_UNLOCKED, WhenAValueMayBeRead.readFrom("while_unlocked"))
        assertEquals(
            WhenAValueMayBeRead.AFTER_FIRST_UNLOCK,
            WhenAValueMayBeRead.readFrom("after_first_unlock"),
        )
    }

    @Test
    fun `a moment nobody named is the narrowest one`() {
        // The direction that matters. A word this does not know comes from a
        // caller newer than the shim, and reading it as the widest choice is how
        // a session becomes readable on a locked phone because of a typo.
        assertEquals(WhenAValueMayBeRead.WHILE_UNLOCKED, WhenAValueMayBeRead.readFrom(null))
        assertEquals(WhenAValueMayBeRead.WHILE_UNLOCKED, WhenAValueMayBeRead.readFrom(""))
        assertEquals(WhenAValueMayBeRead.WHILE_UNLOCKED, WhenAValueMayBeRead.readFrom("whenever_you_like"))
    }
}
