<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * The key this phone holds to prove its operator to a stack, one per stack.
 *
 * Made in the phone's hardware, usable only after the face or fingerprint enrolled when it was
 * made, and never handed over: what crosses is its public half, the platform's attestation of where
 * it lives, and signatures. A new face or fingerprint makes it unusable, and signing then answers
 * {@see WhyTheKeySignedNothing::Gone}. Removing a stack from the phone throws its key away.
 */
interface HoldingAPhoneKey extends ForgetsAStack
{
    /** Where this phone could keep a key, or why it could not. */
    public function whereItCouldLive(): WhereThisPhoneCanKeepAKey;

    /** Make the key for this stack, replacing any held for it, or say why not. */
    public function madeFor(StackId $stack): APhoneKeyMade;

    /** Sign this text with the key held for this stack, once the operator shows their face or fingerprint. */
    public function signedFor(StackId $stack, ATextToSign $text): WhatSigningCameTo;

    /**
     * The name a key this phone makes is registered under: what the phone is called, and four
     * random hexadecimal characters that tell two of the same phone apart.
     */
    public function nameForANewKey(): APhoneKeysName;

    /** Throw away the key held for this stack, and say what that came to. */
    public function forgetFor(StackId $stack): WhatForgettingAPhoneKeyFound;
}
