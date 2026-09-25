<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Illuminate\View\View;

use function is_string;

use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Safekeeping;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Stacks;
use Modules\Kernel\Api\TheCredentialsHeld;
use Modules\Operator\Internal\LetsGoOfARefusedSession;
use Modules\Operator\Internal\Presenters\HowTheCredentialsRead;
use Modules\Operator\Internal\ViewModels\TheCredentialsTurnedOutToBe;
use Modules\Operator\Internal\WhereAStackIs;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

use function view;

/**
 * The credentials a stack holds to let services in: where each stands, who made it and what uses it.
 *
 * Each state is drawn in its own words, so a stale credential, an invalid one
 * and one mid-rotation are three different rows. A credential nothing uses
 * says so. What the store protects against, and what it does not, closes the
 * list.
 *
 * **It sets, changes and shows no value.** None reaches this app, and a
 * credential is replaced at the machine. It asks once, when the frame is
 * built.
 *
 * `Concealed` for the reason every stack-facing screen here is.
 */
#[Lazy]
#[Concealed]
final class WhatItHoldsToLetThemIn extends NativeComponent
{
    use LetsGoOfARefusedSession;

    /**
     * What came back, once the frame has asked.
     *
     * `public`, for the reason {@see WhatIsRunningHere::$answered} gives.
     */
    public ?TheCredentialsTurnedOutToBe $answered = null;

    public function __construct(
        private readonly Safekeeping $safekeeping,
        private readonly SecureStorage $storage,
        private readonly Stacks $stacks,
    ) {}

    /**
     * The stack this screen is about.
     *
     * Read from the route on every frame rather than held, for
     * {@see WhatIsRunningHere::stack()}'s reason.
     */
    public function stack(): Stack
    {
        $named = $this->param('stack');

        return $this->stacks->configured()->stack(
            StackId::rememberedAs(is_string($named) ? $named : ''),
        );
    }

    /** Ask the machine again, which an obstacle must not take away. */
    public function again(): void
    {
        $this->answered = null;
    }

    /** Where this machine's screens are. */
    public function goes(): WhereAStackIs
    {
        return WhereAStackIs::of($this->stack()->id());
    }

    public function render(): View
    {
        return view('operator::what-it-holds-to-let-them-in');
    }

    /** What came back, asked once per frame. */
    public function answer(): TheCredentialsTurnedOutToBe
    {
        return $this->answered ??= $this->ask();
    }

    /** Resume the session, ask the machine, and flatten what came back. */
    private function ask(): TheCredentialsTurnedOutToBe
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): TheCredentialsTurnedOutToBe => $this->asked($stack, $session),
            notHeld: static fn(): TheCredentialsTurnedOutToBe => new HowTheCredentialsRead()->signedOut(),
        );
    }

    /** What the machine said it holds, or what the operator met instead. */
    private function asked(Stack $stack, Session $session): TheCredentialsTurnedOutToBe
    {
        return $this->safekeeping->heldOn($stack, $session)->either(
            found: static fn(TheCredentialsHeld $held): TheCredentialsTurnedOutToBe => new HowTheCredentialsRead()->these($held),
            met: function (Obstacle $why) use ($stack): TheCredentialsTurnedOutToBe {
                $this->letGoOfTheSession($why, $stack);

                return new HowTheCredentialsRead()->met($why);
            },
        );
    }
}
