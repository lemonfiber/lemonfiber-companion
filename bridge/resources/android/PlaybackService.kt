package app.lemonfiber.native

import android.Manifest
import android.content.Context
import android.content.pm.PackageManager
import android.net.Uri
import android.os.Handler
import android.os.Looper
import android.os.SystemClock
import androidx.annotation.OptIn
import androidx.media3.common.AudioAttributes
import androidx.media3.common.C
import androidx.media3.common.MediaItem
import androidx.media3.common.MediaMetadata
import androidx.media3.common.MimeTypes
import androidx.media3.common.PlaybackException
import androidx.media3.common.Player
import androidx.media3.common.TrackSelectionOverride
import androidx.media3.common.Tracks
import androidx.media3.common.util.UnstableApi
import androidx.media3.exoplayer.ExoPlayer
import androidx.media3.exoplayer.source.DefaultMediaSourceFactory
import androidx.media3.session.MediaSession
import androidx.media3.session.MediaSessionService

/**
 * The player itself, kept by a service so it plays on with the screen locked and in picture-in-picture.
 *
 * Media3 plays what [PlayerSession] holds through the one data source the door
 * allows ([DoorDataSource]), and the platform's media session gives it the lock
 * screen and the notification. What is decided here is what the platform does
 * not decide: which tracks to start with ([TrackRule]), when to report how far
 * it got and when to stop waiting ([PlaybackRule]), and why it stopped.
 *
 * **Only this app and the system connect to it.** The session is how the lock
 * screen and a headset's buttons reach the player, and any app may ask to
 * connect to one; [ControllerRule] lets in this app and the system and
 * refuses every other app, so none can control playback or read what plays.
 * The service is not exported, so no other app can bind to it either.
 *
 * **It never waits on a spinner.** A stall is waited out for as long as the
 * rule allows, and then playback stops and says the library is out of reach;
 * nothing buffers for ever and nothing is retried in a loop.
 */
@OptIn(UnstableApi::class)
public class PlaybackService : MediaSessionService() {
    private var session: MediaSession? = null
    private var player: ExoPlayer? = null
    private val ticking = Handler(Looper.getMainLooper())
    private var asked: WhatToPlay? = null
    private var stalledSince: Double? = null
    private var lastReported = 0.0
    private var tracksStarted = false
    private var readyTold = false

    override fun onCreate() {
        super.onCreate()

        val playing =
            ExoPlayer
                .Builder(this)
                .setAudioAttributes(
                    AudioAttributes
                        .Builder()
                        .setContentType(C.AUDIO_CONTENT_TYPE_MOVIE)
                        .setUsage(C.USAGE_MEDIA)
                        .build(),
                    true,
                ).setHandleAudioBecomingNoisy(true)
                .build()

        playing.addListener(Listening())
        player = playing
        session = MediaSession.Builder(this, playing).setCallback(OnlyTheAppAndTheSystem()).build()
        PlayerSession.service = this
        PlayerSession.asked?.let(::play)
    }

    override fun onGetSession(controllerInfo: MediaSession.ControllerInfo): MediaSession? =
        session?.takeIf { admits(this, controllerInfo) }

    override fun onDestroy() {
        val position = player?.let { seconds(it.currentPosition) } ?: 0.0

        ticking.removeCallbacksAndMessages(null)

        if (asked != null) {
            PlayerSession.extensions.tell(PlayerHappening.Closed(position))
        }

        session?.release()
        player?.release()
        session = null
        player = null
        PlayerSession.service = null
        PlayerSession.end(asked, position)
        asked = null

        super.onDestroy()
    }

    /** Play what the member asked for, from where they were, replacing anything playing. */
    internal fun play(request: WhatToPlay) {
        val playing = player ?: return
        val source =
            PlayerSession.extensions.source(request)
                ?: return stopBecause(WhyPlaybackStopped.REFUSED)
        val pinned = PinnedConnection(DoorTrust(request.door, request.pin))
        val through = DefaultMediaSourceFactory(DoorDataSource.Factory(request, pinned))

        asked = request
        stalledSince = null
        lastReported = now()
        tracksStarted = false
        readyTold = false
        ticking.removeCallbacksAndMessages(null)

        playing.setMediaSource(through.createMediaSource(item(request, source)), millis(request.startAt))
        playing.prepare()
        playing.playWhenReady = true
        ticking.post(::tick)
    }

