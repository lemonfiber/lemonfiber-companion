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
use Modules\Sdk\Internal\WhatARefusalMeant;

/**
 * The stack's own upkeep, asked through the SDK.
 *
 * {@see Supervisors} one conversation over, and built the same way: the client
 * is fetched per stack and session so that `N1-R11`'s separate sessions and
 * `N1-R19`'s pinning cannot be paired up wrongly.
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
            // with an envelope. This surface asks about the services; where
            // this copy of lemonfiber stands is a different reading, a
            // different kind, and nothing an operator's phone is for.
            $envelope = $client->read(
                Api::UPDATE_ENDPOINT,
                [WireField::What->value => WireField::TheStack->value],
            );

            // Inside the same `try` as the request, deliberately — the argument
            // {@see Stalls::stoppedOn()} makes. A payload the client fetched and
            // this side could not read is the same thing to an operator as one
            // that never arrived.
            return WhatIsCurrent::stands(Standings::in($envelope));
        } catch (RequestFailed $why) {
            return WhatIsCurrent::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|UnreadableResponse|UnexpectedKind|UpkeepIsUnreadable) {
            return WhatIsCurrent::met(Obstacle::StackDidNotAnswer);
        }
    }

    public function take(Stack $stack, Session $session, TakingAnUpdate $agreed): Underway
    {
        $client = $this->clients->client($stack, $session);

        try {
            $envelope = $client->act(Api::action($agreed->asked()), $this->about($agreed));

            return Underway::as(Handles::in($envelope));
        } catch (RequestFailed $why) {
            return Underway::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|UnreadableResponse|UnexpectedKind|HandleIsUnreadable) {
            return Underway::met(Obstacle::StackDidNotAnswer);
        }
    }

    /**
     * What the operator agreed to, as the action's arguments.
     *
     * The services are iterated for rather than handed over as a list, because
     * a value object's insides are not an adapter's to reach into — which is
     * the whole reason they travel as a collection.
     *
     * @return array<string, mixed>
     */
    private function about(TakingAnUpdate $agreed): array
    {
        $services = [];

        foreach ($agreed->changing() as $service) {
            $services[] = $service->named();
        }

        return [WireField::Services->value => $services];
    }
}
