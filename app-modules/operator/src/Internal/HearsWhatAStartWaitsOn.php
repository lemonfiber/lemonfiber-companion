<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Kernel\Api\AgreedTo;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Operator\Internal\ViewModels\AStartLineAsShown;
use Native\Mobile\Edge\NativeComponent;

/**
 * What a start sent from this screen is waiting for, held from the stack's event stream while it runs.
 *
 * The stack says what a start or a restart waits on as it waits on it, and the
 * newest line replaces the one before. So the screen that sent one holds the
 * stream beside the verb's handle, on a subscription of its own, and takes
 * what arrived on the same wakes it asks after the handle on: taking sends
 * nothing to the stack.
 *
 * **Held only while the verb runs, and while the screen is in front.** It is
 * let go of on the first wake after the verb has finished, and whenever this
 * screen stops being the one in front, which is how every way off a screen
 * ends.
 *
 * **It reads the screen's own `$hearingTheStart` and `$storage`**, and the verb
 * {@see FollowsWhatTheVerbCameTo} follows. That is the coupling, stated here
 * because a trait cannot declare it. The screen takes `$hearingTheStart` as
 * protected, because only this trait reads it, and an analyser that does not
 * follow a trait reads a private one as never used.
 *
 * @phpstan-require-extends NativeComponent
 */
trait HearsWhatAStartWaitsOn
{
    /**
     * The stack's last line for what a start sent from here is waiting for.
     *
     * Kept between frames because the newest line replaces the one before,
     * and a wake that heard nothing new still has the last one to show.
     */
    public string $waitsOn = '';

    abstract public function stack(): Stack;

    /** Let go of the start's subscription as the screen stops, keeping the last line. */
    protected function letGoOfWhatElseItHears(): void
    {
        $this->hearingTheStart->letGo();
    }


    /**
     * Take the stack's newest line for what a running start waits on.
     *
     * Only while a start or a restart sent from here runs: that is when the
     * stack says what the wait is for, and its line is drawn in place of this
     * screen's own. Once the verb has finished the stream is let go of. A
     * stream that could not be heard keeps the last line, because the verb's
     * own report is what says whether anything stood in the way, and a session
     * the stream refused is let go of, for {@see \Modules\Connection\Api\LetsGoOfARefusedSession}'s
     * reason.
     */
    private function listenWhileItStarts(): void
    {
        $stack = $this->stack();
        $sent = $this->sent;

        if (! $this->awaitsAnOutcome() || ! $sent instanceof AgreedTo || ! $sent->doing()->bringsSomethingUp()) {
            $this->hearingTheStart->letGo();

            return;
        }

        $kept = new AStartLineAsShown($this->waitsOn);

        $this->waitsOn = $this->storage->resume($stack->id())->either(
            held: fn(Session $session): AStartLineAsShown => $this->hearingTheStart->whatItWaitsOn($stack, $session)->either(
                saying: static fn(string $line): AStartLineAsShown => new AStartLineAsShown($line),
                nothingNew: static fn(): AStartLineAsShown => $kept,
                met: function (Obstacle $why) use ($kept, $stack): AStartLineAsShown {
                    $this->letGoOfTheSession($why, $stack);

                    return $kept;
                },
            ),
            notHeld: static fn(): AStartLineAsShown => $kept,
        )->said;
    }
}
