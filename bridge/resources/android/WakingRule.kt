package app.lemonfiber.native

import java.math.BigInteger

/**
 * What waking through a UnifiedPush distributor answers with, and what a push for a stack runs.
 *
 * No Android framework in it, so a JVM proves it. A stack is registered with
 * the distributor as an instance of its own, named by the stack's id, and that
 * id reaches a command line only after [isAStack] has passed it.
 */
public object WakingRule {
    /** The outcome when the distributors are listed. */
    public const val LISTED: String = "listed"

    /** The outcome when an endpoint is given. */
    public const val GIVEN: String = "given"

    /** The outcome when no endpoint is given. */
    public const val NOT_GIVEN: String = "not_given"

    /** The outcome when a registration and its key pair are let go of. */
    public const val LET_GO: String = "let_go"

    /** Why: this phone cannot be woken through a distributor. */
    public const val NOT_HERE: String = "not_here"

    /** Why: the keystore made no key pair. */
    public const val NO_KEY: String = "no_key"

    /** Why: the distributor refused to register. */
    public const val REFUSED: String = "refused"

    /** Why: the distributor did not answer in time. */
    public const val NO_ANSWER: String = "no_answer"

    /** Why: the distributor holds no registration for the stack. */
    public const val DROPPED: String = "dropped"

    /** What the distributor is told the registration is for. Nothing of the stack. */
    public const val MESSAGE_FOR_DISTRIBUTOR: String = "lemonfiber"

    /** How long registering waits for the distributor's endpoint, in seconds. */
    public const val WAIT_SECONDS: Long = 10L

    /** The first Android release whose keystore makes a key for key agreement. */
    public const val FIRST_RELEASE: Int = 31

    /** What a stack's key pair is filed under in the keystore, before the stack's id. */
    public const val ALIAS: String = "lemonfiber.waking."

    /** How many bytes one coordinate of a P-256 point is. */
    private const val COORDINATE_BYTES: Int = 32

    /** The byte an uncompressed point begins with. */
    private const val UNCOMPRESSED: Byte = 0x04

    /** What a stack's id may be, to be an instance and an argument on a command line. */
    private val STACK = Regex("^[A-Za-z0-9_-]{1,128}$")

    /** Whether this Android release can keep a waking key. */
    public fun canWakeOn(release: Int): Boolean = release >= FIRST_RELEASE

    /** Whether `instance` names a stack. */
    public fun isAStack(instance: String): Boolean = STACK.matches(instance)

    /** The keystore alias of the stack's key pair. */
    public fun aliasFor(stack: String): String = ALIAS + stack

    /** The command a push for the stack runs, or none where `stack` names no stack. */
    public fun commandFor(stack: String): String? =
        if (isAStack(stack)) "${WakeRule.COMMAND} --stack=$stack" else null

    /** What a push for the stack is queued under, so pushes for one stack run one after another. */
    public fun workFor(stack: String): String = "${WakeRule.NAME}.$stack"

    /**
     * Whether a new endpoint wakes the app for its stack.
     *
     * Only one nobody waits for, where the distributor already gave the stack
     * another: the stack holds the old one, and the wake registers the new.
     */
    public fun wakesForANewEndpoint(
        previous: String?,
        given: String,
        awaited: Boolean,
    ): Boolean = !awaited && previous != null && previous != given

    /** The point (`x`, `y`) as WebPush sends it: 0x04, then each coordinate in 32 bytes. */
    public fun uncompressed(
        x: BigInteger,
        y: BigInteger,
    ): ByteArray = byteArrayOf(UNCOMPRESSED) + coordinate(x) + coordinate(y)

    private fun coordinate(value: BigInteger): ByteArray {
        val bytes = value.toByteArray().takeLast(COORDINATE_BYTES).toByteArray()

        return ByteArray(COORDINATE_BYTES - bytes.size) + bytes
    }
}
