package app.lemonfiber.native

/**
 * What a wake while away is known by, and what it runs.
 *
 * Deliberately mirrors `WakeRule.swift` line for line.
 */
public object WakeRule {
    /** The console command a wake runs, which hears each stack the operator is signed in to. */
    public const val COMMAND: String = "lemonfiber:hear-while-away"

    /** What the platform knows this app's wakes by. */
    public const val NAME: String = "app.lemonfiber.wake"
}
