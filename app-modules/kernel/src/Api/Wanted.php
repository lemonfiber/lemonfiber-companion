<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * One thing somebody in the household asked for, and where it stands.
 *
 * `N2-R11` says a request awaiting a decision is surfaced with enough to decide
 * on, and `N2` calls approving one from a phone the case the whole surface
 * justifies itself with. What *enough to decide on* means is settled by `D7-R3`, `D7-R4` and `D7-R7`
 * rather than by a screen: who asked, what for, roughly how big it is, and
 * whether that size was measured or guessed.
 *
 * **The size is carried with whether it was measured.** `D7-R3` wants the size
 * shown before a request is submitted and `D7-R4` wants an estimate labelled as
 * one — two requirements that are one fact, and a type carrying the number
 * alone would let a screen answer the first and fail the second without anybody
 * noticing. {@see Size} holds both together, so a screen showing a figure has
 * been handed the word for it.
 *
 * **The requester's name is here and `N4-R10` keeps it off a notification.**
 * Not in tension: a household member's name is exactly what an operator needs
 * in order to decide, and exactly what must not appear on a lock screen. The
 * requirement is about where it is shown, and this is what a screen the
 * operator has unlocked reads.
 *
 * **Not every request is waiting.** The wire carries ones that were declined,
 * that failed, that are being fetched and that have arrived, because a
 * household screen shows a member's history. {@see Waiting} says which, and a
 * screen offering to approve something already here would be offering to do
 * nothing.
 *
 * Named for what the household member wants rather than for the record of it,
 * because {@see Asked} is already the answer to a different question — whether
 * this app may ask the device for a permission again.
 */
final readonly class Wanted
{
    private function __construct(
        private int $number,
        private string $by,
        private string $forWhat,
        private Size $size,
        private Waiting $standing,
    ) {}

    /**
     * The one place a request on the wire becomes one this app can show.
     *
     * The title is refused when blank for {@see Finding}'s reason: a row an
     * operator is being asked to approve, saying nothing about what they would
     * be approving, is a decision nobody can make. The requester is refused for
     * the same reason one step along — *somebody* asked for this is not enough
     * to decide on, and `D7-R7` has a decline reach them by name.
     */
    public static function of(
        int $number,
        string $by,
        string $forWhat,
        Size $size,
        Waiting $standing,
    ): self {
        $asker = trim($by);
        $title = trim($forWhat);

        if ($asker === '') {
            throw RequestHasNobodyBehindIt::atNumber($number);
        }

        if ($title === '') {
            throw RequestHasNobodyBehindIt::forNothing($number, $asker);
        }

        return new self($number, $asker, $title, $size, $standing);
    }

    /**
     * What the stack calls this request, for naming it when acting on it.
     *
     * Not shown. It is how an approval says which request it is about, and an
     * operator reading a number has learned nothing they can decide from.
     */
    public function number(): int
    {
        return $this->number;
    }

    /** Who asked, which is half of what a decision is made from. */
    public function by(): string
    {
        return $this->by;
    }

    /** What they asked for, in the words the stack holds. */
    public function forWhat(): string
    {
        return $this->forWhat;
    }

    /** How big it is, and whether anybody measured (`D7-R3`, `D7-R4`). */
    public function size(): Size
    {
        return $this->size;
    }

    /** Where it stands, which is what says whether a decision is wanted. */
    public function standing(): Waiting
    {
        return $this->standing;
    }
}
