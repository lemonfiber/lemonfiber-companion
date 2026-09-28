<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * A guard on the data location an operator asked for, naming the forms it guards.
 *
 * The forms are the ones the guard stops the moment the data location goes,
 * so they are what the operator is agreeing to have stopped.
 */
final readonly class AGuardAskedFor
{
    private function __construct(private Forms $forms) {}

    /** A guard for these forms. */
    public static function of(Forms $forms): self
    {
        return new self($forms);
    }

    /** The forms it would guard, and stop. */
    public function forms(): Forms
    {
        return $this->forms;
    }

    /**
     * lemonfiber's name for the action.
     *
     * Spelled here, once, for {@see TakingAnUpdate::asked()}'s reason: an
     * adapter that spelled it could spell any action a stack offers.
     */
    public function asked(): string
    {
        return 'watch';
    }
}
