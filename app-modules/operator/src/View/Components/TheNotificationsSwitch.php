<?php

declare(strict_types=1);

namespace Modules\Operator\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Kernel\Api\Permission;
use Modules\Operator\Internal\WhereAskingForNotificationsStands;

use function view;

/**
 * The phone's switch for notifications, and the reason it opens before the phone is asked.
 *
 * Drawn the same on App settings and at the top of the notification page, each
 * a screen using {@see \Modules\Operator\Internal\ChoosesNotifications}, whose
 * methods its taps name. Off until the operator turns it on; turning it on says
 * why first, and only its button asks the phone.
 */
final class TheNotificationsSwitch extends Component
{
    /** The reason the app wants notifications, as a key. */
    public readonly string $why;

    /** What still works without them, as a key. */
    public readonly string $without;

    public function __construct(
        public readonly bool $on,
        public readonly ?WhereAskingForNotificationsStands $asking,
        public readonly bool $settingsWouldNotOpen,
    ) {
        $this->why = Permission::Notifications->reason();
        $this->without = Permission::Notifications->alternative();
    }

    public function render(): View
    {
        return view('operator::components.the-notifications-switch');
    }
}
