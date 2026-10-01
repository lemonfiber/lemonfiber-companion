<?php

declare(strict_types=1);

namespace Bootstrap\Composition;

use function array_any;
use function array_values;

use Modules\Kernel\Api\ForgetsAStack;
use Modules\Kernel\Api\Forgotten;
use Modules\Kernel\Api\StackId;

/**
 * Every keeper of a stack, asked as one.
 *
 * In the order the composition root registers them, which puts the pairing
 * first: the stack leaves every list the moment it is let go of, whatever the
 * rest take.
 */
final readonly class EveryKeeperOfAStack implements ForgetsAStack
{
    /** @var list<ForgetsAStack> */
    private array $keepers;

    public function __construct(ForgetsAStack ...$keepers)
    {
        $this->keepers = array_values($keepers);
    }

    public function forgetTheStack(StackId $stack): Forgotten
    {
        $forgotten = Forgotten::nothing();

        foreach ($this->keepers as $keeper) {
            $forgotten = $forgotten->beside($keeper->forgetTheStack($stack));
        }

        return $forgotten;
    }

    public function keepsAnythingOf(StackId $stack): bool
    {
        return array_any($this->keepers, static fn(ForgetsAStack $keeper): bool => $keeper->keepsAnythingOf($stack));
    }
}
