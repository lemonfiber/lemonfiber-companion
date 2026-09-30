<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Illuminate\Contracts\Translation\Translator;

use function is_string;

use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Stacks;

/**
 * What a screen about one stack reads about the stacks this phone holds, and
 * what its menu is called.
 *
 * Every screen about a stack is handed one, built by the container, and asks
 * it rather than the stacks themselves.
 */
final readonly class TheWayAround
{
    private const string THE_MENU_IS_CALLED = 'navigation.menu.open';

    public function __construct(private Stacks $stacks, private Translator $catalogue) {}

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

    /** What a screen reader calls the control that opens the menu. */
    public function theMenuIsCalled(): string
    {
        $said = $this->catalogue->get(self::THE_MENU_IS_CALLED);

        return is_string($said) ? $said : self::THE_MENU_IS_CALLED;
    }
}
