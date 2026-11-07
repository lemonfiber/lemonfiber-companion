/// What a wake does before it hears, by whether the app's PHP runtime is up.
///
/// The system launches the app with no window to run a background refresh
/// when it had ended it, and NativePHP boots its runtime only from the splash.
/// The background lane a wake hears through stands on that runtime, so a wake
/// that finds none boots it first, the way opening the app would.
///
/// No Apple framework in sight, for `LinkRule`'s reason.
public enum ColdWake: Equatable {
    /// The runtime is up: hear at once.
    case hears

    /// The runtime is not up: boot it, then hear.
    case bootsTheRuntimeFirst

    /// What a wake does, where the app's runtime is up or not.
    public static func on(runtimeIsUp: Bool) -> ColdWake {
        runtimeIsUp ? .hears : .bootsTheRuntimeFirst
    }
}
