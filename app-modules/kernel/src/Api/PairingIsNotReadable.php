<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * The pairing material did not say what pairing material has to say.
 *
 * Raised rather than answered with, and that is the exception to this codebase's
 * usual preference: a refusal a caller must look at is a value, and this is not
 * one a caller can do anything with except stop. There is no half-paired stack
 * to carry on with — either the material parsed or there is nothing to pair to.
 *
 * `InvalidArgumentException` rather than `RuntimeException`, which is what every
 * other refusal here extends and is not merely convention: the analyser treats a
 * `RuntimeException` as checked, and every Pest test body is a closure — so the
 * runtime kind would make this refusal the one case no test could exercise. The
 * right parent is the honest one anyway. Material that does not parse is a bad
 * argument, not a fault that arose while running.
 *
 * The message names {@see HowItWasRead} because the remedy differs by route. A
 * scan that produced nonsense is a camera pointed at the wrong thing; the same
 * nonsense typed is a transcription error, and telling somebody to try again
 * when they have just typed sixty-four characters correctly is the screen this
 * distinction exists to prevent.
 */
final class PairingIsNotReadable extends InvalidArgumentException
{
    /** The payload was not the shape pairing material has. */
    public static function fromWhatWasRead(HowItWasRead $how): self
    {
        return new self(sprintf(
            'The pairing material read by %s did not carry an address and a fingerprint.',
            $how->value,
        ));
    }

    /**
     * It parsed, and one of the two halves was missing.
     *
     * Separate from the above because it says something different to whoever is
     * reading the log: the payload had the right shape and the wrong contents,
     * which is a stack that produced bad material rather than an operator who
     * read it badly.
     */
    public static function withoutIts(WhatPairingMaterialSays $half, HowItWasRead $how): self
    {
        return new self(sprintf(
            'The pairing material read by %s carried no %s.',
            $how->value,
            $half->value,
        ));
    }

    /**
     * It carried something the format does not define (`N1-R48`).
     *
     * The requirement says material must not carry a credential, and the
     * tempting enforcement is a list of names — `credential`, `token`,
     * `password` — with anything else waved through. That list is wrong the
     * first time somebody picks a name nobody thought of, and the failure is
     * silent: the app pairs happily, having been handed a secret out of band by
     * whoever produced the payload.
     *
     * So the format is closed instead. Three keys are defined and a fourth is
     * refused whatever it is called, which needs no list to stay current. A
     * stack that has something new to say says it by raising the
     * {@see WireVersion}, and a version this app does not know is a refusal
     * that names the real problem.
     */
    public static function carrying(string $key, HowItWasRead $how): self
    {
        return new self(sprintf(
            'The pairing material read by %s carried "%s", which pairing material does not define.',
            $how->value,
            $key,
        ));
    }
}
