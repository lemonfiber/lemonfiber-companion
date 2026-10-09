package app.lemonfiber.native

import android.net.Uri
import androidx.annotation.OptIn
import androidx.media3.common.C
import androidx.media3.common.util.UnstableApi
import androidx.media3.datasource.BaseDataSource
import androidx.media3.datasource.DataSource
import androidx.media3.datasource.DataSpec
import androidx.media3.datasource.HttpDataSource
import java.io.ByteArrayInputStream
import java.io.IOException
import java.io.InputStream
import java.net.HttpURLConnection
import javax.net.ssl.HttpsURLConnection

/**
 * Every byte the player plays, fetched from the door over a pinned connection.
 *
 * Media3 reads every address — the manifest, the playlists, the segments, the
 * keys, the subtitles and every range of a file played directly — through the
 * one factory this makes, so nothing the player fetches goes round it. Each
 * fetch is held to the door ([Door]), over a connection that admits only the
 * certificate the core stated ([PinnedConnection]); a redirect is followed only
 * to the same door, and an HLS playlist is read through [PlaylistRule] before
 * Media3 sees it, so a playlist naming anything elsewhere stops playback rather
 * than sending the player there.
 *
 * **The grant goes in a header and nowhere else**, and nothing is cached.
 *
 * What went wrong is kept for the screen to say: the pin refused, an address
 * off the door, or the door's status.
 */
@OptIn(UnstableApi::class)
public class DoorDataSource(
    private val asked: WhatToPlay,
    private val pinned: PinnedConnection,
) : BaseDataSource(true) {
    private var connection: HttpsURLConnection? = null
    private var body: InputStream? = null
    private var opened: Uri? = null
    private var left: Long = C.LENGTH_UNSET.toLong()

    /** What makes one of these for every fetch Media3 asks for. */
    public class Factory(
        private val asked: WhatToPlay,
        private val pinned: PinnedConnection,
    ) : DataSource.Factory {
        override fun createDataSource(): DataSource = DoorDataSource(asked, pinned)
    }

    override fun open(dataSpec: DataSpec): Long {
        transferInitializing(dataSpec)

        val address = dataSpec.uri.toString()
        val range =
            RangeRule.asked(
                dataSpec.position,
                dataSpec.length.takeIf { it != C.LENGTH_UNSET.toLong() },
            )
        val connected = connect(address, range, dataSpec)
        val status = connected.responseCode

        if (!RangeRule.admits(status, range != null)) {
            PlayerSession.stoppedBecause(PlaybackRule.why(status))
            connected.disconnect()

            throw HttpDataSource.InvalidResponseCodeException(
                status,
                null,
                null,
                emptyMap(),
                dataSpec,
                ByteArray(0),
            )
        }

        connection = connected
        opened = dataSpec.uri

        if (PlayerExtensions.kind(address) == KindOfSource.HLS) {
            val rewritten = playlist(connected, address, dataSpec)

            body = ByteArrayInputStream(rewritten)
            left = rewritten.size.toLong()
        } else {
            body = connected.inputStream
            left = connected.contentLengthLong.takeIf { it >= 0 } ?: C.LENGTH_UNSET.toLong()
        }

        transferStarted(dataSpec)

        return left
    }

    override fun read(
        buffer: ByteArray,
        offset: Int,
        length: Int,
    ): Int {
        val read = body?.read(buffer, offset, length)?.takeIf { it >= 0 } ?: C.RESULT_END_OF_INPUT

        if (read != C.RESULT_END_OF_INPUT) {
            bytesTransferred(read)
        }

        return read
    }

    override fun getUri(): Uri? = opened

    override fun close() {
        body?.close()
        connection?.disconnect()
        body = null
        connection = null

        if (opened != null) {
            opened = null
            transferEnded()
        }
    }

    /** Open a connection, following redirects only while they stay at the door. */
    private fun connect(
        address: String,
        range: String?,
        dataSpec: DataSpec,
    ): HttpsURLConnection {
        var at = address

        repeat(REDIRECTS_FOLLOWED) {
            val one = request(at, range, dataSpec)
            val next = one.getHeaderField("Location")

            if (one.responseCode !in REDIRECTS || next == null) {
                return one
            }

            one.disconnect()
            at = asked.door.resolve(next, at) ?: refuse(dataSpec)
        }

        return refuse(dataSpec)
    }

    /** One request to one address at the door. */
    private fun request(
        address: String,
        range: String?,
        dataSpec: DataSpec,
    ): HttpsURLConnection {
        // The address fetched is the one the door admitted, parsed once, so the
        // connection cannot go anywhere the check did not look.
        val admitted = asked.door.admitted(address) ?: refuse(dataSpec)
        val one = admitted.toURL().openConnection() as HttpsURLConnection
        one.sslSocketFactory = pinned.sockets
        one.hostnameVerifier = pinned.hosts
        one.instanceFollowRedirects = false
        one.useCaches = false
        one.connectTimeout = TIMEOUT_MS
        one.readTimeout = TIMEOUT_MS
        one.setRequestProperty(GRANT_HEADER, GRANT_PREFIX + asked.grant)
        range?.let { one.setRequestProperty("Range", it) }

        try {
            one.connect()
        } catch (failed: IOException) {
            PlayerSession.stoppedBecause(
                if (pinned.refusedAPin) WhyPlaybackStopped.PIN_MISMATCH else WhyPlaybackStopped.UNREACHABLE,
            )

            throw HttpDataSource.HttpDataSourceException(
                failed,
                dataSpec,
                androidx.media3.common.PlaybackException.ERROR_CODE_IO_NETWORK_CONNECTION_FAILED,
                HttpDataSource.HttpDataSourceException.TYPE_OPEN,
            )
        }

        return one
    }

    /** A whole playlist, read through the rule, or a refusal where it names anything off the door. */
    private fun playlist(
        connected: HttpURLConnection,
        address: String,
        dataSpec: DataSpec,
    ): ByteArray {
        val read = connected.inputStream.use { it.readBytes().toString(Charsets.UTF_8) }
        val rewritten = PlaylistRule(asked.door, Door.SCHEME).rewrite(read, address) ?: refuse(dataSpec)

        return rewritten.toByteArray(Charsets.UTF_8)
    }

    /** Stop: something named an address off the door. */
    private fun refuse(dataSpec: DataSpec): Nothing {
        PlayerSession.stoppedBecause(WhyPlaybackStopped.REFUSED)

        throw HttpDataSource.HttpDataSourceException(
            IOException("not at the door"),
            dataSpec,
            androidx.media3.common.PlaybackException.ERROR_CODE_IO_BAD_HTTP_STATUS,
            HttpDataSource.HttpDataSourceException.TYPE_OPEN,
        )
    }

    private companion object {
        /** The header the grant is carried in. */
        const val GRANT_HEADER = "Authorization"

        /** How the grant is written in it. */
        const val GRANT_PREFIX = "Bearer "

        /** How many redirects are followed before giving up. */
        const val REDIRECTS_FOLLOWED = 5

        /** The statuses a door redirects with. */
        val REDIRECTS = setOf(301, 302, 303, 307, 308)

        /** How long a connection or a read may wait, in milliseconds. */
        const val TIMEOUT_MS = 10_000
    }
}
