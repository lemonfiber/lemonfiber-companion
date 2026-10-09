<?php

declare(strict_types=1);

namespace Bootstrap\Composition\NativePHP;

use Lemonfiber\Native\Events\ThePlayerMoved;
use Modules\Household\Internal\Playing\KeepsTheMembersPlace;
use Modules\Household\Internal\Screens\HearsThePlayer;
use Native\Mobile\Edge\NativeComponent;

/**
 * What the app does when the device's player moves.
 *
 * The device wakes the app with {@see ThePlayerMoved}, which carries nothing,
 * and the member's place is read afresh from the player and told to the core,
 * whichever screen is under it. The screen on view looks again where it shows
 * Play, so a title that stopped says why.
 *
 * So an event from anywhere, forged or replayed, can make the app ask the
 * player where it stands and tell the core what it says. It cannot hand the
 * player anything.
 *
 * In the composition root because it knows both the navigation stack and the
 * household's player, which is what this directory is for.
 */
final readonly class WhenThePlayerMoves
{
    public function __construct(private KeepsTheMembersPlace $place) {}

    /** Heard from the dispatcher, over the screen driving the runloop. */
    public function handle(): void
    {
        $this->over(NativeComponent::active());
    }

    /** What the player moving does, with this screen on view. */
    public function over(?NativeComponent $screen): void
    {
        $this->place->heard();

        if ($screen instanceof HearsThePlayer) {
            $screen->playerMoved();
        }
    }
}
