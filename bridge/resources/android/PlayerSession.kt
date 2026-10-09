package app.lemonfiber.native

import android.app.Activity
import com.nativephp.mobile.ui.nativerender.NativeElementBridge
import java.lang.ref.WeakReference
import java.util.concurrent.atomic.AtomicReference

/**
 * The one playback this app has at a time, shared by the bridge, the service and the screen.
 *
 * What the member asked to play, and where it stands. The bridge writes the
 * request, the service plays it and settles the state, the screen draws it,
 * and the bridge answers the app from the state. **The request lives here and
 * only here**, in memory, while the player is open: it is never put in an
 * intent, a bundle or anything else the platform might write down, because it
 * carries the member's grant.
 *
 * **What the app is told carries nothing.** A move sends an event that says
 * *ask again*, and the app asks; a forged or replayed event can make it ask,
 * and nothing more.
 */
public object PlayerSession {
    /** What the app is told when the state moves. */
    private const val MOVED = "Lemonfiber\\Native\\Events\\ThePlayerMoved"

    /** The extensions the player was built with, registered when the app starts. */
    public val extensions: PlayerExtensions = PlayerExtensions()

    /** What the member asked to play, while a player is open. */
    @Volatile
    public var asked: WhatToPlay? = null
        private set

    /** The service playing it, while there is one. Touched on the main thread. */
    @Volatile
    internal var service: PlaybackService? = null

    /** The screen showing it, while there is one. Touched on the main thread. */
    internal var screen: WeakReference<Activity>? = null

    /** Where playback stands. */
    @Volatile
    public var state: PlayerState = PlayerState.CLOSED
        private set

    /** Why it stopped, the first reason found, until the next open. */
    private val why = AtomicReference<WhyPlaybackStopped?>(null)

    /** Begin a new playback, forgetting why the last one stopped. */
    public fun begin(request: WhatToPlay) {
        why.set(null)
        asked = request
        settle(PlayerState.CLOSED.copy(stands = WherePlaybackStands.OPENING, position = request.startAt))
    }

    /** Keep the first reason playback could not go on. */
    public fun stoppedBecause(reason: WhyPlaybackStopped) {
        why.compareAndSet(null, reason)
    }

    /** Why playback stopped, where a reason was found. */
    public fun whyItStopped(): WhyPlaybackStopped? = why.get()

    /** Move to a new state and tell the app to ask again. */
    public fun settle(now: PlayerState) {
        state = now
        NativeElementBridge.sendNativeEvent(MOVED, "{}")
    }

    /** Forget the request the player was playing, unless another has been asked for since. */
    public fun end(
        played: WhatToPlay?,
        position: Double,
    ) {
        if (asked === played) {
            asked = null
            settle(PlayerState.CLOSED.copy(position = position))
        }
    }
}
