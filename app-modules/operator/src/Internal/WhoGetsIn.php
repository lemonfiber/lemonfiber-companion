<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Kernel\Api\SomebodyInTheHousehold;
use Modules\Kernel\Api\StackId;
use Modules\Stacks\Api\AStacksScreen;

/**
 * Where the screens are that answer who gets in to one machine.
 *
 * What it holds to let services in, which app the household watches on,
 * where the household comes in, asking somebody in, connecting their device,
 * and taking somebody out.
 * Apart from {@see WhereAStackIs} for {@see WhatItKeepsOfItself}'s reason: one
 * accessor there hands out this, and the next screen of this kind costs it
 * nothing.
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

    /** Where somebody is asked in. */
    public function invite(): string
    {
        return AStacksScreen::Invite->forTheStack($this->stack);
    }

    /**
     * Where one member is taken out of the household, by the name their account is held under.
     *
     * Text on the way in, because a template holds the names as text, and a
     * {@see SomebodyInTheHousehold} on the way out, which refuses a blank.
     */
    public function takingOut(string $named): string
    {
        return AStacksScreen::TakeOut->forTheStacksMember($this->stack, SomebodyInTheHousehold::called($named));
    }

    /**
     * Where somebody is asked in with their name already typed.
     *
     * Text on the way in, for {@see self::takingOut()}'s reason.
     */
    public function inviting(string $named): string
    {
        return AStacksScreen::InviteNamed->forTheStacksMember($this->stack, SomebodyInTheHousehold::called($named));
    }

    /**
     * Where one member's device is connected, by the name their account is held under.
     *
     * Text on the way in, for {@see self::takingOut()}'s reason.
     */
    public function connecting(string $named): string
    {
        return AStacksScreen::Device->forTheStacksMember($this->stack, SomebodyInTheHousehold::called($named));
    }
}
