<?php

declare(strict_types=1);

namespace Modules\Vault\Api;

use function array_key_exists;
use function count;
use function is_array;
use function is_int;
use function is_string;
use function json_decode;
use function json_encode;

use Lemonfiber\Native\Keeps;
use Lemonfiber\Native\WhenAValueMayBeRead;
use Modules\Kernel\Api\Forgotten;
use Modules\Kernel\Api\HowItStands;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Noted;
use Modules\Kernel\Api\Reading;
use Modules\Kernel\Api\Showing;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Standings;
use Modules\Vault\Internal\KeptInAShape;
use Modules\Vault\Internal\KeptUnder;
use Modules\Vault\Internal\TheRowsHeld;
use Modules\Vault\Internal\WhetherAnythingIsHeld;

/**
 * The word each stack's one line last said, kept in the platform's own store.
 *
 * **The keychain rather than a file, and for {@see PlatformStacks}' reason
 * rather than for secure storage's.** A session token is what that names, and
 * this is not one. What this is, is the sentence *this person's machine is
 * broken*, against a stack the same store already holds an address for — and a
 * file in the app's sandbox is readable by a backup, by a device that has been
 * rooted, and by whatever a restore puts it back onto. This package offers no
 * third option, and putting the quieter half of the same record somewhere
 * weaker would be a decision nobody would defend if asked.
 *
 * **One key for the whole record**, as the stack list is, and for the same
 * reason: the list reads every stack's word at once, and a key per stack would
 * mean enumerating a store that offers no enumeration.
 *
 * **The word and the moment, and nothing else.** The list wants the word and
 * the stack's own screen hears the rest for itself.
 *
 * **A shape number, because everything retained carries one**, and an
 * unrecognised shape is discarded rather than interpreted. Discarding costs a
 * row its word until the stack's screen is next opened, which draws the stack
 * as not known yet — a state the list already draws. That is a cheaper failure
 * than opening on a word assembled from a record nobody in this build wrote.
 */
