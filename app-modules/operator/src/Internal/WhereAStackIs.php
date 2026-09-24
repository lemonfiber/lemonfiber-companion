<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Kernel\Api\Form;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\StackId;
use Modules\Stacks\Api\AStacksScreen;

/**
 * Where one stack's screens live, which is the only place that knows.
 *
 * `HowThisStackIs::signInAt()` said the URI was built in PHP so it would have
 * one spelling. That was the right idea and it stopped half way: the spelling
 * left the templates and landed in six classes, each building
 * `/stacks/%s/sign-in` for itself, while the provider declared a seventh. A
 * rename would have had to be found in all of them, and the one that was missed
 * would be a button leading nowhere — which is the failure the original move
 * was made to prevent.
 *
 * The paths themselves are {@see AStacksScreen}'s, which the provider registers
 * from. This is the half that knows *which machine*; that one knows *which
 * screen*, and neither can be renamed without the other following.
 *
 * **One accessor per screen rather than one per destination on each screen.**
 * `HowThisStackIs` is the screen every other is reached from, so each new
 * destination arrived on it as another `somethingAreAt()` — three of them, on
 * the way to the twenty-method ceiling that is refused. Handing out one of
 * these instead means the next destination costs no method at all.
 *
 * **It takes what a pairing wrote down and gives it a name here.** Two screens
 * reach this holding only the identifier and nothing else, before anything has
 * looked the stack back up — and a type that refused them would have those two
 * building the URI by hand, which is the situation this exists to end. So the
 * string is admitted at {@see self::rememberedAs()} and becomes a
 * {@see StackId} there, which is the one place `D2` allows a primitive across
 * a boundary: a named constructor, where it is checked and given a name.
 *
 * `Internal` because where a screen lives is a detail of this module's own
 * surface; `E2`'s promise is that it can be renamed without reading another.
 */
final readonly class WhereAStackIs
{
    private function __construct(private StackId $stack) {}

    /** A stack this device holds. */
    public static function of(StackId $stack): self
    {
        return new self($stack);
    }

    /**
     * A stack named by what a pairing wrote down.
     *
     * For the two screens that finish a pairing and send the operator onwards
     * before anything has read the stack back. They hold the identifier and
     * nothing else, which is enough to say where to go.
     */
    public static function rememberedAs(string $stored): self
    {
        return new self(StackId::rememberedAs($stored));
    }

    /** How this machine is doing, which is what the app is for. */
    public function health(): string
    {
        return AStacksScreen::Health->forTheStack($this->stack);
    }

    /** Where a password is offered, and where a session that ended is renewed. */
    public function signIn(): string
    {
        return AStacksScreen::SignIn->forTheStack($this->stack);
    }

    /** What the household has asked this machine for. */
    public function requests(): string
    {
        return AStacksScreen::Requests->forTheStack($this->stack);
    }

    /**
     * What this machine says the person holding the session is owed.
     *
     * The one destination here that is not this module's own screen. It is
     * offered from the operator's surface because the operator's surface owns
     * every road into the application — a device opens on the list of stacks —
     * and a member's reading with nothing pointing at it is a screen nobody can
     * reach. Which of the two readings the person in front of it gets is the
     * core's answer rather than this module's: what comes back about one member
     * is one member's, and what comes back about a house is nobody's.
     */
    public function yours(): string
    {
        return AStacksScreen::Owed->forTheStack($this->stack);
    }

    /** What this machine would put right, stated before any yes. */
    public function repairs(): string
    {
        return AStacksScreen::Repairs->forTheStack($this->stack);
    }

    /** What has stopped coming in to this machine. */
    public function stuck(): string
    {
        return AStacksScreen::Stuck->forTheStack($this->stack);
    }

    /** What this machine is running, and the verbs about it. */
    public function services(): string
    {
        return AStacksScreen::Services->forTheStack($this->stack);
    }

    /** What this machine keeps running with nobody signed in. */
    public function keepsRunning(): string
    {
        return AStacksScreen::Hosting->forTheStack($this->stack);
    }

    /** Everything this machine is set to. */
    public function settings(): string
    {
        return AStacksScreen::Settings->forTheStack($this->stack);
    }

    /** Where this machine stands on being up to date. */
    public function updates(): string
    {
        return AStacksScreen::Updates->forTheStack($this->stack);
    }

    /** What is running here that this machine never declared. */
    public function elsewhere(): string
    {
        return AStacksScreen::Elsewhere->forTheStack($this->stack);
    }

    /**
     * Where what this machine keeps about itself is: what it changed, what it
     * runs, what it sends and what it wakes somebody for.
     */
    public function ofItself(): WhatItKeepsOfItself
    {
        return WhatItKeepsOfItself::of($this->stack);
    }

    /** What one of this machine's services has been saying. */
    public function logsOf(ServiceId $service): string
    {
        return AStacksScreen::Logs->forTheStacksService($this->stack, $service);
    }

    /** One service of this machine, and the verbs about it. */
    public function doingWith(ServiceId $service): string
    {
        return AStacksScreen::Doing->forTheStacksService($this->stack, $service);
    }

    /**
     * One whole form of this machine, and the verbs about it.
     *
     * The same screen as the one above, because what an operator is choosing
     * between is identical and only the name the stack is told differs.
     *
     * Text on the way in and a {@see Form} on the way out, which is the one
     * place on this class the conversion happens. A form reaches a screen as
     * the name the stack sent — the listing carries `list<string>` and the verb
     * has always been asked for by that name — and what puts those strings
     * there is {@see Form::named()}, so a blank cannot arrive by that road.
     * {@see Form::called()} refuses one anyway, which is the check the boundary
     * is entitled to rather than a raise waiting on a tap.
     */
    public function doingWithTheForm(string $named): string
    {
        return AStacksScreen::Doing->forTheStacksForm($this->stack, Form::called($named));
    }
}
