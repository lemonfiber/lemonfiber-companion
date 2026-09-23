<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Kernel\Api\StackId;
use Modules\Stacks\Api\AStacksScreen;

/**
 * Where the screens are that read what one machine keeps about itself.
 *
 * What it changed, where what it runs came from, what it sends and what it
 * will wake somebody for — four screens answering one kind of question, which
 * is what the machine does and keeps on its own account while nobody is
 * looking. Apart from {@see WhereAStackIs} because that is where the questions
 * change subject, and because that class had reached the twenty-method ceiling
 * `H3` refuses: one accessor there hands out this, and the next screen of this
 * kind costs it nothing.
 *
 * `Internal`, for {@see WhereAStackIs}' reason.
 */
final readonly class WhatItKeepsOfItself
{
    private function __construct(private StackId $stack) {}

    /** The screens of this kind for one stack this device holds. */
    public static function of(StackId $stack): self
    {
        return new self($stack);
    }

    /** What this machine has changed about itself. */
    public function record(): string
    {
        return AStacksScreen::Record->forTheStack($this->stack);
    }

    /** Where every service on this machine comes from. */
    public function origins(): string
    {
        return AStacksScreen::Origins->forTheStack($this->stack);
    }

    /** Everything that leaves this machine. */
    public function leaving(): string
    {
        return AStacksScreen::Leaving->forTheStack($this->stack);
    }

    /** What this machine will tell its operator about. */
    public function told(): string
    {
        return AStacksScreen::Told->forTheStack($this->stack);
    }
}
