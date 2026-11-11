<?php

declare(strict_types=1);

namespace Modules\Connection\Api;

use Modules\Kernel\Api\Asked;
use Modules\Kernel\Api\Notification;
use Modules\Kernel\Api\Notifier;
use Modules\Kernel\Api\Shown;
use Modules\Kernel\Api\WhyNothingIsShown;

/**
 * Every notification the operator lets reach the phone from its stack, and none they left out.
 *
 * Read from what the phone keeps for that stack on every one, so a kind
 * switched off, or a floor raised, holds from the next notification on, heard
 * in the open app or on a wake alike. The alert still reaches the app's
 * screens; only the notification is withheld.
 */
final readonly class NotifyingOnlyWhatEachStackLets implements Notifier
{
    public function __construct(private Notifier $within, private ChoosingWhatReachesThePhone $chosen) {}

    public function standing(): Asked
    {
        return $this->within->standing();
    }

    public function ask(): Asked
    {
        return $this->within->ask();
    }

    public function show(Notification $notification): Shown
    {
        if (! $notification->isLetThroughBy($this->chosen->forTheStack($notification->about()))) {
            return Shown::withheld(WhyNothingIsShown::TheOperatorLeftItOut);
        }

        return $this->within->show($notification);
    }
}
