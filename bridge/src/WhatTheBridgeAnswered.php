<?php

declare(strict_types=1);

namespace Lemonfiber\Native;

use function array_key_exists;
use function is_array;
use function is_float;
use function is_int;
use function is_string;
use function json_decode;
use function json_encode;
use function nativephp_call;

/**
 * One call to the bridge, and what came back, decoded once.
 *
 * `nativephp_call()` is the bridge. On a handset it is a C extension; on a
 * development machine `nativephp/mobile` supplies a fallback that relays to a
 * connected device and answers `{"status":"error","code":"NO_DEVICE"}` when
 * there is none; under test a bound `FakeBridge` intercepts it in-process.
 * Every capability here asks it the same way and reads the same envelope, so
 * the asking and the reading are here once: an answer that is not a JSON object
 * is no answer, and a word or a flag that is not there, or is not a word or a
 * flag, is nothing — asked for rather than defaulted, because `??` cannot say
 * whether the bridge answered no or answered nothing at all.
 */
final readonly class WhatTheBridgeAnswered
{
    /** @param array<mixed>|null $said what came back, where it was an object */
    private function __construct(private ?array $said) {}

    /**
     * One call, carrying what it was handed.
     *
     * @param array<string, int|float|string|bool> $with
     */
    public static function to(Call $function, array $with = []): self
    {
        // A payload that will not encode is not sent.
        //
        // `json_encode` answers false for a string that is not valid UTF-8.
        // A `(string)` cast turns that false into `''`, which reaches the
        // device as a call carrying no parameters at all — indistinguishable
        // from one that meant to carry none. The device answers whatever it
        // answers to a call with everything missing, and the operator sees the
        // result of a request nobody made.
        //
        // Refused here instead, as the same nothing every other way of
        // not reaching the bridge produces: nobody answered, because nobody
        // was asked. It is also the only shape a test can hold: a cast
        // between two values nothing downstream can tell apart is a line
        // nothing can fail on.
        $payload = json_encode($with);

        if (! is_string($payload)) {
            return new self(null);
        }

        return self::decoded(nativephp_call($function->value, $payload));
    }

    /** One call that carries nothing, and says so with {@see Call::CARRIES_NOTHING}. */
    public static function toNothing(Call $function): self
    {
        return self::decoded(nativephp_call($function->value, Call::CARRIES_NOTHING));
    }

    /** The word the answer says what became of the call with, or nothing. */
    public function outcome(): ?string
    {
        return $this->word(WhatAnAnswerHolds::Outcome);
    }

    /** The word under this key, or nothing where there is no word there. */
    public function word(WhatAnAnswerHolds $key): ?string
    {
        $said = $this->under($key);

        return is_string($said) ? $said : null;
    }

    /** Whether the answer says yes under this key; anything else, or nothing, is no. */
    public function says(WhatAnAnswerHolds $key): bool
    {
        return $this->under($key) === true;
    }

    /** The number under this key, whole or not, or nothing where there is no number there. */
    public function number(WhatAnAnswerHolds $key): ?float
    {
        $said = $this->under($key);

        return is_int($said) || is_float($said) ? $said : null;
    }

    /**
     * The list under this key, or nothing where there is no list there.
     *
     * @return array<mixed>|null
     */
    public function listed(WhatAnAnswerHolds $key): ?array
    {
        $said = $this->under($key);

        return is_array($said) ? $said : null;
    }

    private static function decoded(mixed $said): self
    {
        if (! is_string($said)) {
            return new self(null);
        }

        $decoded = json_decode($said, associative: true);

        return new self(is_array($decoded) ? $decoded : null);
    }

    private function under(WhatAnAnswerHolds $key): mixed
    {
        return $this->said !== null && array_key_exists($key->value, $this->said) ? $this->said[$key->value] : null;
    }
}