final readonly class PlatformStandings implements Standings
{
    /** The shape this build writes, and the only one it reads. */
    private const int SHAPE = 1;

    public function __construct(private Keeps $store) {}

    public function lastKnownOf(StackId $stack): Showing
    {
        $record = $this->whatEachStackSaid()->rows;
        $under = $stack->stored();

        // Written out rather than coalesced: a `??` on a subscript folds
        // absent, present-and-null and present-and-the- wrong-type into one
        // answer, and the one it picks reads as *carry on*.
        if (! array_key_exists($under, $record) || ! is_array($record[$under])) {
            return Showing::waiting();
        }

        return $this->reading($record[$under]);
    }

    public function remember(StackId $stack, HowItStands $standing, Instant $at): Noted
    {
        $record = $this->whatEachStackSaid()->rows;
        $record[$stack->stored()] = ['standing' => $standing->value, 'at' => $at->epochSeconds()];

        return $this->kept($record) ? Noted::downAt($at) : Noted::notKept();
    }

    public function forgetEverything(): Forgotten
    {
        $held = count($this->whatEachStackSaid()->rows);

        return $this->store->forget(KeptUnder::Standings->value)->either(
            done: static fn(): Forgotten => Forgotten::rows($held),
            refused: static fn(): Forgotten => Forgotten::nothing(),
        );
    }

    public function forgetTheStack(StackId $stack): Forgotten
    {
        $record = $this->whatEachStackSaid()->rows;

        if (! array_key_exists($stack->stored(), $record)) {
            return Forgotten::nothing();
        }

        unset($record[$stack->stored()]);

        return $this->kept($record) ? Forgotten::rows(1) : Forgotten::nothing();
    }

    public function keepsAnythingOf(StackId $stack): bool
    {
        return $this->store->read(KeptUnder::Standings->value)->either(
            found: fn(string $written): WhetherAnythingIsHeld => array_key_exists($stack->stored(), $this->rowsIn(json_decode($written, associative: true)))
                ? WhetherAnythingIsHeld::itIs()
                : WhetherAnythingIsHeld::itIsNot(),
            nothing: static fn(): WhetherAnythingIsHeld => WhetherAnythingIsHeld::itIsNot(),
            refused: static fn(): WhetherAnythingIsHeld => WhetherAnythingIsHeld::itIs(),
        )->held;
    }

    /**
     * Write the record down in place of the one before, and say whether it was.
     *
     * Which of the two refusals it was is deliberately not carried. A word
     * that could not be written down costs the list one row's word until
     * the stack's screen hears it again, so there is nothing for an
     * operator to do differently about a store with no keystore than about
     * a store that would not open.
     *
     * @param array<mixed> $record
     */
    private function kept(array $record): bool
    {
        $written = json_encode(KeptInAShape::written(self::SHAPE, ['standings' => $record]));

        if ($written === false) {
            return false;
        }

        return $this->store->keep(KeptUnder::Standings->value, $written, WhenAValueMayBeRead::WhileUnlocked)->either(
            done: static fn(): WhetherAnythingIsHeld => WhetherAnythingIsHeld::itIs(),
            refused: static fn(): WhetherAnythingIsHeld => WhetherAnythingIsHeld::itIsNot(),
        )->held;
    }

    /**
     * One stack's row, read back as the retained reading it is.
     *
     * Always `retained`, never `live`. Everything here came out of a store
     * rather than off a stack, so nothing this adapter answers can stand as the
     * confirmation of an action.
     *
     * @param array<mixed> $row
     */
    private function reading(array $row): Showing
    {
        $standing = $this->standingIn($row);
        $at = $this->momentIn($row);

        if (! $standing instanceof HowItStands || $at === null) {
            return Showing::waiting();
        }

        return Showing::holding(Reading::retained($standing, Instant::atEpochSeconds($at)));
    }

    /**
     * The word a row holds, where it holds one this build knows.
     *
     * A word this build does not recognise reads as nothing held rather than as
     * a guess, which is discarding an unrecognised shape at the size of one
     * field.
     *
     * @param array<mixed> $row
     */
    private function standingIn(array $row): ?HowItStands
    {
        if (! array_key_exists('standing', $row) || ! is_string($row['standing'])) {
            return null;
        }

        return HowItStands::tryFrom($row['standing']);
    }

    /**
     * When a row says the word was heard, where it says so as a count of seconds.
     *
     * @param array<mixed> $row
     */
    private function momentIn(array $row): ?int
    {
        if (! array_key_exists('at', $row) || ! is_int($row['at'])) {
            return null;
        }

        return $row['at'];
    }

    /**
     * Every stack's row, or none where the store holds nothing this build wrote.
     *
     * A store that could not be asked reads as nothing held, which the list
     * already draws. This record is the quiet half, and the screens that must
     * not collapse the two refusals are the ones about pairing.
     */
    private function whatEachStackSaid(): TheRowsHeld
    {
        return $this->store->read(KeptUnder::Standings->value)->either(
            found: fn(string $written): TheRowsHeld => TheRowsHeld::of(
                $this->rowsIn(json_decode($written, associative: true)),
            ),
            nothing: static fn(): TheRowsHeld => TheRowsHeld::none(),
            refused: static fn(): TheRowsHeld => TheRowsHeld::none(),
        );
    }

    /**
     * The rows a decoded record holds, where it is a record this build wrote.
     *
     * Separated from reading the store because the two decide different things:
     * this one decides whether the shape is one this build understands, and the
     * caller decides what to do about a store that answered nothing.
     *
     * @return array<mixed>
     */
    private function rowsIn(mixed $record): array
    {
        if (! KeptInAShape::isIn($record, self::SHAPE)) {
            return [];
        }

        if (! array_key_exists('standings', $record) || ! is_array($record['standings'])) {
            return [];
        }

        return $record['standings'];
    }
}
