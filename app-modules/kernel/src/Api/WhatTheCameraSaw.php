<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What came back from pointing the camera at something: a payload, or a reason.
 *
 * The outcome {@see Scanning} answers with, and its shape is `C1`'s: a caller
 * has to open it to get at either half, so the refusal cannot be the one nobody
 * handled. The refusing arm is not an error path — an operator closing the
 * scanner is the ordinary way out of it — which is exactly why an exception
 * would be the wrong instrument.
 *
 *     $saw->either(
 *         read: fn (string $payload): Screen => $this->pair($payload),
 *         nothing: fn (WhyNothingWasScanned $why): Screen => $this->explain($why),
 *     );
 *
 * **It carries the payload as a string, deliberately.** What a camera read is
 * characters until something parses them, and the thing that parses them is
 * {@see \Modules\Connection\Api\WhatTheCodeSaysSoFar}, which already does it for
 * the typed road. A `Pairing` here would mean this type parsing — and then the
 * two roads would have two parsers, of which only one would stay tested.
 * `D2`'s "a primitive crosses into a module in one place" is satisfied by that
 * one place being the named constructor both roads go through.
 */
final readonly class WhatTheCameraSaw
{
    private function __construct(private ?string $payload, private ?WhyNothingWasScanned $why) {}

    /** The camera read something. Whether it is pairing material is not asked here. */
    public static function read(string $payload): self
    {
        return new self($payload, null);
    }

    /** It did not, and this is which of the three ways. */
    public static function nothing(WhyNothingWasScanned $why): self
    {
        return new self(null, $why);
    }

    /**
     * Say what happens either way, and get back what you built.
     *
     * There is no `wasRead()` and no `payload()`, for the reason {@see Outcome}
     * gives: a check-then-get pair puts the check where it can be forgotten, and
     * the forgotten one here is a screen that pairs against an empty string.
     *
     * @template TRead of object
     * @template TNothing of object
     *
     * @param Closure(string): TRead                  $read
     * @param Closure(WhyNothingWasScanned): TNothing $nothing
     *
     * @return TRead|TNothing
     */
    public function either(Closure $read, Closure $nothing): object
    {
        if ($this->why instanceof WhyNothingWasScanned) {
            return $nothing($this->why);
        }

        return $read($this->payload ?? '');
    }
}
