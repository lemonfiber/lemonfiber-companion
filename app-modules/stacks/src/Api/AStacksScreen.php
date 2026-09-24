<?php

declare(strict_types=1);

namespace Modules\Stacks\Api;

use Modules\Kernel\Api\Form;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\StackId;

use function str_contains;
use function str_replace;

/**
 * Every screen a stack has, and the one place each one's path is written.
 *
 * The provider registers these and {@see WhereAStackIs} builds from them, so
 * the pattern and the path that has to match it are the same string. Before
 * this they were not: the provider declared `/stacks/{stack}/repairs` and seven
 * other places spelled it again, and nothing compared the two.
 *
 * **That gap could not fail a test.** The screens' tests hold a *third* copy —
 * a test asserting `sprintf('/stacks/%s/requests', ...)` against a screen that
 * builds the same `sprintf` compares two spellings and never asks what was
 * registered. So renaming a route in the provider alone left the whole suite
 * green, the analyser green, the rules green, and every button on the hub
 * pointing at a path nothing serves. It surfaced as an operator tapping and
 * nothing happening, on a handset, at runtime.
 *
 * The registrations are unnamed — `Router::native()` takes a pattern and a
 * class and nothing else — so there is no `route()` to ask, which is why this
 * exists rather than a lookup.
 *
 * **In a capability module because two surfaces need it and neither may name
 * the other.** A stack's screens are rendered from both `Modules\Operator` and
 * `Modules\Household`: the operator reads the machine and the member reads what
 * they are owed by it, and each has to be able to send somebody to the other.
 * A surface may depend on a kernel, a design and a capability module and never
 * on a second surface, so a path spelled in both would be the one drift this
 * type cannot watch — the failure it was written to end, arriving by the only
 * road left open to it.
 *
 * `Api` rather than `Internal`, and that is the whole of the move: the two
 * surfaces reach it through this module's published surface, which is the only
 * way `E2` lets either of them reach anything here at all.
 */
enum AStacksScreen: string
{
    /** How this machine is doing, which is what the app is for. */
    case Health = '/stacks/{stack}';

    /** Where a password is offered, and a session that ended is renewed. */
    case SignIn = '/stacks/{stack}/sign-in';

    /** What the household has asked this machine for. */
    case Requests = '/stacks/{stack}/requests';

    /** What this machine would put right, stated before any yes. */
    case Repairs = '/stacks/{stack}/repairs';

    /** What has stopped coming in, which is the first of four. */
    case Stuck = '/stacks/{stack}/stuck';


    /** What this machine is running, as a list of rows. */
    case Services = '/stacks/{stack}/services';

    /** What this machine keeps running when nobody is signed in. */
    case Hosting = '/stacks/{stack}/keeps-running';

    /** Everything this machine is set to, as the machine itself lists it. */
    case Settings = '/stacks/{stack}/settings';

    /**
     * One thing this machine runs, and the verbs about it.
     *
     * A service or a whole form, which is the two granularities arriving
     * at one screen. The path says `{service}` for both because the name is all
     * either is: {@see Screens\WhatToDoWithThis}
     * reads a service first and a form second, which is the narrower reading
     * and the safer one where a stack has named a service after its form.
     */
    case Doing = '/stacks/{stack}/do/{service}';

    /** Where this machine stands on being up to date. */
    case Updates = '/stacks/{stack}/updates';

    /** What is running here that this machine's own configuration never declared. */
    case Elsewhere = '/stacks/{stack}/elsewhere';

    /** What one of this machine's services has been saying. */
    case Logs = '/stacks/{stack}/logs/{service}';

    /**
     * What this machine says the member holding the session is owed.
     *
     * The one case here a member's surface draws rather than an operator's,
     * and it is a case here rather than an enum of its own for the reason the
     * type carries: it is a screen under one machine, reached by naming that
     * machine, and a second enum spelling `/stacks/{stack}/` would be the
     * drift this exists to prevent with the placeholder in a different file.
     */
    case Owed = '/stacks/{stack}/yours';

