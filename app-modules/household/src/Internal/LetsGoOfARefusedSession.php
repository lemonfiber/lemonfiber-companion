<?php

declare(strict_types=1);

namespace Modules\Household\Internal;

use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Stack;
use Native\Mobile\Edge\NativeComponent;

/**
 * The effect half of letting go of a refused session, for this surface's screens.
 *
 * A screen that resumes a session and hands it to a stack has to stop holding
 * one that stack has just refused. A fold already renders a refused credential
 * as the signed-out state, so nothing already loaded reaches a template — but a
 * fold cannot forget anything, and a session left in the store is resumed on
 * the next frame and refused again. The person in front of it is looking at a
 * sign-in prompt over a device that still believes it is signed in.
 *
 * **It is the second copy of this, and the boundary is why.** The operator
 * surface has the same trait under the same name, and a surface may depend on a
 * kernel, a design and a capability module and never on another surface — so
 * there is nowhere both could reach it from that is not a weaker home than
 * either. The two do not drift into disagreeing, because neither of them
 * decides anything: {@see Obstacle::meansWeAreSignedOut()} is the decision, in
 * the kernel, asked by both and answered by one — and `ARefusedSessionIsLetGoOfTest`
 * holds every screen in the application to having this, by name, whichever
 * surface it is on.
 *
 * **A trait rather than a collaborator, because there is no state here.** What
 * it needs is the screen's own store, and a type handed out to be called back
 * would mean a constructor holding something that answers one question.
 *
 * **It reads the using screen's own `$storage`.** That is the coupling, stated
 * here because a trait cannot declare it.
 *
 * @phpstan-require-extends NativeComponent
 */
trait LetsGoOfARefusedSession
{
    /**
     * Stop holding a session the stack has just refused.
     *
     * Whether an obstacle means that is the kernel's decision, asked here
     * rather than answered again, so a screen cannot come to disagree with the
     * fold it hands the same obstacle to.
     */
    private function letGoOfTheSession(Obstacle $why, Stack $stack): void
    {
        if (! $why->meansWeAreSignedOut()) {
            return;
        }

        $this->storage->forget($stack->id());
    }
}