    /** Stop playing and let the service go, once no screen is connected to it. */
    internal fun close() {
        player?.stop()
        stopSelf()
    }

    /** Carry out one thing the app asked of the player. */
    internal fun command(
        word: String,
        seconds: Double,
        track: String,
    ) {
        val playing = player ?: return

        when (word) {
            "play" -> playing.play()
            "pause" -> playing.pause()
            "seek" -> playing.seekTo(millis(seconds))
            "audio" -> PlayerTracks.choose(playing, C.TRACK_TYPE_AUDIO, track)
            "subtitle" ->
                PlayerTracks.choose(
                    playing,
                    C.TRACK_TYPE_TEXT,
                    track.takeIf { it != TrackRule.OFF },
                )
            else -> return
        }

        settle(playing, PlayerSession.state.stands)
    }

    /** Look at the stall and the position once a second. */
    private fun tick() {
        val playing = player ?: return
        val since = stalledSince

        if (since != null && PlaybackRule.givesUp(now() - since)) {
            return stopBecause(PlayerSession.whyItStopped() ?: WhyPlaybackStopped.UNREACHABLE)
        }

        report(playing, moved = false)
        ticking.postDelayed(::tick, TICK_MS)
    }

    /** Report the position where the rule says to. */
    private fun report(
        playing: Player,
        moved: Boolean,
    ) {
        if (!PlaybackRule.reportsProgress(now() - lastReported, playing.isPlaying, moved)) {
            return
        }

        val position = seconds(playing.currentPosition)

        lastReported = now()
        PlayerSession.extensions.tell(
            if (playing.isPlaying) PlayerHappening.Progressed(position) else PlayerHappening.Paused(position),
        )
        settle(playing, PlayerSession.state.stands)
    }

    /** Stop and say why. */
    private fun stopBecause(why: WhyPlaybackStopped) {
        if (PlayerSession.state.stands == WherePlaybackStands.STOPPED) {
            return
        }

        PlayerSession.stoppedBecause(why)
        ticking.removeCallbacksAndMessages(null)
        player?.pause()
        PlayerSession.extensions.tell(PlayerHappening.Stopped(why))

        val playing = player

        if (playing == null) {
            PlayerSession.settle(PlayerSession.state.copy(stands = WherePlaybackStands.STOPPED, why = why))
        } else {
            settle(playing, WherePlaybackStands.STOPPED)
        }
    }

    /** Move to a new state, read off the player. */
    private fun settle(
        playing: Player,
        stands: WherePlaybackStands,
    ) {
        val tracks = playing.currentTracks

        PlayerSession.settle(
            PlayerState(
                stands = stands,
                position = seconds(playing.currentPosition),
                duration = playing.duration.takeIf { it != C.TIME_UNSET }?.let(::seconds) ?: 0.0,
                audio = PlayerTracks.offered(tracks, C.TRACK_TYPE_AUDIO),
                subtitles = PlayerTracks.offered(tracks, C.TRACK_TYPE_TEXT),
                chosenAudio = PlayerTracks.chosen(tracks, C.TRACK_TYPE_AUDIO),
                chosenSubtitle = PlayerTracks.chosen(tracks, C.TRACK_TYPE_TEXT),
                why = if (stands == WherePlaybackStands.STOPPED) PlayerSession.whyItStopped() else null,
            ),
        )
    }

    /** Every connection to the session, let in only for this app and the system ([ControllerRule]). */
    private inner class OnlyTheAppAndTheSystem : MediaSession.Callback {
        override fun onConnect(
            session: MediaSession,
            controller: MediaSession.ControllerInfo,
        ): MediaSession.ConnectionResult =
            if (admits(this@PlaybackService, controller)) {
                super.onConnect(session, controller)
            } else {
                MediaSession.ConnectionResult.reject()
            }
    }

