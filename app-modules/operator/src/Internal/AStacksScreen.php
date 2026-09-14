<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

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
 * `Internal` because where a screen lives is a detail of this module's own
 * surface; `E2`'s promise is that it can be renamed without reading another.
 */
enum AStacksScreen: string
{
    /** How this machine is doing, which is what the app is for. */
    case Health = '/stacks/{stack}';

    /** Where a password is offered, and a session that ended is renewed. */
    case SignIn = '/stacks/{stack}/sign-in';

    /** What the household has asked this machine for (`N2-R11`). */
    case Requests = '/stacks/{stack}/requests';

    /** What this machine would put right, stated before any yes (`N2-R4`). */
    case Repairs = '/stacks/{stack}/repairs';

    /** What has stopped coming in, which is the first of `N2-R9`'s four. */
    case Stuck = '/stacks/{stack}/stuck';


    /** What this machine is running, and the verbs about it (`N2-R7`). */
    case Services = '/stacks/{stack}/services';

    /** What one of this machine's services has been saying (`N2-R10`). */
    case Logs = '/stacks/{stack}/logs/{service}';

    /** What the router holds a machine under. */
    public const string NAMED = '{stack}';

    /** What it holds one of that machine's services under (`N2-R10`). */
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
    public function forTheStack(string $stored): string
    {
        if ($this->alsoNeedsAService()) {
            throw AScreenNeedsMoreThanAStack::toBeReached($this);
        }

        return str_replace(self::NAMED, $stored, $this->value);
    }

    /**
     * This screen's path, for one service on one machine.
     *
     * The other half, and it refuses the other mistake: a case with no
     * `{service}` in it would silently ignore the second argument, which is a
     * caller holding a service name the path does not carry and a screen that
     * opens on whatever it likes.
     */
    public function forTheStacksService(string $stored, string $service): string
    {
        if (! $this->alsoNeedsAService()) {
            throw AScreenNeedsMoreThanAStack::andThisOneDoesNot($this);
        }

        return str_replace([self::NAMED, self::ABOUT], [$stored, $service], $this->value);
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
