<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Illuminate\View\View;

use function is_string;

use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Provenance;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Stacks;
use Modules\Kernel\Api\WhereTheServicesComeFrom;
use Modules\Operator\Internal\LetsGoOfARefusedSession;
use Modules\Operator\Internal\Presenters\HowTheOriginsRead;
use Modules\Operator\Internal\ViewModels\TheOriginsTurnedOutToBe;
use Modules\Operator\Internal\WhereAStackIs;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

use function view;

/**
 * Where every service on this machine comes from.
 *
 * The question somebody asks when they want to know what they are actually
 * running: which image, pinned at what, built from which project and under
 * whose licence. It is the other half of what a stack keeps about itself —
 * {@see WhatWasChangedHere} says what was done, and this says what it was done
 * with.
 *
 * **It asks once, when the frame is built, and holds what came back**, which is
 * {@see WhatWasChangedHere}'s shape: one read per frame.
 *
 * **It asks the stack and nothing else.** No project is reached to check a
 * licence or a version, so one that has gone away leaves this screen exactly
 * as true as it was — the pin and the licence are facts the stack wrote down.
 *
 * `Concealed` for the reason every stack-facing screen here is: what a house
 * runs is the household's business.
 */
#[Lazy]
#[Concealed]
final class WhereThisComesFrom extends NativeComponent
{
    use LetsGoOfARefusedSession;

    /**
     * What came back, once the frame has asked.
     *
     * `public`, which is what `NativeComponent`'s property syncing needs to
     * reach: since 4.5.1 it writes only public, non-static properties. The
     * same reason {@see WhatWasChangedHere::$answered} gives.
     */
    public ?TheOriginsTurnedOutToBe $answered = null;

    public function __construct(
        private readonly Provenance $provenance,
        private readonly SecureStorage $storage,
        private readonly Stacks $stacks,
    ) {}

    /**
     * The stack this screen is about.
     *
     * Read from the route on every frame rather than held, for
     * {@see WhatWasChangedHere::stack()}'s reason.
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
        return view('operator::where-this-comes-from');
    }

    /** What came back, asked once per frame. */
    public function answer(): TheOriginsTurnedOutToBe
    {
        return $this->answered ??= $this->ask();
    }

    /** Resume the session, ask the machine, and flatten what came back. */
    private function ask(): TheOriginsTurnedOutToBe
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): TheOriginsTurnedOutToBe => $this->asked($stack, $session),
            notHeld: static fn(): TheOriginsTurnedOutToBe => new HowTheOriginsRead()->signedOut(),
        );
    }

    /** Where the machine said its services come from, or what the operator met instead. */
    private function asked(Stack $stack, Session $session): TheOriginsTurnedOutToBe
    {
        return $this->provenance->declaredOn($stack, $session)->either(
            origins: static fn(WhereTheServicesComeFrom $origins): TheOriginsTurnedOutToBe
                => new HowTheOriginsRead()->this($origins),
            met: function (Obstacle $why) use ($stack): TheOriginsTurnedOutToBe {
                $this->letGoOfTheSession($why, $stack);

                return new HowTheOriginsRead()->met($why);
            },
        );
    }
}
