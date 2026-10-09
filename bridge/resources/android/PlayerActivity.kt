package app.lemonfiber.native

import android.app.PictureInPictureParams
import android.content.ComponentName
import android.content.res.Configuration
import android.os.Build
import android.os.Bundle
import android.util.Rational
import androidx.annotation.OptIn
import androidx.fragment.app.FragmentActivity
import androidx.lifecycle.Lifecycle
import androidx.media3.common.util.UnstableApi
import androidx.media3.session.MediaController
import androidx.media3.session.SessionToken
import androidx.media3.ui.PlayerView
import com.google.common.util.concurrent.ListenableFuture
import com.google.common.util.concurrent.MoreExecutors
import java.lang.ref.WeakReference

/**
 * The player on screen: the platform's own view and controls, over the service that plays.
 *
 * The screen holds no playback of its own. It connects to [PlaybackService]
 * as a controller and draws what that plays, so locking the phone, leaving for
 * picture-in-picture or turning it round never interrupts the picture. Leaving
 * the app while it plays shrinks it into picture-in-picture; going back closes
 * the player, and so does closing the small window.
 */
@OptIn(UnstableApi::class)
public class PlayerActivity : FragmentActivity() {
    private lateinit var view: PlayerView
    private var connecting: ListenableFuture<MediaController>? = null

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        if (PlayerSession.asked == null) {
            finish()

            return
        }

        view = PlayerView(this)
        view.keepScreenOn = true
        setContentView(view)
        PlayerSession.screen = WeakReference(this)

        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.S) {
            setPictureInPictureParams(pictureInPicture().setAutoEnterEnabled(true).build())
        }
    }

    override fun onStart() {
        super.onStart()

        val token = SessionToken(this, ComponentName(this, PlaybackService::class.java))
        val future = MediaController.Builder(this, token).buildAsync()

        connecting = future
        future.addListener(
            { if (!future.isCancelled) view.player = future.get() },
            MoreExecutors.directExecutor(),
        )
    }

    override fun onStop() {
        super.onStop()

        view.player = null
        connecting?.let(MediaController::releaseFuture)
        connecting = null
    }

    override fun onUserLeaveHint() {
        super.onUserLeaveHint()

        val playing = PlayerSession.state.stands == WherePlaybackStands.PLAYING

        if (Build.VERSION.SDK_INT < Build.VERSION_CODES.S && playing) {
            enterPictureInPictureMode(pictureInPicture().build())
        }
    }

    override fun onPictureInPictureModeChanged(
        isInPictureInPictureMode: Boolean,
        newConfig: Configuration,
    ) {
        super.onPictureInPictureModeChanged(isInPictureInPictureMode, newConfig)

        view.useController = !isInPictureInPictureMode

        // Leaving the small window for the full screen resumes the screen; leaving
        // it any other way is the member closing it.
        if (!isInPictureInPictureMode && !lifecycle.currentState.isAtLeast(Lifecycle.State.STARTED)) {
            finish()
        }
    }

    override fun onDestroy() {
        super.onDestroy()

        if (PlayerSession.screen?.get() === this) {
            PlayerSession.screen = null
        }

        // Going back, closing the picture-in-picture window and the app's
        // own close all finish the screen, and each of them closes the player.
        if (isFinishing) {
            PlayerSession.service?.close()
        }
    }

    private fun pictureInPicture(): PictureInPictureParams.Builder =
        PictureInPictureParams.Builder().setAspectRatio(Rational(WIDE, HIGH))

    private companion object {
        /** The shape the picture-in-picture window starts in. */
        const val WIDE = 16
        const val HIGH = 9
    }
}
