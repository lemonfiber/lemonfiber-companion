<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Kernel\Api\StackId;
use Modules\Stacks\Api\AStacksScreen;

/**
 * Where the screens are that answer who gets in to one machine.
 *
 * What it holds to let services in, which app the household watches on, and
 * where the household comes in. Apart from {@see WhereAStackIs} for
 * {@see WhatItKeepsOfItself}'s reason: one accessor there hands out this, and
 * the next screen of this kind costs it nothing.
 *
 * `Internal`, for {@see WhereAStackIs}' reason.
 */
final readonly class WhoGetsIn
{
    private function __construct(private StackId $stack) {}

    /** The screens of this kind for one stack this device holds. */
    public static function of(StackId $stack): self
    {
        return new self($stack);
    }

    /** The credentials this machine holds, and what uses each. */
    public function credentials(): string
    {
        return AStacksScreen::Credentials->forTheStack($this->stack);
    }

    /** Which app the household should watch on. */
    public function clients(): string
    {
        return AStacksScreen::Clients->forTheStack($this->stack);
    }

    /** Where the household comes in. */
    public function frontDoor(): string
    {
        return AStacksScreen::FrontDoor->forTheStack($this->stack);
    }
}
