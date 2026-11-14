<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/** What a back-end that delivers alerts speaks. */
enum KindOfAlertBackEnd: string
{
    /** ntfy, which a phone subscribes to by topic. */
    case Ntfy = 'ntfy';

    /** Gotify, which a phone subscribes to by application. */
    case Gotify = 'gotify';

    /** A webhook the core posts each alert to. */
    case Webhook = 'webhook';

    /** Apprise, which hands each alert on to the services it is set up with. */
    case Apprise = 'apprise';

    /** UnifiedPush, through the distributor a phone runs. */
    case UnifiedPush = 'unifiedpush';

    /** The key for what this is called on the screen. */
    public function saidOnTheScreen(): string
    {
        return sprintf('alerts.kind.%s', $this->value);
    }
}
