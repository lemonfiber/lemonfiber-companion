package app.lemonfiber.native

import java.security.cert.CertificateException
import java.security.cert.X509Certificate
import javax.net.ssl.HostnameVerifier
import javax.net.ssl.SSLContext
import javax.net.ssl.SSLSocketFactory
import javax.net.ssl.X509TrustManager

/**
 * The one way the player opens a connection to the door: pinned, and nowhere else.
 *
 * Two checks, both answered by [DoorTrust]. The trust manager admits a
 * handshake only where the leaf certificate is the one the core stated, and
 * the hostname verifier admits the connection only where it is to the door's
 * host and port and presented that same certificate. **The platform's trust
 * store is never asked**: what it thinks of a certificate on a house's own
 * network is not the question.
 *
 * A refusal is remembered, so the screen can say the door was not the house's
 * rather than that it did not answer.
 */
public class PinnedConnection(
    private val trust: DoorTrust,
) {
    /** Whether a connection was refused for presenting another certificate. */
    @Volatile
    public var refusedAPin: Boolean = false
        private set

    /** The socket factory every connection to the door is opened with. */
    public val sockets: SSLSocketFactory =
        SSLContext.getInstance(
            PROTOCOL,
        ).apply { init(null, arrayOf(TheDoorsCertificate()), null) }.socketFactory

    /** The hostname verifier every connection to the door is checked with. */
    public val hosts: HostnameVerifier =
        HostnameVerifier { host, session ->
            val leaf = session.peerCertificates.firstOrNull()?.encoded
            val admitted = trust.admits(leaf, host, session.peerPort)

            if (!admitted) {
                refusedAPin = true
            }

            admitted
        }

    /** A trust manager that knows one certificate. */
    private inner class TheDoorsCertificate : X509TrustManager {
        override fun checkServerTrusted(
            chain: Array<out X509Certificate>?,
            authType: String?,
        ) {
            val leaf = chain?.firstOrNull()?.encoded

            if (leaf == null || !trust.pin.admits(leaf)) {
                refusedAPin = true

                throw CertificateException("not the door's certificate")
            }
        }

        override fun checkClientTrusted(
            chain: Array<out X509Certificate>?,
            authType: String?,
        ): Unit = throw CertificateException("the player presents no certificate of its own")

        override fun getAcceptedIssuers(): Array<X509Certificate> = emptyArray()
    }

    private companion object {
        /** The protocol family the connection is made in; the platform picks the version. */
        const val PROTOCOL = "TLS"
    }
}
