<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Wayfinding\Api\AScreenWithoutAStack;
use Native\Mobile\Edge\NativeComponent;

/**
 * The way from the phone's settings to the notification page, where each stack's kinds and floor are chosen.
 *
 * @phpstan-require-extends NativeComponent
 */
trait OpensTheNotificationPage
{
    /** Where the notification page is. */
    public function notificationPageIsAt(): string
    {
        return AScreenWithoutAStack::Notifications->value;
    }
}
