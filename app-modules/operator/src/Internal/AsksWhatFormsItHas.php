<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Kernel\Api\Forms;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Supervising;
use Modules\Operator\Internal\Presenters\HowAListingReads;
use Modules\Operator\Internal\ViewModels\TheFormsAsFound;
use Native\Mobile\Edge\NativeComponent;

/**
 * The forms a stack declares, asked once and held for as long as the screen is.
 *
 * What a stack declares changes when its configuration does, which no screen
 * here waits on, so a screen that has the forms keeps them while what is
 * running is read again. A list that could not be read is not held: asking
 * again is how the operator gets past it.
 *
 * Handed the screen's own store and port rather than reaching for them, for
 * {@see AsksWhatTheStackIsRunning}'s reason.
 *
 * @phpstan-require-extends NativeComponent
 */
trait AsksWhatFormsItHas
{
    use LetsGoOfARefusedSession;

    /** The forms, once a frame has asked. `public` so the screen holds them between frames. */
    public ?TheFormsAsFound $formsFound = null;

    /** Let go of forms that could not be read, so the next frame asks for them again. */
    private function formsAgain(): void
    {
        if ($this->formsFound instanceof TheFormsAsFound && ! $this->formsFound->went->cameBack()) {
            $this->formsFound = null;
        }
    }

    /** Resume the session, ask the stack for its forms, and flatten what came back. */
    private function askWhatFormsItHas(Stack $stack, SecureStorage $storage, Supervising $supervising): TheFormsAsFound
    {
        return $storage->resume($stack->id())->either(
            held: fn(Session $session): TheFormsAsFound => $supervising->formsOn($stack, $session)->either(
                these: static fn(Forms $forms): TheFormsAsFound => new HowAListingReads()->forms($forms),
                met: function (Obstacle $why) use ($stack): TheFormsAsFound {
                    $this->letGoOfTheSession($why, $stack);

                    return new HowAListingReads()->formsMet($why);
                },
            ),
            notHeld: static fn(): TheFormsAsFound => new HowAListingReads()->formsSignedOut(),
        );
    }
}
