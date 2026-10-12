<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function preg_match;
use function trim;

/**
 * An identifier off one of the core's notification decisions.
 *
 * Deliberately not a {@see Code}, though both wrap the same kind of word and
 * both are searched for the same way. This app raises no alert of
 * its own, and the only thing standing between it and one was that a
 * notification carries a code and no text — which is not enough, because this
 * app has codes. {@see Obstacle::code()} mints `COMPANION-NO-ANSWER` and the
 * rest of them, on this side, about a server that did not answer; and
 * {@see Problem::code()} carries what a stack said when it refused a request
 * the operator made. Both are `Code`, so both could be handed to
 * {@see Notification::fromTheCore()} and become an alert nobody in the core
 * decided to raise.
 *
 * Two types that cannot be substituted is what turns that from a sentence in a
 * docblock into a sentence that will not compile.
 *
 * **It answers no comparison with a `Code`, and takes none.** *Is this alert
 * about the problem I am looking at* is a real question and it is not this
 * type's: the core decides what is worth telling somebody about, and an app
 * that matched its own obstacles against the core's alerts would be deciding
 * which of them to raise — which is the half of that a type can keep.
 *
 * **Built from the wire from what an `alert` event carries**: its kind, which
 * a notification says, and its name, which a notification is filed under
 * (see {@see AnAlert}). {@see self::toSay()} is the one place either becomes
 * this type.
 */
final readonly class WhatTheCoreDecided
{
    /** The core's own form of a code: lower-case words, hyphenated where they are several, joined by dots. */
    private const string A_CODE = '/^[a-z][a-z0-9]*(?:-[a-z0-9]+)*(?:\.[a-z][a-z0-9]*(?:-[a-z0-9]+)*)*\z/';

    private function __construct(private string $code) {}

    /**
     * The one place a string becomes something this app may raise an alert for.
     *
     * Blank is refused for {@see Code::of()}'s reason: it is what a screen
     * shows beside an alert and what somebody searches for afterwards, and an
     * empty one reads as a rendering fault rather than as a core that sent
     * nothing.
     *
     * Anything not in the core's own form of a code is refused too, so a
     * sentence a stack chose never reaches a notification: what is shown
     * beside an alert is a code or nothing.
     */
    public static function toSay(string $code): self
    {
        $trimmed = trim($code);

        if ($trimmed === '') {
            throw CodeIsBlank::inAnAlert();
        }

        if (preg_match(self::A_CODE, $trimmed) !== 1) {
            throw CodeIsMisshapen::inAnAlert();
        }

        return new self($trimmed);
    }

    /** The identifier, for keying the words and for an operator to search. */
    public function shown(): string
    {
        return $this->code;
    }
}
