<?php

declare(strict_types=1);

namespace Bootstrap\Composition\NativePHP;

use Lemonfiber\Native\Events\TheLockMoved;
use Modules\Connection\Api\TheLock;
use Modules\Operator\Internal\AScreenWithoutAStack;
use Modules\Operator\Internal\AwaitsAnOutcome;
use Modules\Operator\Internal\Screens\Locked;
use Native\Mobile\Edge\NativeComponent;

/**
 * What the screen on view does when the device's lock moves.
 *
 * The device wakes the app with {@see TheLockMoved}, which carries nothing, and
 * this reads the lock afresh through the bridge. Over the lock screen, that is
 * the lock screen's own business: it goes on if the lock opened. Over anything
 * else, a lock that stands puts the lock screen over it, keeping the screen
 * under it as it was; a lock that is open changes nothing.
 *
 * So an event from anywhere, forged or replayed, can make the app look again
 * and put the lock up. It cannot take the lock down.
 *
 * In the composition root because it knows both the navigation stack and the
 * surface's lock screen, which is what this directory is for.
 */
final readonly class WhenTheLockMoves
{
    public function __construct(private TheLock $lock) {}

    /** Heard from the dispatcher, over the screen driving the runloop. */
    public function handle(): void
    {
        $this->over(NativeComponent::active());
    }

    /** What the lock moving does to the screen on view. */
    public function over(?NativeComponent $screen): void
    {
        if ($screen instanceof Locked) {
            $screen->lockMoved();

            return;
        }

        if (! $screen instanceof NativeComponent) {
            return;
        }

        $this->lock->standing()->either(
            held: static fn(): NativeComponent => $screen->navigate(
                AScreenWithoutAStack::Locked->value,
                [Locked::AWAITS => $screen instanceof AwaitsAnOutcome && $screen->awaitsAnOutcome()],
            ),
            open: static fn(): NativeComponent => $screen,
        );
    }
}
