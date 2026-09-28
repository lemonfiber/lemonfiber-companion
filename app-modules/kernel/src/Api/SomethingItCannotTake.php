<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * Something lemonfiber will not remove, why it is not lemonfiber's, and how to remove it by hand.
 *
 * **Listed whether or not it was found.** A survey that could not look is
 * not a machine without it, so the entry is there either way, and whether
 * this machine was found to have it is carried beside it.
 */
final readonly class SomethingItCannotTake
{
    private function __construct(private string $what, private string $why, private string $byHand, private bool $found) {}

    /** Something the survey found on this machine; a blank word is refused. */
    public static function found(string $what, string $why, string $byHand): self
    {
        self::refuseBlank($what, $why, $byHand);

        return new self($what, $why, $byHand, found: true);
    }

    /** Something the survey did not find, which is not the same as it not being there; a blank word is refused. */
    public static function notFound(string $what, string $why, string $byHand): self
    {
        self::refuseBlank($what, $why, $byHand);

        return new self($what, $why, $byHand, found: false);
    }

    /** What it is. */
    public function what(): string
    {
        return $this->what;
    }

    /** Why it is not lemonfiber's to take away. */
    public function why(): string
    {
        return $this->why;
    }

    /** How to remove it on this platform, as the operator would type or do it. */
    public function byHand(): string
    {
        return $this->byHand;
    }

    /** Whether this machine was found to have it. */
    public function wasFound(): bool
    {
        return $this->found;
    }

    /** Refuse any of the three words left blank. */
    private static function refuseBlank(string $what, string $why, string $byHand): void
    {
        foreach (['outside.what' => $what, 'outside.why' => $why, 'outside.by_hand' => $byHand] as $field => $word) {
            if (trim($word) === '') {
                throw UninstallSaysNothing::about($field);
            }
        }
    }
}
