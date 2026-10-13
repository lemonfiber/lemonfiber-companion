package app.lemonfiber.native

import android.content.pm.PackageManager
import android.os.Build
import android.os.Looper
import android.security.keystore.KeyGenParameterSpec
import android.security.keystore.KeyInfo
import android.security.keystore.KeyPermanentlyInvalidatedException
import android.security.keystore.KeyProperties
import android.security.keystore.StrongBoxUnavailableException
import android.util.Base64
import android.util.Log
import androidx.biometric.BiometricManager
import androidx.biometric.BiometricPrompt
import androidx.core.content.ContextCompat
import androidx.fragment.app.FragmentActivity
import com.nativephp.mobile.bridge.BridgeError
import com.nativephp.mobile.bridge.BridgeFunction
import java.security.GeneralSecurityException
import java.security.KeyFactory
import java.security.KeyPairGenerator
import java.security.KeyStore
import java.security.PrivateKey
import java.security.SecureRandom
import java.security.Signature
import java.security.SignatureException
import java.security.UnrecoverableKeyException
import java.security.spec.ECGenParameterSpec
import java.util.concurrent.ArrayBlockingQueue
import java.util.concurrent.TimeUnit

/**
 * The key that proves this phone's operator to a stack, on Android.
 *
 * Namespace: `Lemonfiber.PhoneKey.*`
 *
 * A P-256 key pair made in the phone's Keystore: in StrongBox where the phone has the
 * chip, in its trusted environment otherwise, and never in software, which the stack
 * refuses and this file throws away. Every use of the private half takes a strong
 * biometric and nothing else; a new face or fingerprint makes it unusable, and then it
 * is thrown away too. The private half never leaves the hardware: what crosses the
 * bridge is the public half, the attestation chain the hardware wrote for it, and a
 * signature.
 *
 * The decisions are [PhoneKeyRule]'s, held on a JVM. **Nothing a caller passed reaches a
 * log line**: not the alias, which names a stack, nor the text signed. The log carries the
 * outcome word and the reason word, both closed sets.
 */
public object PhoneKeyFunctions {
    /** What this plugin's log lines are tagged with. */
    private const val TAG = "Lemonfiber"

    /** The provider a key made in hardware lives under. */
    private const val KEYSTORE = "AndroidKeyStore"

    /** The curve the stack verifies against. */
    private const val CURVE = "secp256r1"

    /** The signature the stack verifies: ECDSA over SHA-256, in DER. */
    private const val SIGNED_WITH = "SHA256withECDSA"

    /** The bytes an attestation is asked with; the stack reads the key off the certificate, not off these. */
    private const val CHALLENGE_BYTES = 32

    /** How long the bridge thread waits for the operator at the prompt. */
    private const val PATIENCE_MINUTES = 5L

    /** The word for where a key may live. */
    private const val CAN = "can"

    /** The word for a phone that may not keep one. */
    private const val CANNOT = "cannot"

    /** The word for a key that was made. */
    private const val MADE = "made"

    /** The word for a key that was not. */
    private const val NOT_MADE = "not_made"

    /** The word for a signature that was made. */
    private const val SIGNED = "signed"

    /** The word for one that was not. */
    private const val NOT_SIGNED = "not_signed"

    /** What the prompt is titled with where a caller sends no reason. */
    private const val PROVE_IT = "Prove it is you"

    /** Where a key may live on this phone, or why it may not. */
    private fun mayLive(activity: FragmentActivity): PhoneKeyRule.Either =
        PhoneKeyRule.mayLive(
            biometric = BiometricManager.from(activity).canAuthenticate(WhatUnlocks.BIOMETRIC_STRONG),
            strongBox =
                Build.VERSION.SDK_INT >= Build.VERSION_CODES.P &&
                    activity.packageManager.hasSystemFeature(PackageManager.FEATURE_STRONGBOX_KEYSTORE),
        )

    /** The alias a call named, or a refusal to guess at one. */
    private fun aliasIn(parameters: Map<String, Any>): String =
        parameters["alias"] as? String ?: throw BridgeError.InvalidParameters("alias is required")

