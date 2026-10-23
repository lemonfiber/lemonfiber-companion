import BackgroundTasks
import Foundation

@_silgen_name("ephemeral_php_boot")
private func ephemeralBoot(_ bootstrapPath: UnsafePointer<CChar>?) -> Int32

@_silgen_name("ephemeral_php_artisan")
private func ephemeralArtisan(_ command: UnsafePointer<CChar>?) -> UnsafePointer<CChar>?

@_silgen_name("ephemeral_php_shutdown")
private func ephemeralShutdown()

/// Being woken while away, on iOS.
///
/// Namespace: `Lemonfiber.Wakes.*`
///
/// Background app refresh, which the system runs when it chooses and may not
/// run at all. A wake carries nothing: it boots a short-lived PHP runtime beside
/// the app's own and runs `WakeRule.command`, which reads what each stack raised
/// over the app's own pinned connection and tells it locally. No push service is
/// involved.
///
/// The short-lived runtime stands on the app's own, so a wake reaches PHP only
/// while the app is held in the background; a wake after the system ended the
/// app finds no runtime and completes having told nothing.
///
/// Whether it was asked is kept beside the app's other preferences, so the
/// request is made again at launch and after every wake until `Stop`.
enum WakeFunctions {
    private static let tag = "Lemonfiber"

    /// Where it is kept that wakes are asked for.
    private static let asked = "lemonfiber.wakes.asked"

    /// Where NativePHP's bootstrap for a PHP runtime lives, under the app's own directory.
    private static let bootstrap = "/vendor/nativephp/mobile/bootstrap/ios/persistent.php"

    /// Register what a wake does, and ask again where wakes were asked for.
    ///
    /// Called from `LemonfiberInit.install()`, before the app finishes launching,
    /// which is the only moment the system accepts the registration.
    static func install() {
        BGTaskScheduler.shared.register(forTaskWithIdentifier: WakeRule.name, using: nil) { task in
            guard let refresh = task as? BGAppRefreshTask else {
                task.setTaskCompleted(success: false)

                return
            }

            heard(refresh)
        }

        if UserDefaults.standard.bool(forKey: asked) {
            _ = submitted()
        }
    }

    /// Ask the system for the next wake, whenever it chooses.
    private static func submitted() -> Bool {
        do {
            try BGTaskScheduler.shared.submit(BGAppRefreshTaskRequest(identifier: WakeRule.name))

            return true
        } catch {
            NSLog("%@ wakes: not taken, %@", tag, String(describing: type(of: error)))

            return false
        }
    }

    /// One wake: the next asked for first, then the command, completed once either way.
    private static func heard(_ task: BGAppRefreshTask) {
        if UserDefaults.standard.bool(forKey: asked) {
            _ = submitted()
        }

        let completed = NSLock()
        var done = false

        let complete: (Bool) -> Void = { success in
            completed.lock()
            defer { completed.unlock() }

            if !done {
                done = true
                task.setTaskCompleted(success: success)
            }
        }

        task.expirationHandler = { complete(false) }

        DispatchQueue.global(qos: .utility).async {
            let path = AppUpdateManager.shared.getAppPath() + bootstrap

            guard path.withCString({ ephemeralBoot($0) }) == 0 else {
                NSLog("%@ wakes: no runtime", tag)
                complete(true)

                return
            }

            _ = WakeRule.command.withCString { ephemeralArtisan($0) }
            ephemeralShutdown()

            NSLog("%@ wakes: heard", tag)
            complete(true)
        }
    }

    /// `Lemonfiber.Wakes.Ask` — ask to be woken whenever the system chooses.
    class Ask: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            UserDefaults.standard.set(true, forKey: asked)

            guard submitted() else {
                return Envelope.refusing("refused", because: "unavailable").asAnswer()
            }

            NSLog("%@ wakes: asked", tag)

            return Envelope.of("will_wake").asAnswer()
        }
    }

    /// `Lemonfiber.Wakes.Stop` — stop asking, and take back the wake the system holds.
    class Stop: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            UserDefaults.standard.removeObject(forKey: asked)
            BGTaskScheduler.shared.cancel(taskRequestWithIdentifier: WakeRule.name)

            NSLog("%@ wakes: stopped", tag)

            return Envelope.of("will_not_wake").asAnswer()
        }
    }
}
