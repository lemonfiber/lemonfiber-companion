package app.lemonfiber.native

/**
 * Why nothing could be kept, read, or forgotten.
 *
 * Two refusals and they are not the same sentence. *This phone cannot do this*
 * is answered by telling the operator so and never asking again; *the store
 * would not open* is answered by trying again, and by something being wrong
 * with the device if it keeps happening. A boolean cannot carry that, and an
 * adapter behind one has to guess which it was.
 *
 * Deliberately mirrors `StorageRule.swift` line for line.
 */
public enum class WhyNothingWasKept(
    /** What this answer is called on the wire. */
    public val word: String,
) {
    /** There is no secure store on this device. Nothing will fix it. */
    NO_STORE_ON_THIS_DEVICE("no_store_on_this_device"),

    /** There is one and it would not open. Trying again is reasonable. */
    STORE_WOULD_NOT_OPEN("store_would_not_open"),
}

/**
 * When a kept value may be decrypted again.
 *
 * iOS makes this choice unavoidable and Android has no equivalent knob, which
 * is exactly why it belongs on the wire rather than inside the iOS shim. A
 * decision with security consequences and no safe default is one both platforms
 * should be seen answering — the Android half answers it explicitly, by saying
 * that its store has one behaviour and which of these it corresponds to, rather
 * than by omission.
 *
 * Deliberately mirrors `StorageRule.swift` line for line.
 */
public enum class WhenAValueMayBeRead(
    /** What this choice is called on the wire. */
    public val word: String,
) {
    /**
     * Only while the device is unlocked.
     *
     * The narrowest, and the right one for a session token: a value nothing can
     * read while the phone is in somebody else's hand and locked.
     */
    WHILE_UNLOCKED("while_unlocked"),

    /**
     * From the first unlock after a restart until the next one.
     *
     * What a value needs if anything reads it in the background. Wider than the
     * above and the reason it is a choice rather than a constant.
     */
    AFTER_FIRST_UNLOCK("after_first_unlock"),
    ;

    /** Where a word off the wire becomes one of these. */
    public companion object {
        /**
         * The choice a caller asked for, or the narrowest where it did not.
         *
         * A word this does not recognise is a caller from a newer version of
         * this application than the shim, and the safe reading of that is the
         * tightest of the choices rather than the loosest. Widening when
         * something is not understood is how a value ends up readable on a
         * locked phone because of a typo.
         */
        public fun readFrom(said: String?): WhenAValueMayBeRead =
            entries.firstOrNull { it.word == said } ?: WHILE_UNLOCKED
    }
}

/**
 * What the store's answer means, told apart the way a caller needs them.
 *
 * The decision this capability turns on is not whether a read succeeded. It is
 * **the difference between a key that is not there and a store that cannot be
 * asked**, which look identical to anything that only knows the read came back
 * empty and which are opposite answers. A launch reading a refusal as *no
 * stacks configured* offers to pair a machine that is already paired, and the
 * operator pairs it twice.
 *
 * No Android framework in sight. Everything is a reading of three facts a
 * caller passes in, which is what lets it be run on a JVM in two seconds rather
 * than demonstrated on a handset.
 *
 * Deliberately mirrors `StorageRule.swift` line for line.
 */
public data class StorageRule(
    /**
     * Whether this device has a secure store at all.
     *
     * Its own fact rather than inferred from a failure, because the two
     * refusals differ only in this and an adapter that guessed would tell an
     * operator to try again on a phone where trying again is hopeless.
     */
    public val storeExists: Boolean,
    /**
     * Whether the store was reachable for this operation.
     *
     * False where the platform raised, which the shim catches rather than lets
     * through: a platform exception message can carry the key it failed on, and
     * an uncaught one reaches a crash reporter.
     */
    public val storeOpened: Boolean,
) {
    /** Whether the operation may go ahead at all. */
    public val mayProceed: Boolean
        get() = storeExists && storeOpened

    /**
     * Why it may not, or nothing because it may.
     *
     * Read in this order and the order is load-bearing: a device with no store
     * did not fail to open one, and reporting it as a store that would not open
     * is the advice that sends somebody to try again for ever.
     */
    public val whyNot: WhyNothingWasKept?
        get() =
            when {
                !storeExists -> WhyNothingWasKept.NO_STORE_ON_THIS_DEVICE
                !storeOpened -> WhyNothingWasKept.STORE_WOULD_NOT_OPEN
                else -> null
            }

    /** Where the starting state lives, named so it reads at a call site. */
    public companion object {
        /** A device with a store that opens: what nearly every phone is. */
        public val WORKING: StorageRule = StorageRule(storeExists = true, storeOpened = true)
    }
}
