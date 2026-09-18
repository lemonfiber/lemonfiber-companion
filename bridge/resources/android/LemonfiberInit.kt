package app.lemonfiber.native

import android.app.Application
import android.content.Context
import android.util.Log

private const val TAG = "Lemonfiber"

/**
 * Where the Android half is switched on.
 *
 * Named in `nativephp.json` as this plugin's `android.init_function`, so the
 * builder calls it once while the app starts, out of the same generated file
 * that registers the bridge functions. It exists as its own symbol because the
 * alternative — installing the lifecycle callbacks lazily, the first time a
 * bridge function is called — would leave the window unprotected until a screen
 * happened to conceal itself. The task switcher does not wait for that.
 *
 * **A top-level function taking a `Context`, which is the one shape the builder
 * can call.** The generated registration imports the symbol by its full path and
 * calls it with the context it was handed, so a member of an object is not
 * reachable from there however `@JvmStatic` it is. `LemonfiberInit.swift` is an
 * `@objc` class for the same reason in reverse: each builder emits one shape,
 * and a symbol that does not match its platform's is a plugin whose init never
 * runs — silently, because nothing looks for a function it was never told to
 * call.
 *
 * Deliberately mirrors `LemonfiberInit.swift`.
 *
 * @param context whatever the host had to hand when the bridge was registered.
 */
public fun installLemonfiber(context: Context) {
    val application = context.applicationContext

    if (application !is Application) {
        // There is nothing to register lifecycle callbacks on, and nothing this
        // file can do about it. Said out loud rather than returned quietly: the
        // window would go unprotected in the task switcher while every screen
        // still looked right, which is the failure that cannot be seen from
        // inside the app.
        Log.e(TAG, "capture protection not installed: ${application.javaClass.name} is not an Application")

        return
    }

    LemonfiberFunctions.install(application)
}
