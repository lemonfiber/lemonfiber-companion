package app.lemonfiber.native

import android.content.Intent
import android.os.Handler
import android.os.Looper
import android.util.Log
import androidx.fragment.app.FragmentActivity
import com.nativephp.mobile.bridge.BridgeFunction

/**
 * Playing a title from the household's library, on Android.
 *
 * Namespace: `Lemonfiber.Player.*`
 *
 * The decisions worth testing are not in this file. Where a request holds
 * ([WhatToPlay]), which door a connection may reach ([DoorTrust]), what a
 * playlist may name ([PlaylistRule]), which tracks to start with
 * ([TrackRule]), when to report and when to stop waiting ([PlaybackRule]), and
 * what the app is told ([PlayerState]) all run on a laptop. What is here is
 * putting the player on screen and answering the app.
 *
 * **What the player says reaches the app only by being asked.** The event it
 * sends carries nothing; `State` is the answer, and it carries no address, no
 * grant and no fingerprint.
 *
 * **Nothing the app sent is logged.** Not the location, not the grant, not the
 * fingerprint. The log lines here carry closed words.
 */
public object PlayerFunctions {
    private const val TAG = "Lemonfiber"

    private val main = Handler(Looper.getMainLooper())

    /** `Lemonfiber.Player.Open` — put the player on screen for what the app asked to play. */
    public class Open(
        private val activity: FragmentActivity,
    ) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val read = WhatToPlay.read(parameters)
            val asked = (read as? WhatToPlay.Read.ToPlay)?.asked
            val why =
                when {
                    read is WhatToPlay.Read.Refused -> read.why.word
                    asked == null || PlayerSession.extensions.source(asked) == null ->
                        WhyPlaybackStopped.REFUSED.word
                    else -> null
                }

            if (asked == null || why != null) {
                Log.i(TAG, "player: refused, ${why ?: WhyPlaybackStopped.REFUSED.word}")

                return Envelope.refusing("refused", why ?: WhyPlaybackStopped.REFUSED.word).asAnswer()
            }

            PlayerSession.begin(asked)
            main.post {
                PlayerSession.service?.play(asked)
                activity.startActivity(
                    Intent(
                        activity,
                        PlayerActivity::class.java,
                    ).addFlags(Intent.FLAG_ACTIVITY_REORDER_TO_FRONT),
                )
            }
            Log.i(TAG, "player: showing")

            return Envelope.of("showing").asAnswer()
        }
    }

    /** `Lemonfiber.Player.Command` — carry out one thing the app asked of the player on screen. */
    public class Command(
        @Suppress("UnusedPrivateProperty") private val activity: FragmentActivity,
    ) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val word = parameters["command"] as? String ?: ""
            val seconds = (parameters["seconds"] as? Number)?.toDouble() ?: 0.0
            val track = parameters["track"] as? String ?: ""

            if (PlayerSession.service == null) {
                return Envelope.refusing("refused", "no_player").asAnswer()
            }

            main.post { PlayerSession.service?.command(word, seconds, track) }

            return Envelope.of("done").asAnswer()
        }
    }

    /** `Lemonfiber.Player.Close` — take the player off screen. */
    public class Close(
        @Suppress("UnusedPrivateProperty") private val activity: FragmentActivity,
    ) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            main.post {
                PlayerSession.screen?.get()?.finish()
                PlayerSession.service?.close()
            }

            return Envelope.of("closed").asAnswer()
        }
    }

    /** `Lemonfiber.Player.State` — where the player stands: the only way the app learns about playback. */
    public class State(
        @Suppress("UnusedPrivateProperty") private val activity: FragmentActivity,
    ) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> =
            Envelope.of("said", PlayerSession.state.answer()).asAnswer()
    }
}
