import Foundation

/// Where the iOS half is switched on.
///
/// Named in `nativephp.json` as this plugin's `init_function`, so the builder
/// calls it once while the app starts. It exists as its own file and its own
/// symbol because the alternative — installing observers lazily, the first time
/// a bridge function is called — would leave the window uncovered until a screen
/// happened to conceal itself. The task switcher does not wait for that.
@objc public final class LemonfiberInit: NSObject {
    /// Install the observers that keep the window protected for the life of the
    /// app.
    @objc public static func install() {
        LemonfiberFunctions.install()
    }
}