    /** What the player does when Media3 tells it something. */
    private inner class Listening : Player.Listener {
        override fun onPlaybackStateChanged(playbackState: Int) {
            val playing = player ?: return

            when (playbackState) {
                Player.STATE_BUFFERING -> {
                    stalledSince = stalledSince ?: now()
                    settle(playing, WherePlaybackStands.STALLED)
                }
                Player.STATE_READY -> ready(playing)
                Player.STATE_ENDED -> {
                    report(playing, moved = true)
                    PlayerSession.extensions.tell(PlayerHappening.Ended)
                    settle(playing, WherePlaybackStands.ENDED)
                }
                else -> Unit
            }
        }

        override fun onIsPlayingChanged(isPlaying: Boolean) {
            val playing = player ?: return

            if (playing.playbackState != Player.STATE_READY) {
                return
            }

            report(playing, moved = !isPlaying)
            settle(playing, if (isPlaying) WherePlaybackStands.PLAYING else WherePlaybackStands.PAUSED)
        }

        override fun onPositionDiscontinuity(
            oldPosition: Player.PositionInfo,
            newPosition: Player.PositionInfo,
            reason: Int,
        ) {
            val playing = player ?: return

            if (reason == Player.DISCONTINUITY_REASON_SEEK) {
                report(playing, moved = true)
            }
        }

        override fun onTracksChanged(tracks: Tracks) {
            val playing = player ?: return
            val request = asked ?: return

            if (!tracksStarted && !tracks.isEmpty) {
                tracksStarted = true
                PlayerTracks.start(playing, tracks, request)
            }

            settle(playing, PlayerSession.state.stands)
        }

        override fun onPlayerError(error: PlaybackException) {
            val why =
                when (error.errorCode) {
                    in DECODING -> WhyPlaybackStopped.UNSUPPORTED_FORMAT
                    else -> WhyPlaybackStopped.UNREACHABLE
                }

            stopBecause(PlayerSession.whyItStopped() ?: why)
        }

        private fun ready(playing: Player) {
            stalledSince = null

            if (!readyTold) {
                readyTold = true
                PlayerSession.extensions.tell(PlayerHappening.Ready(seconds(playing.duration)))
            }

            settle(
                playing,
                if (playing.isPlaying) WherePlaybackStands.PLAYING else WherePlaybackStands.PAUSED,
            )
        }
    }

