<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * An identifier off one of the core's notification decisions.
 *
 * Deliberately not a {@see Code}, though both wrap the same kind of word and
 * both are searched for the same way. `N4-R11` says this app raises no alert of
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
 * which of them to raise — which is the half of `N4-R11` a type can keep.
 *
 * **Nothing builds one from the wire yet**, and that is a gap rather than an
 * oversight: the contract carries alert *preferences* and no decision this app
 * could render. `N1-R17` has the work stop there rather than approximate it
 * from a neighbouring endpoint, so {@see self::toSay()} is where that reading
 * will arrive when the contract carries it, and nothing invents one meanwhile.
 */
final readonly class WhatTheCoreDecided
{
    private function __construct(private string $code) {}

    /**
     * The one place a string becomes something this app may raise an alert for.
     *
     * Blank is refused for {@see Code::of()}'s reason: it is what a screen
     * shows beside an alert and what somebody searches for afterwards, and an
     * empty one reads as a rendering fault rather than as a core that sent
     * nothing.
     */
    public static function toSay(string $code): self
    {
        $trimmed = trim($code);

        if ($trimmed === '') {
            throw CodeIsBlank::inAnAlert();
        }

        return new self($trimmed);
    }

    /** The identifier, for keying the words and for an operator to search. */
    public function shown(): string
    {
        return $this->code;
    }
}