    /** The Keystore, opened. */
    private fun keystore(): KeyStore = KeyStore.getInstance(KEYSTORE).apply { load(null) }

    /**
     * Throw the key under this alias away, whether or not it was there.
     *
     * @return thrown away, never there, or a key that may still be there
     */
    private fun thrownAway(alias: String): ThrownAway =
        try {
            val store = keystore()
            val heldBefore = store.containsAlias(alias)

            store.deleteEntry(alias)
            PhoneKeyRule.thrownAway(heldBefore, heldAfter = store.containsAlias(alias))
        } catch (failed: GeneralSecurityException) {
            Log.d(TAG, "phone key: not thrown away, ${failed.javaClass.simpleName}")
            ThrownAway.NOT_FORGOTTEN
        }

    /** What a key is made with: P-256 for signing, face or fingerprint each use, gone with a new one. */
    private fun spec(
        alias: String,
        inTheChip: Boolean,
    ): KeyGenParameterSpec {
        val challenge = ByteArray(CHALLENGE_BYTES).also { SecureRandom().nextBytes(it) }
        val builder =
            KeyGenParameterSpec
                .Builder(alias, KeyProperties.PURPOSE_SIGN)
                .setAlgorithmParameterSpec(ECGenParameterSpec(CURVE))
                .setDigests(KeyProperties.DIGEST_SHA256)
                .setUserAuthenticationRequired(true)
                .setInvalidatedByBiometricEnrollment(true)
                .setAttestationChallenge(challenge)

        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.R) {
            builder.setUserAuthenticationParameters(0, KeyProperties.AUTH_BIOMETRIC_STRONG)
        } else {
            @Suppress("DEPRECATION")
            builder.setUserAuthenticationValidityDurationSeconds(-1)
        }

        if (inTheChip && Build.VERSION.SDK_INT >= Build.VERSION_CODES.P) {
            builder.setIsStrongBoxBacked(true)
        }

