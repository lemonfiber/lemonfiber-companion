<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use function is_string;

use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Stacks;

/**
 * What a screen about one stack reads about the stacks this phone holds.
 *
 * Every screen about a stack is handed one, built by the container, and asks
 * it rather than the stacks themselves.
 */
final readonly class TheWayAround
{
    public function __construct(private Stacks $stacks) {}

    /**
     * The stack a screen is about, from the name its route carries, as this
     * phone has it paired.
     *
     * The route's parameter arrives untyped, and anything that is not text
     * names no stack.
     */
    public function stackNamed(mixed $named): Stack
    {
        return $this->stacks->configured()->stack(StackId::rememberedAs(is_string($named) ? $named : ''));
    }
}
