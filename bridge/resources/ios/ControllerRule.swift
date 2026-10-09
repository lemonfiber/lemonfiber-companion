/// Who may connect to the player to control it or to read what it plays.
///
/// The player publishes a media session so the lock screen, the notification
/// and a headset's buttons can pause it, and a session is something any app on
/// the phone can ask to connect to. **Only two kinds of asker are let in**: this
/// app itself, whose own screen and notification are controllers, and the
/// system, which is what draws the lock screen and carries a headset's buttons
/// and is the only kind of app granted the media control permission. Every
/// other app is refused, whatever it calls itself, so none can pause, seek or
/// read the title of what a member is watching.
///
/// On iOS the platform hands remote commands to an app only from the system,
/// so nothing there asks this; it is mirrored so both halves state the same
/// rule.
///
/// Deliberately mirrors `ControllerRule.kt` line for line.
public enum ControllerRule {
    /// Whether an app asking to connect may.
    ///
    /// - Parameters:
    ///   - asker: the app asking, by its package or bundle name.
    ///   - own: this app's own name.
    ///   - holdsMediaControl: whether the asker holds the platform's media control permission.
    /// - Returns: whether to let it connect.
    public static func admits(asker: String, own: String, holdsMediaControl: Bool) -> Bool {
        !asker.isEmpty && (asker == own || holdsMediaControl)
    }
}
