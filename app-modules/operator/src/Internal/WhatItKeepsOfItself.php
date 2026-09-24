<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Kernel\Api\StackId;
use Modules\Stacks\Api\AStacksScreen;

/**
 * Where the screens are that read what one machine keeps about itself.
 *
 * What it changed, where what it runs came from, what it sends, what it will
 * wake somebody for and how it shares the line — screens answering one kind of
 * question, which
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

    /** How this machine shares its line with the household. */
    public function line(): string
    {
        return AStacksScreen::Line->forTheStack($this->stack);
    }

    /** What this machine keeps, where, and why, and the copies it holds. */
    public function keeps(): string
    {
        return AStacksScreen::Keeps->forTheStack($this->stack);
    }

    /** How full this machine is, and where the room went. */
    public function room(): string
    {
        return AStacksScreen::Room->forTheStack($this->stack);
    }

    /** Which version of lemonfiber this machine runs, and whether a newer one exists. */
    public function itself(): string
    {
        return AStacksScreen::Itself->forTheStack($this->stack);
    }

    /** What lemonfiber's words mean. */
    public function words(): string
    {
        return AStacksScreen::Words->forTheStack($this->stack);
    }
}
