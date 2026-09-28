<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * One row of what stopped moving: an item, or one cause standing for several.
 *
 * The stack folds items that share a cause into one row naming the cause, with
 * how many it stands for. Twenty downloads stopped by a full disk are one thing
 * to fix, and this is that one thing rather than twenty. So the name
 * is an item's where the row stands for one and the cause's where it stands for
 * more, and the count is what tells them apart.
 *
 * **What blocked it is the service's own words**, carried as they were said,
 * or empty where the service said nothing. A permission denial read out of an
 * import log is the thing an operator can act on, and anything written in its
 * place here would be this app's guess.
 *
 * **How long is part of the row**, as the stack counted it. It is
 * what makes *stuck* a sentence an operator can weigh.
 */
final readonly class AStoppage
{
    private function __construct(
        private HowItStopped $how,
        private string $name,
        private int $items,
        private string $blocking,
        private HowLong $heldFor,
    ) {}

    /**
     * A row as the stack sent it.
     *
     * A blank name is refused because it is a row somebody is asked to act on
     * that says what about nothing. A row standing for no items is a count the
     * stack cannot have meant, and drawing it would put a number on the screen
     * nobody could believe; {@see HowLong} refuses less than no time for the
     * same reason.
     */
    public static function of(HowItStopped $how, string $name, int $items, string $blocking, int $heldFor): self
    {
        $named = trim($name);

        if ($named === '') {
            throw StoppageSaysNothing::whatStopped();
        }

        if ($items < 1) {
            throw StoppageSaysNothing::howMany($items);
        }

        return new self($how, $named, $items, trim($blocking), HowLong::ofSeconds($heldFor));
    }

    public function how(): HowItStopped
    {
        return $this->how;
    }

    /** The item's name, or where the row stands for several, the cause they share. */
    public function name(): string
    {
        return $this->name;
    }

    /** How many items this row stands for; one in the ordinary case. */
    public function items(): int
    {
        return $this->items;
    }

    /** What the service said was in the way, in its words, or empty where it said nothing. */
    public function blocking(): string
    {
        return $this->blocking;
    }

    /** How long it has been this way. */
    public function heldFor(): HowLong
    {
        return $this->heldFor;
    }
}
