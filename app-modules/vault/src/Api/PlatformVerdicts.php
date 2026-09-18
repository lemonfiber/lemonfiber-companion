<?php

declare(strict_types=1);

namespace Modules\Vault\Api;

use function array_key_exists;
use function is_array;
use function is_int;
use function is_string;
use function json_decode;
use function json_encode;

use Lemonfiber\Native\Keeps;
use Lemonfiber\Native\WhenAValueMayBeRead;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Noted;
use Modules\Kernel\Api\Overall;
use Modules\Kernel\Api\Reading;
use Modules\Kernel\Api\Showing;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Verdicts;
use Modules\Vault\Internal\TheRowsHeld;

/**
 * What each stack last came to, kept in the platform's own store.
 *
 * **The keychain rather than a file, and for {@see PlatformStacks}' reason
 * rather than for secure storage's.** A session token is what that names, and
 * this is not one. What this is, is the sentence *this person's machine is broken*,
 * against a stack the same store already holds an address for — and a file in
 * the app's sandbox is readable by a backup, by a device that has been rooted,
 * and by whatever a restore puts it back onto. This package offers no third
 * option, and putting the quieter half of the same record somewhere weaker
 * would be a decision nobody would defend if asked.
 *
 * **One key for the whole record**, as the stack list is, and for the same
 * reason: the opening screen reads every stack's verdict at once, and a key per
 * stack would mean enumerating a store that offers no enumeration.
 *
 * **The word and the moment, and nothing else.** Not the findings, not the
 * codes, not which service — the opening screen wants the verdict and the
 * findings screen fetches its own. Keeping them would be writing down a
 * description of what is wrong with somebody's machine to save a round trip
 * that happens anyway.
 *
 * **A shape number, because everything retained carries one**, and an
 * unrecognised shape
 * is discarded rather than interpreted. Discarding here costs
 * nothing at all: the opening screen shows the stack with no verdict yet, which
 * is a state it already draws, and the first ask refills it. That is a cheaper
 * failure than the one this avoids, which is opening on a verdict assembled
 * from a record nobody in this build wrote.
 */
final readonly class PlatformVerdicts implements Verdicts
{
    /** The one key the whole record lives under. */
    private const string UNDER = 'lemonfiber.verdicts';

    /** The shape this build writes, and the only one it reads. */
    private const int SHAPE = 1;

    public function __construct(private Keeps $store) {}

    public function lastKnownOf(StackId $stack): Showing
    {
        $record = $this->held();
        $under = $stack->stored();

        // Written out rather than coalesced, which `C9` refuses by name: a `??`
        // on a subscript folds absent, present-and-null and present-and-the-
        // wrong-type into one answer, and the one it picks reads as *carry on*.
        if (! array_key_exists($under, $record) || ! is_array($record[$under])) {
            return Showing::waiting();
        }

        return $this->reading($record[$under]);
    }

    public function remember(StackId $stack, Overall $overall, Instant $at): Noted
    {
        $record = $this->held();
        $record[$stack->stored()] = ['overall' => $overall->value, 'at' => $at->epochSeconds()];

        $written = json_encode(['shape' => self::SHAPE, 'verdicts' => $record]);

        if ($written === false) {
            return Noted::notKept();
        }

        // Which of the two refusals it was is deliberately not carried. A
        // verdict that could not be written down costs the opening screen one
        // word until the next ask refills it, so there is nothing for an
        // operator to do differently about a store with no keystore than about
        // a store that would not open — and a reason nobody acts on is a reason
        // that ends up on a screen for no purpose.
        return $this->store->keep(self::UNDER, $written, WhenAValueMayBeRead::WhileUnlocked)->either(
            done: static fn(): Noted => Noted::downAt($at),
            refused: static fn(): Noted => Noted::notKept(),
        );
    }

    /**
     * One stack's row, read back as the retained reading it is.
     *
     * Always `retained`, never `live`. Everything here came out of a store
     * rather than off a stack, which is what *retained, never live* means and
     * what keeps it true by construction: nothing this adapter
     * answers can stand as the confirmation of an action, because nothing it
     * answers was read in this session.
     *
     * @param array<mixed> $row
     */
    private function reading(array $row): Showing
    {
        $overall = $this->overallIn($row);
        $at = $this->momentIn($row);

        if (! $overall instanceof Overall || $at === null) {
            return Showing::waiting();
        }

        return Showing::holding(Reading::retained($overall, Instant::atEpochSeconds($at)));
    }

    /**
     * The word a row holds, where it holds one this build knows.
     *
     * A word this build does not recognise reads as nothing held rather than as
     * a guess, which is discarding an unrecognised shape at the size of one
     * field: the opening screen
     * shows the stack with no verdict yet and the first ask refills it.
     *
     * @param array<mixed> $row
     */
    private function overallIn(array $row): ?Overall
    {
        if (! array_key_exists('overall', $row) || ! is_string($row['overall'])) {
            return null;
        }

        return Overall::tryFrom($row['overall']);
    }

    /**
     * When a row says it was read, where it says so as a count of seconds.
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
     * Written out rather than coalesced through the subscripts, which `C9`
     * refuses by name: a `??` chain folds absent, present-and-null and
     * present-and-the-wrong-type into one answer, and the one it picks reads as
     * *carry on*.
     *
     * @return array<mixed>
     */
    private function held(): array
    {
        return $this->store->read(self::UNDER)->either(
            found: fn(string $written): TheRowsHeld => TheRowsHeld::of(
                $this->rowsIn(json_decode($written, associative: true)),
            ),
            nothing: static fn(): TheRowsHeld => TheRowsHeld::none(),
            // A store that could not be asked reads as no verdict yet, which is
            // a state the opening screen already draws and the first ask
            // refills. Nothing here is worth putting a failure in front of
            // somebody for: this record is the quiet half, and the screens that
            // must not collapse the two refusals are the ones about pairing.
            refused: static fn(): TheRowsHeld => TheRowsHeld::none(),
        )->rows;
    }

    /**
     * The rows a decoded record holds, where it is a record this build wrote.
     *
     * Separated from reading the store because the two decide different things:
     * this one decides whether the shape is one this build understands, and the
     * caller above decides what to do about a store that answered nothing —
     * which is the split {@see PlatformStacks} makes for the same reason.
     *
     * @return array<mixed>
     */
    private function rowsIn(mixed $record): array
    {
        if (! is_array($record) || ! array_key_exists('shape', $record) || $record['shape'] !== self::SHAPE) {
            return [];
        }

        if (! array_key_exists('verdicts', $record) || ! is_array($record['verdicts'])) {
            return [];
        }

        return $record['verdicts'];
    }
}
