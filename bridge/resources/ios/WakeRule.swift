/// What a wake while away is known by, and what it runs.
///
/// No Apple framework in sight, for `LinkRule`'s reason.
///
/// Deliberately mirrors `WakeRule.kt` line for line.
public enum WakeRule {
    /// The console command a wake runs, which hears each stack the operator is signed in to.
    public static let command = "lemonfiber:hear-while-away"

    /// What the platform knows this app's wakes by.
    public static let name = "app.lemonfiber.wake"
}