    /**
     * What this machine says the member holding the session may watch.
     *
     * The second of the member's own screens, and here for the reason
     * {@see self::Owed} is. It is deliberately not under `yours`: what a
     * member may ask for and what they already have are two readings of two
     * endpoints, and one path covering both would be a screen having to
     * decide which the person meant.
     */
    case Shelf = '/stacks/{stack}/watch';

    /**
     * What this machine has changed about itself, and how far each could be put back.
     *
     * Beside what it is set to rather than under it: that screen is a setting
     * as it stands now, and this is how it came to stand there.
     */
    case Record = '/stacks/{stack}/record';

    /**
     * Where every service on this machine comes from.
     *
     * Beside the record: that screen is what was done, and this is what it was
     * done with.
     */
    case Origins = '/stacks/{stack}/origins';

    /**
     * Everything that leaves this machine: lemonfiber's own requests, and its
     * services'.
     */
    case Leaving = '/stacks/{stack}/leaving';

    /** What this machine will tell its operator about. */
    case Told = '/stacks/{stack}/told';

    /** How this machine shares its line with the household. */
    case Line = '/stacks/{stack}/line';

    /** What this machine keeps, where, and why, and the copies it holds. */
    case Keeps = '/stacks/{stack}/keeps';

    /** How full this machine is, and where the room went. */
    case Room = '/stacks/{stack}/room';

    /** Which version of lemonfiber this machine runs, and whether a newer one exists. */
    case Itself = '/stacks/{stack}/itself';

    /** What the router holds a machine under. */
    public const string NAMED = '{stack}';

    /** What it holds one of that machine's services under. */
    public const string ABOUT = '{service}';

    /**
     * This screen's path, for one machine.
     *
     * Built by replacing the router's own placeholder rather than by a second
     * format string, so there is no way for the pattern and the path to be
     * spelled differently — which is the whole of what this type is for.
     *
     * **It refuses a case that needs more than a machine.** A path still
     * carrying `{service}` resolves to nothing and would be a button that does
     * nothing on a handset — the exact failure this type was written to end,
     * arriving by a new road. {@see self::forTheStacksService()} is the one for
     * those, and a caller that used the wrong one finds out here rather than in
     * somebody's hand.
     */
    public function forTheStack(StackId $stack): string
    {
        if ($this->alsoNeedsAService()) {
            throw AScreenNeedsMoreThanAStack::toBeReached($this);
        }

        return str_replace(self::NAMED, $stack->stored(), $this->value);
    }

    /**
     * This screen's path, for one service on one machine.
     *
     * The other half, and it refuses the other mistake: a case with no
     * `{service}` in it would silently ignore the second argument, which is a
     * caller holding a service name the path does not carry and a screen that
     * opens on whatever it likes.
     */
    public function forTheStacksService(StackId $stack, ServiceId $service): string
    {
        if (! $this->alsoNeedsAService()) {
            throw AScreenNeedsMoreThanAStack::andThisOneDoesNot($this);
        }

        return str_replace([self::NAMED, self::ABOUT], [$stack->stored(), $service->named()], $this->value);
    }

    /**
     * This screen's path, for one whole form on one machine.
     *
     * The same segment {@see self::forTheStacksService()} fills and a different
     * thing filling it, which is why it is a second method rather than a wider
     * first one. The screen at the other end reads a service first and a form
     * second, so the path carries a name and not a kind — and a single builder
     * taking `string` would be the one thing `D2` is about here: a form name
     * and a service name are both text, and passing one where the other belongs
     * would compile and ship.
     */
    public function forTheStacksForm(StackId $stack, Form $form): string
    {
        if (! $this->alsoNeedsAService()) {
            throw AScreenNeedsMoreThanAStack::andThisOneDoesNot($this);
        }

        return str_replace([self::NAMED, self::ABOUT], [$stack->stored(), $form->named()], $this->value);
    }

    /**
     * Whether naming a machine is enough to reach this screen.
     *
     * Read off the pattern rather than listed, so a case added with a second
     * placeholder is covered by both refusals above without anybody
     * remembering to add it to a list — which is the failure mode a hand-kept
     * list has, and the one this whole type exists to close.
     */
    public function alsoNeedsAService(): bool
    {
        return str_contains($this->value, self::ABOUT);
    }
}
