package app.lemonfiber.native

import kotlin.math.roundToInt

/**
 * Where the rows of a list somebody puts in order end up.
 *
 * The rule `ReorderableRenderer` draws by, kept free of Compose so a JVM runs it
 * in a second. `ReorderingTests.swift` asks the iOS half the same questions.
 */
public object Reordering {
    /** Between two keys in the order a list sends, as `Reorderable::BETWEEN` reads it. */
    public const val BETWEEN: String = "\n"

    /** The keys with the one at [from] moved to [to]; a position outside the list moves nothing. */
    public fun moved(
        keys: List<String>,
        from: Int,
        to: Int,
    ): List<String> {
        if (from !in keys.indices || to !in keys.indices || from == to) {
            return keys
        }

        val reordered = keys.toMutableList()
        reordered.add(to, reordered.removeAt(from))

        return reordered
    }

    /**
     * Where a row picked up at [from] and dragged by [offset] lands, in a list of
     * [count] rows each [rowHeight] tall: the nearest place, never past either end.
     */
    public fun landing(
        from: Int,
        offset: Float,
        rowHeight: Float,
        count: Int,
    ): Int {
        if (rowHeight <= 0f || count <= 0) {
            return from
        }

        return (from + (offset / rowHeight).roundToInt()).coerceIn(0, count - 1)
    }

    /** The keys as the list sends them. */
    public fun sent(keys: List<String>): String = keys.joinToString(BETWEEN)
}
