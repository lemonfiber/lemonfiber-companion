import Foundation

/// Where the rows of a list somebody puts in order end up.
///
/// The rule `ReorderableRenderer` draws by, kept free of SwiftUI so `swift test`
/// runs it on a laptop. `ReorderingTest.kt` asks the Android half the same
/// questions.
public enum Reordering {
    /// Between two keys in the order a list sends, as `Reorderable::BETWEEN` reads it.
    public static let between = "\n"

    /// The keys with the one at `from` moved to `to`; a position outside the list moves nothing.
    public static func moved(_ keys: [String], from: Int, to: Int) -> [String] {
        guard keys.indices.contains(from), keys.indices.contains(to), from != to else {
            return keys
        }

        var reordered = keys
        reordered.insert(reordered.remove(at: from), at: to)

        return reordered
    }

    /// Where a row picked up at `from` and dragged by `offset` lands, in a list of
    /// `count` rows each `rowHeight` tall: the nearest place, never past either end.
    public static func landing(from: Int, offset: Double, rowHeight: Double, count: Int) -> Int {
        guard rowHeight > 0, count > 0 else {
            return from
        }

        // Half a row rounds towards the end of the list, as Kotlin's `roundToInt` does.
        let steps = Int((offset / rowHeight + 0.5).rounded(.down))

        return min(max(from + steps, 0), count - 1)
    }

    /// The keys as the list sends them.
    public static func sent(_ keys: [String]) -> String {
        keys.joined(separator: between)
    }
}
