<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use function array_key_exists;
use function is_string;

/**
 * What a handset's secure store would answer, for a bridge with no handset.
 *
 * Scripted into `nativephp/mobile`'s `FakeBridge` so the adapter above it runs
 * the real call — the function name from the manifest, the JSON out, the JSON
 * back — rather than a parallel path built to resemble it. {@see
 * ACameraOnAHandset} is the same move at the camera, and without one of these
 * {@see \Lemonfiber\Native\Storage} is a file nothing executes: there is no
 * keychain behind a PHP process on a laptop, so the only way to hold it to the
 * same assertions as the fake above it is to put a device under it.
 *
 * **The words are written out rather than derived from the enums.** A stand-in
 * and an adapter sharing one mapping agree with themselves, and a word wrong on
 * both sides is a word neither notices. These are the ones `StorageRule.kt` and
 * `StorageFunctions.swift` answer with.
 *
 * **Two platforms rather than one store.** iOS is told which moment to file an
 * item under, files it under that one, and answers back what it was asked for.
 * Android's encrypted store is readable whenever the application can run and
 * has no equivalent knob, so its half answers `after_first_unlock` to every
 * caller whatever they asked for. That disagreement is the whole reason the
 * answer carries a moment at all, and a store that could only be the first of
 * the two would make the second unreachable from any test.
 */
final class AStoreOnAHandset
{
    /** The narrowest moment, which is what either half answers to a word it does not know. */
    private const string THE_NARROWEST = 'while_unlocked';

    /** The moment Android's store gives everything it holds. */
    private const string WHENEVER_THE_APP_RUNS = 'after_first_unlock';

    /** @var array<string, string> */
    private array $held = [];

    private function __construct(
        private readonly ?string $refusing,
        private readonly ?string $grants,
    ) {}

    /**
     * A store that works and files each value under the moment it was asked for.
     *
     * What iOS does: the accessibility is an attribute of the item, so the
     * caller's choice is the one the Keychain holds and the one that comes back.
     */
    public static function working(): self
    {
        return new self(refusing: null, grants: null);
    }

    /**
     * A store that works and gives everything the wider moment regardless.
     *
     * What Android does. The encrypted store is readable whenever the
     * application can run, so a caller asking for the narrower one is told it
     * got the wider — and a caller that assumed otherwise is holding a session
     * readable on a locked phone while believing it is not.
     */
    public static function thatCannotNarrow(): self
    {
        return new self(refusing: null, grants: self::WHENEVER_THE_APP_RUNS);
    }

    /** A device with no secure store at all. */
    public static function absent(): self
    {
        return new self(refusing: 'no_store_on_this_device', grants: null);
    }

    /** A store that is there and will not open. */
    public static function refusing(): self
    {
        return new self(refusing: 'store_would_not_open', grants: null);
    }

    /**
     * What the bridge answers when the store is asked to keep a value.
     *
     * @param  array<string, mixed>  $sent
     * @return array<string, string>
     */
    public function keep(array $sent): array
    {
        if (is_string($this->refusing)) {
            return ['outcome' => 'refused', 'because' => $this->refusing];
        }

        $this->held[$this->wordIn($sent, 'key')] = $this->wordIn($sent, 'value');

        return ['outcome' => 'kept', 'readable' => $this->grants ?? $this->asked($sent)];
    }

    /**
     * What it answers when asked what is under a key.
     *
     * Three outcomes rather than two, because the store that cannot be asked
     * and the key that is not there are opposite answers — and the probe the
     * adapter asks *is there a store at all* with is a read of a key nothing is
     * ever kept under, so it arrives here.
     *
     * @param  array<string, mixed>  $sent
     * @return array<string, string>
     */
    public function read(array $sent): array
    {
        if (is_string($this->refusing)) {
            return ['outcome' => 'refused', 'because' => $this->refusing];
        }

        $key = $this->wordIn($sent, 'key');

        return array_key_exists($key, $this->held)
            ? ['outcome' => 'found', 'value' => $this->held[$key]]
            : ['outcome' => 'nothing'];
    }

    /**
     * What it answers when asked to take a value out.
     *
     * A key that was never kept is `forgotten` rather than an error, which both
     * halves say in as many words: it is the ordinary case after a refused
     * write. A store that cannot be asked still refuses, because there is
     * nothing there to take anything out of — the caller for whom forgetting
     * must always work is the one that does not read this answer.
     *
     * @param  array<string, mixed>  $sent
     * @return array<string, string>
     */
    public function forget(array $sent): array
    {
        if (is_string($this->refusing)) {
            return ['outcome' => 'refused', 'because' => $this->refusing];
        }

        unset($this->held[$this->wordIn($sent, 'key')]);

        return ['outcome' => 'forgotten'];
    }

    /**
     * The moment this store was asked for, or the narrowest where it was asked
     * for nothing it knows.
     *
     * Both halves read the word this way. Widening on confusion is how a
     * session becomes readable on a locked phone, so an unrecognised word
     * narrows rather than defaults to whatever was convenient.
     *
     * @param  array<string, mixed>  $sent
     */
    private function asked(array $sent): string
    {
        $said = $this->wordIn($sent, 'readable');

        return $said === self::WHENEVER_THE_APP_RUNS ? $said : self::THE_NARROWEST;
    }

    /**
     * One word out of what was sent, or an empty string where there is none.
     *
     * @param  array<string, mixed>  $sent
     */
    private function wordIn(array $sent, string $named): string
    {
        $said = $sent[$named] ?? null;

        return is_string($said) ? $said : '';
    }
}
