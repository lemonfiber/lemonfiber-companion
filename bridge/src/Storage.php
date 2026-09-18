<?php

declare(strict_types=1);

namespace Lemonfiber\Native;

use function array_key_exists;
use function is_array;
use function is_string;
use function json_decode;
use function json_encode;
use function nativephp_call;

/**
 * The device's own secure store, as this application is willing to use one.
 *
 * The PHP face of lemonfiber's own storage. Three calls: keep a value, read one,
 * forget one. Every session this application holds and every stack it is paired
 * with goes through here and nowhere else.
 *
 * **Reading answers three things rather than two.** *Found*, *there is no such
 * key*, and *the store could not be asked*. The last two arrive as the same
 * emptiness from anything that returns a nullable string, and they are opposite
 * answers — a launch reading a refusal as an empty store offers to pair a
 * machine that is already paired. {@see WasRead} is the shape that makes
 * skipping that distinction impossible rather than merely discouraged.
 *
 * **When a value may be read again is asked for and answered back.** iOS makes
 * the choice unavoidable and Android has no equivalent knob, so a caller states
 * what it wants and the answer states what it got. Those differ on Android,
 * whose encrypted store is readable whenever the application can run — and a
 * facade that echoed the request back would be reporting a promise nobody kept.
 *
 * **Off a handset every call reports the least it can.** Nothing was kept,
 * nothing was found, and the store would not open. That is honest rather than
 * convenient: a stand-in claiming a session would make a test about resuming one
 * pass on a machine with nowhere to keep it.
 */
final readonly class Storage
{
    /** The word the store answers a successful write with. */
    private const string KEPT = 'kept';

    /** The word the store answers a successful read with. */
    private const string FOUND = 'found';

    /** The word the store answers where it holds no such key. */
    private const string NOTHING = 'nothing';

    /** The word the store answers a successful removal with. */
    private const string FORGOTTEN = 'forgotten';

    /**
     * Keep one value, or say why not.
     *
     * @param string $key   what this application calls the value. Never logged
     *                      by either native half, because a key can name a
     *                      stack and a stack is somebody's home.
     * @param string $value the secret itself.
     * @param WhenAValueMayBeRead $when the moment it may be decrypted again.
     */
    public function keep(string $key, string $value, WhenAValueMayBeRead $when): Wrote
    {
        $said = $this->answering(Call::Keep, [
            'key' => $key,
            'value' => $value,
            'readable' => $when->value,
        ]);

        return $this->wordUnder($said, 'outcome') === self::KEPT
            ? Wrote::done(WhenAValueMayBeRead::orTheNarrowest($this->wordUnder($said, 'readable')))
            : Wrote::refused(WhyNothingWasKept::orTheStoreWouldNotOpen($this->wordUnder($said, 'because')));
    }

    /** Read one value, or say there is none, or say nobody could be asked. */
    public function read(string $key): WasRead
    {
        $said = $this->answering(Call::Kept, ['key' => $key]);
        $outcome = $this->wordUnder($said, 'outcome');

        if ($outcome === self::FOUND) {
            return WasRead::found($this->wordUnder($said, 'value') ?? '');
        }

        return $outcome === self::NOTHING
            ? WasRead::nothing()
            : WasRead::refused(WhyNothingWasKept::orTheStoreWouldNotOpen($this->wordUnder($said, 'because')));
    }

    /**
     * Forget one value.
     *
     * Forgetting a key that was never kept is done rather than an error. It is
     * the ordinary case after a refused write, and getting rid of a session is
     * the one operation that must always work — including on the device where
     * keeping it did not.
     */
    public function forget(string $key): Wrote
    {
        $said = $this->answering(Call::Forget, ['key' => $key]);

        return $this->wordUnder($said, 'outcome') === self::FORGOTTEN
            ? Wrote::done(WhenAValueMayBeRead::WhileUnlocked)
            : Wrote::refused(WhyNothingWasKept::orTheStoreWouldNotOpen($this->wordUnder($said, 'because')));
    }

    /**
     * One word out of an answer, or nothing where there is no word there.
     *
     * Asked for rather than defaulted. `??` on a decoded answer folds absent,
     * present-and-null and present-and-the-wrong-type into one value, and the
     * callers above have a different thing to do about *nobody answered* than
     * about *answered with something unexpected* (`C9`).
     */
    private function wordUnder(mixed $said, string $key): ?string
    {
        if (! is_array($said) || ! array_key_exists($key, $said) || ! is_string($said[$key])) {
            return null;
        }

        return $said[$key];
    }

    /**
     * One bridge call, decoded.
     *
     * Everything that is not an answer — no device, an unparsable reply, a
     * function the router has never heard of — arrives here as something that
     * is not an array with an `outcome` in it, and every caller above reads
     * that as the least it could mean.
     *
     * @param array<string, string> $with
     */
    private function answering(Call $function, array $with): mixed
    {
        return json_decode(
            (string) nativephp_call($function->value, (string) json_encode($with)),
            associative: true,
        );
    }
}
