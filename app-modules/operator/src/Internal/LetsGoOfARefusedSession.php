<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Stack;
use Native\Mobile\Edge\NativeComponent;

/**
 * The effect half of letting go of a refused session, written once for the screens that need it.
 *
 * Five screens resume a session and hand it to a stack, and every one of them
 * has to let go of a session that stack has just refused. The decision is the
 * same decision, the effect is the same effect, and five copies of a rule about
 * whether somebody is signed in is five chances to come to disagree — which is
 * the failure {@see Obstacle::meansWeAreSignedOut()} was written to end one
 * layer down, and it would be odd to rebuild it one layer up.
 *
 * **A trait rather than a collaborator, because there is no state here.** What
 * this needs is the screen's own store, and a type handed out to be called back
 * would mean five constructors changing to hold something that answers one
 * question. `WhereAStackIs` is the other shape and it earns its keep by holding
 * an identifier; this holds nothing.
 *
 * It is also what keeps the screens under the twenty-method ceiling.
 * Three of them arrived at twenty-one the day this effect was added to each,
 * which is what a cross-cutting concern looks like when it is pasted.
 *
 * **It reads the using screen's own `$storage`.** That is the coupling, stated
 * here because a trait cannot declare it: every screen that resumes a session
 * already holds the store it resumed from, and a trait that took one as an
 * argument would be a function with extra steps.
 *
 * @phpstan-require-extends NativeComponent
 */
trait LetsGoOfARefusedSession
{
    /**
     * Stop holding a session the stack has just refused.
     *
     * The half of the requirement that is an effect rather than a value. A fold
     * already renders a refused credential as the signed-out state, so nothing
     * already loaded reaches a template — but a fold cannot forget anything,
     * and a session left in the store is resumed on the next frame and refused
     * again. The operator would be looking at a sign-in prompt over a device
     * that still believes it is signed in.
     *
     * Whether an obstacle means that is {@see Obstacle::meansWeAreSignedOut()}'s
     * decision, asked here rather than answered again, so a screen cannot come
     * to disagree with the fold it hands the same obstacle to.
     */
    private function letGoOfTheSession(Obstacle $why, Stack $stack): void
    {
        if (! $why->meansWeAreSignedOut()) {
            return;
        }

        $this->storage->forget($stack->id());
    }
}