        return builder.build()
    }

    /** Make the pair, in the chip where asked and it will, in the trusted environment otherwise. */
    private fun generated(
        alias: String,
        inTheChip: Boolean,
    ) {
        val generator = KeyPairGenerator.getInstance(KeyProperties.KEY_ALGORITHM_EC, KEYSTORE)

        try {
            generator.initialize(spec(alias, inTheChip))
            generator.generateKeyPair()
        } catch (refused: StrongBoxUnavailableException) {
            Log.d(TAG, "phone key: the chip refused, ${refused.javaClass.simpleName}")
            generator.initialize(spec(alias, inTheChip = false))
            generator.generateKeyPair()
        }
    }

    /** Where the key under this alias lives, as the Keystore reports it. */
    private fun securityLevelOf(alias: String): Int {
        val key = keystore().getKey(alias, null) as? PrivateKey ?: throw UnrecoverableKeyException(alias)
        val info = KeyFactory.getInstance(key.algorithm, KEYSTORE).getKeySpec(key, KeyInfo::class.java)

        return if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.S) {
            info.securityLevel
        } else {
            @Suppress("DEPRECATION")
            if (info.isInsideSecureHardware) {
                PhoneKeyRule.SECURITY_LEVEL_TRUSTED_ENVIRONMENT
            } else {
                PhoneKeyRule.SECURITY_LEVEL_SOFTWARE
            }
        }
    }

    /** The key's public half and its attestation chain, each certificate's DER in base64, its own first. */
    private fun made(
        alias: String,
        lives: WhereAPhoneKeyLives,
    ): Map<String, Any> {
        val chain = keystore().getCertificateChain(alias)

        if (chain == null || !PhoneKeyRule.attested(chain.size)) {
            thrownAway(alias)
            Log.d(TAG, "phone key: not made, ${WhyNoPhoneKey.UNATTESTED.word}")

            return Envelope.refusing(NOT_MADE, WhyNoPhoneKey.UNATTESTED.word).asAnswer()
        }

        Log.d(TAG, "phone key: made, ${lives.word}")

        return Envelope
            .of(
                MADE,
                mapOf(
                    "lives" to lives.word,
                    "public_key" to encoded(chain[0].publicKey.encoded),
                    "chain" to chain.map { encoded(it.encoded) },
                ),
            ).asAnswer()
    }

    /** A signature ready for the prompt, or why there is none. */
    private sealed interface Opened {
        /** Opened with the key, waiting for face or fingerprint. */
        data class Ready(
            /** The signature the prompt unlocks. */
            val signature: Signature,
        ) : Opened

        /** None, for this reason. */
        data class Not(
            /** Why. */
            val why: WhyNothingWasSigned,
        ) : Opened
    }

    /** Bytes as the bridge carries them. */
    private fun encoded(bytes: ByteArray): String = Base64.encodeToString(bytes, Base64.NO_WRAP)

    /**
     * `Lemonfiber.PhoneKey.Where` — where a key may live on this phone, or why it may not.
     *
     * Asks nothing of the operator and makes nothing.
     */
    public class Where(
        private val activity: FragmentActivity,
    ) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> =
            when (val may = mayLive(activity)) {
                is PhoneKeyRule.Either.Lives -> Envelope.of(CAN, mapOf("lives" to may.where.word)).asAnswer()
                is PhoneKeyRule.Either.Not -> Envelope.refusing(CANNOT, may.why.word).asAnswer()
            }
    }

    /**
     * `Lemonfiber.PhoneKey.Make` — make the key under this alias, replacing any there, or say why not.
     *
     * A key the Keystore made in software is thrown away and answered as such: the stack
     * would refuse it, and a key that could leave the hardware is not one this app keeps.
     */
    public class Make(
        private val activity: FragmentActivity,
    ) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val alias = aliasIn(parameters)
            val may = mayLive(activity)

            val refused =
                when {
                    may is PhoneKeyRule.Either.Not -> may.why
                    thrownAway(alias) == ThrownAway.NOT_FORGOTTEN -> WhyNoPhoneKey.FAILED
                    else -> null
                }

            if (refused != null) {
                return Envelope.refusing(NOT_MADE, refused.word).asAnswer()
            }

            return try {
                generated(
                    alias,
                    inTheChip = (may as PhoneKeyRule.Either.Lives).where == WhereAPhoneKeyLives.STRONGBOX,
                )
                val lives = PhoneKeyRule.madeIn(securityLevelOf(alias))

                if (lives == null) {
                    thrownAway(alias)
                    Log.d(TAG, "phone key: not made, ${WhyNoPhoneKey.IN_SOFTWARE.word}")

                    Envelope.refusing(NOT_MADE, WhyNoPhoneKey.IN_SOFTWARE.word).asAnswer()
                } else {
                    made(alias, lives)
                }
            } catch (failed: GeneralSecurityException) {
                thrownAway(alias)
                Log.d(TAG, "phone key: not made, ${failed.javaClass.simpleName}")

                Envelope.refusing(NOT_MADE, WhyNoPhoneKey.FAILED.word).asAnswer()
            }
        }
    }

    /**
     * `Lemonfiber.PhoneKey.Sign` — sign this text with the key under this alias, behind face or fingerprint.
     *
     * The bridge thread waits while the operator answers, as it does for the lock. A key
     * that is gone, or that a new face or fingerprint made unusable, is thrown away and
     * answered as `gone`, which the app answers by making another.
     */
    public class Sign(
        private val activity: FragmentActivity,
    ) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val alias = aliasIn(parameters)
            val text =
                parameters["text"] as? String ?: throw BridgeError.InvalidParameters("text is required")
            val reason = parameters["reason"] as? String ?: PROVE_IT
            val opened =
                if (Looper.myLooper() == Looper.getMainLooper()) {
                    Opened.Not(WhyNothingWasSigned.FAILED)
                } else {
                    opened(alias)
                }

            return when (opened) {
                is Opened.Ready -> prompted(opened.signature, text.toByteArray(Charsets.UTF_8), reason)
                is Opened.Not -> Envelope.refusing(NOT_SIGNED, opened.why.word).asAnswer()
            }
        }

        /** A signature opened with the key under this alias, waiting for the prompt, or why there is none. */
        private fun opened(alias: String): Opened =
            try {
                val key = keystore().getKey(alias, null) as? PrivateKey

                if (key == null) {
                    Opened.Not(WhyNothingWasSigned.GONE)
                } else {
                    Opened.Ready(Signature.getInstance(SIGNED_WITH).apply { initSign(key) })
                }
            } catch (invalidated: KeyPermanentlyInvalidatedException) {
                thrownAway(alias)
                Log.d(TAG, "phone key: not signed, ${invalidated.javaClass.simpleName}")

                Opened.Not(WhyNothingWasSigned.GONE)
            } catch (failed: GeneralSecurityException) {
                Log.d(TAG, "phone key: not signed, ${failed.javaClass.simpleName}")

                Opened.Not(WhyNothingWasSigned.FAILED)
            }

        /** Raise the prompt with the signature as its crypto object, and wait for what the operator did. */
        private fun prompted(
            signature: Signature,
            text: ByteArray,
            reason: String,
        ): Map<String, Any> {
            val answered = ArrayBlockingQueue<Map<String, Any>>(1)

            activity.runOnUiThread {
                BiometricPrompt(
                    activity,
                    ContextCompat.getMainExecutor(activity),
                    object : BiometricPrompt.AuthenticationCallback() {
                        override fun onAuthenticationSucceeded(result: BiometricPrompt.AuthenticationResult) {
                            answered.offer(signed(result.cryptoObject?.signature, text))
                        }

                        override fun onAuthenticationError(
                            code: Int,
                            message: CharSequence,
                        ) {
                            val why = PhoneKeyRule.whyNotSigned(code)
                            Log.d(TAG, "phone key: not signed, ${why.word}")
                            answered.offer(Envelope.refusing(NOT_SIGNED, why.word).asAnswer())
                        }
                    },
                ).authenticate(
                    BiometricPrompt.PromptInfo
                        .Builder()
                        .setTitle(reason)
                        .setAllowedAuthenticators(WhatUnlocks.BIOMETRIC_STRONG)
                        .setNegativeButtonText(activity.getString(android.R.string.cancel))
                        .build(),
                    BiometricPrompt.CryptoObject(signature),
                )
            }

            return answered.poll(PATIENCE_MINUTES, TimeUnit.MINUTES)
                ?: Envelope.refusing(NOT_SIGNED, WhyNothingWasSigned.CANCELLED.word).asAnswer()
        }

        /** The text signed with the signature the prompt unlocked, in DER as base64. */
        private fun signed(
            signature: Signature?,
            text: ByteArray,
        ): Map<String, Any> =
            try {
                val made = signature ?: throw SignatureException("the prompt unlocked no signature")
                made.update(text)
                Log.d(TAG, "phone key: signed")

                Envelope.of(SIGNED, mapOf("signature" to encoded(made.sign()))).asAnswer()
            } catch (failed: GeneralSecurityException) {
                Log.d(TAG, "phone key: not signed, ${failed.javaClass.simpleName}")

                Envelope.refusing(NOT_SIGNED, WhyNothingWasSigned.FAILED.word).asAnswer()
            }
    }

    /**
     * `Lemonfiber.PhoneKey.Forget` — throw the key under this alias away.
     *
     * A key that was never made answers `none_held` rather than a refusal: removing a stack
     * from the phone, or a key the stack refused, must always be able to end it. A key the
     * Keystore still holds afterwards is refused as `not_forgotten`, never answered as gone.
     * The activity is what the plugin's registration hands every function, and this one has
     * no use for it.
     */
    public class Forget(
        @Suppress("UnusedPrivateProperty") private val activity: FragmentActivity,
    ) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val came = thrownAway(aliasIn(parameters))

            Log.d(TAG, "phone key: ${came.word}")

            return if (came == ThrownAway.NOT_FORGOTTEN) {
                Envelope.refusing(came.word, WhyNoPhoneKey.FAILED.word).asAnswer()
            } else {
                Envelope.of(came.word).asAnswer()
            }
        }
    }
}
