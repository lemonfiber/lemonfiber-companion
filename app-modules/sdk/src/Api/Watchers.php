<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Lemonfiber\Sdk\Contract\Api;
use Lemonfiber\Sdk\Exception\ApiVersionMismatch;
use Lemonfiber\Sdk\Exception\CertificateWasRefused;
use Lemonfiber\Sdk\Exception\RequestFailed;
use Lemonfiber\Sdk\Exception\UnexpectedKind;
use Lemonfiber\Sdk\Exception\Unreachable;
use Lemonfiber\Sdk\Exception\UnreadableResponse;
use Modules\Kernel\Api\AskingTheWatcher;
use Modules\Kernel\Api\HowLongIsBelowNothing;
use Modules\Kernel\Api\InstantIsBeforeTheEpoch;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatTheWatcherSaid;
use Modules\Sdk\Internal\GatedClient;
use Modules\Sdk\Internal\WhatARefusalMeant;

/**
 * The one place this application asks a stack what its outside watcher sees.
 *
 * Beside {@see Restarts} and written the same way: it asks and never changes.
 */
final readonly class Watchers implements AskingTheWatcher
{
    public function __construct(private Clients $clients) {}

    public function whatItSees(Stack $stack, Session $session): WhatTheWatcherSaid
    {
        $client = GatedClient::of($this->clients, $stack, $session);

        try {
            return WhatTheWatcherSaid::said(WhatTheWatcherSaw::in($client->read(Api::WATCHER_ENDPOINT)));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return WhatTheWatcherSaid::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|TheStackDoesNotOfferIt|UnreadableResponse|UnexpectedKind|WatcherIsUnreadable|HowLongIsBelowNothing|InstantIsBeforeTheEpoch $why) {
            return WhatTheWatcherSaid::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }
}
