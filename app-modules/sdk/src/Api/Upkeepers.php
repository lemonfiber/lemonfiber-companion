<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Lemonfiber\Sdk\Contract\Api;
use Lemonfiber\Sdk\Exception\ApiVersionMismatch;
use Lemonfiber\Sdk\Exception\RequestFailed;
use Lemonfiber\Sdk\Exception\UnexpectedKind;
use Lemonfiber\Sdk\Exception\UnreadableResponse;
use Modules\Kernel\Api\KeepingCurrent;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TakingAnUpdate;
use Modules\Kernel\Api\Underway;
use Modules\Kernel\Api\WhatIsCurrent;
use Modules\Sdk\Api\Fields\UpdateField;
use Modules\Sdk\Internal\WhatARefusalMeant;

/**
 * The stack's own upkeep, asked through the SDK.
 *
 * {@see Supervisors} one conversation over, and built the same way: the client
 * is fetched per stack and session so that separate sessions and separate
 * pinning cannot be paired up wrongly.
 */
final readonly class Upkeepers implements KeepingCurrent
{
    public function __construct(private Clients $clients) {}

    public function standing(Stack $stack, Session $session): WhatIsCurrent
    {
        $client = $this->clients->client($stack, $session);

        try {
            // Naming what this is about, because the endpoint serves two things
            // and a request that says neither is answered in prose rather than
            // with an envelope. This asks about the services; where the running
            // copy of lemonfiber stands is {@see Inspectors}' reading.
            $envelope = $client->read(
                Api::UPDATE_ENDPOINT,
                [WireField::What->value => UpdateField::TheStack->value],
            );

            // Inside the same `try` as the request, deliberately — the argument
            // {@see Stalls::stoppedOn()} makes. A payload the client fetched and
            // this side could not read is the same thing to an operator as one
            // that never arrived.
            return WhatIsCurrent::stands(Standings::in($envelope));
        } catch (RequestFailed $why) {
            return WhatIsCurrent::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|UnreadableResponse|UnexpectedKind|UpkeepIsUnreadable|ChangelogIsUnreadable) {
            return WhatIsCurrent::met(Obstacle::StackDidNotAnswer);
        }
    }

    public function take(Stack $stack, Session $session, TakingAnUpdate $agreed): Underway
    {
        $client = $this->clients->client($stack, $session);

        try {
            // `confirm` and nothing else. Unconfirmed, the stack's `update`
            // action only says what would change; confirmed, it moves every
            // service it listed and did not refuse, which is the list
            // `TakingAnUpdate::changing()` holds and the confirmation named.
            // The action narrows to one `service` and takes no list, so none
            // is sent: one would narrow the run, and a list is refused.
            $envelope = $client->act(Api::action($agreed->asked()), [UpdateField::Confirm->value => true]);

            return Underway::as(Handles::in($envelope));
        } catch (RequestFailed $why) {
            return Underway::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|UnreadableResponse|UnexpectedKind|HandleIsUnreadable) {
            return Underway::met(Obstacle::StackDidNotAnswer);
        }
    }
}
