<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * Something a removal could not take, what the machine said about it, and how to finish it by hand.
 */
final readonly class SomethingLeftBehind
{
    private function __construct(private string $name, private string $why, private string $byHand) {}

    /** That, left; a blank word is refused. */
    public static function named(string $name, string $why, string $byHand): self
    {
        foreach (['left.name' => $name, 'left.why' => $why, 'left.by_hand' => $byHand] as $field => $word) {
            if (trim($word) === '') {
                throw UninstallSaysNothing::about($field);
            }
        }

        return new self($name, $why, $byHand);
    }

    /** What is still there. */
    public function name(): string
    {
        return $this->name;
    }

    /** What the machine said about it, as it said it. */
    public function why(): string
    {
        return $this->why;
    }

    /** How to finish it by hand. */
    public function byHand(): string
    {
        return $this->byHand;
    }
}