    private companion object {
        /** Milliseconds in a second, as the player counts positions. */
        const val MILLIS_PER_SECOND = 1000.0

        /** How often the stall and the position are looked at, in milliseconds. */
        const val TICK_MS = 1000L

        /** The errors that mean the device could not decode or read what it was given. */
        val DECODING =
            setOf(
                PlaybackException.ERROR_CODE_DECODER_INIT_FAILED,
                PlaybackException.ERROR_CODE_DECODER_QUERY_FAILED,
                PlaybackException.ERROR_CODE_DECODING_FAILED,
                PlaybackException.ERROR_CODE_DECODING_FORMAT_UNSUPPORTED,
                PlaybackException.ERROR_CODE_DECODING_FORMAT_EXCEEDS_CAPABILITIES,
                PlaybackException.ERROR_CODE_PARSING_CONTAINER_UNSUPPORTED,
                PlaybackException.ERROR_CODE_PARSING_MANIFEST_UNSUPPORTED,
            )

        /** What to play, with every extra subtitle at the door beside it. */
        private fun item(
            request: WhatToPlay,
            source: PlayableSource,
        ): MediaItem {
            val subtitles =
                PlayerSession.extensions
                    .extraTracks(request)
                    .filter { it.kind == KindOfTrack.SUBTITLE }
                    .map {
                        MediaItem.SubtitleConfiguration
                            .Builder(Uri.parse(it.address))
                            .setMimeType(MimeTypes.TEXT_VTT)
                            .setLanguage(it.language)
                            .setLabel(it.label)
                            .build()
                    }

            return MediaItem
                .Builder()
                .setUri(source.address)
                .setMimeType(
                    when (source.kind) {
                        KindOfSource.HLS -> MimeTypes.APPLICATION_M3U8
                        KindOfSource.DASH -> MimeTypes.APPLICATION_MPD
                        KindOfSource.PROGRESSIVE -> null
                    },
                ).setSubtitleConfigurations(subtitles)
                .setMediaMetadata(MediaMetadata.Builder().setTitle(request.shown.title).build())
                .build()
        }

        /** Whether a controller asking to connect is this app or the system. */
        fun admits(
            context: Context,
            controller: MediaSession.ControllerInfo,
        ): Boolean =
            ControllerRule.admits(
                asker = controller.packageName,
                own = context.packageName,
                holdsMediaControl =
                    context.packageManager.checkPermission(
                        Manifest.permission.MEDIA_CONTENT_CONTROL,
                        controller.packageName,
                    ) == PackageManager.PERMISSION_GRANTED,
            )

        /** Now, in seconds since boot, which no clock change moves. */
        fun now(): Double = SystemClock.elapsedRealtime() / MILLIS_PER_SECOND

        fun seconds(millis: Long): Double = millis / MILLIS_PER_SECOND

        fun millis(seconds: Double): Long = (seconds * MILLIS_PER_SECOND).toLong()
    }
}

/** The tracks on the player, under the ids the rules know them by: group and index. */
internal object PlayerTracks {
    /** Every track of one type. */
    fun offered(
        tracks: Tracks,
        type: Int,
    ): List<OfferedTrack> =
        tracks.groups.withIndex().filter { it.value.type == type }.flatMap { (group, one) ->
            (0 until one.length).map { at ->
                val format = one.getTrackFormat(at)

                OfferedTrack(
                    id = "$group:$at",
                    language = format.language ?: "",
                    label = format.label ?: format.language ?: "",
                    isDefault = format.selectionFlags and C.SELECTION_FLAG_DEFAULT != 0,
                )
            }
        }

    /** The track of one type that is playing, or null. */
    fun chosen(
        tracks: Tracks,
        type: Int,
    ): String? =
        tracks.groups.withIndex().filter { it.value.type == type }.firstNotNullOfOrNull { (group, one) ->
            (0 until one.length).firstOrNull { one.isTrackSelected(it) }?.let { "$group:$it" }
        }

    /** Start with the sound and subtitles the member prefers. */
    fun start(
        playing: Player,
        tracks: Tracks,
        request: WhatToPlay,
    ) {
        val audio = TrackRule.audio(offered(tracks, C.TRACK_TYPE_AUDIO), request.shown.audio)
        val subtitle = TrackRule.subtitle(offered(tracks, C.TRACK_TYPE_TEXT), request.shown.subtitle)

        audio?.let { choose(playing, C.TRACK_TYPE_AUDIO, it) }
        choose(playing, C.TRACK_TYPE_TEXT, subtitle)
    }

    /** Play this track of this type, or none of the type where the id is null or names no track of it. */
    fun choose(
        playing: Player,
        type: Int,
        id: String?,
    ) {
        val parts = id?.split(":")?.mapNotNull { it.toIntOrNull() }?.takeIf { it.size == 2 }
        val group = parts?.let { playing.currentTracks.groups.getOrNull(it[0]) }?.takeIf { it.type == type }
        val at = parts?.get(1)?.takeIf { group != null && it in 0 until group.length }
        val parameters = playing.trackSelectionParameters.buildUpon()

        playing.trackSelectionParameters =
            if (group == null || at == null) {
                parameters.setTrackTypeDisabled(type, true).build()
            } else {
                parameters
                    .setTrackTypeDisabled(type, false)
                    .setOverrideForType(TrackSelectionOverride(group.mediaTrackGroup, at))
                    .build()
            }
    }
}
