<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Lemonfiber\Sdk\Contract\Api;
use Lemonfiber\Sdk\Exception\ApiVersionMismatch;
use Lemonfiber\Sdk\Exception\RequestFailed;
use Lemonfiber\Sdk\Exception\UnexpectedKind;
use Lemonfiber\Sdk\Exception\UnreadableResponse;
use Modules\Kernel\Api\AgreedTo;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Supervising;
use Modules\Kernel\Api\Underway;
use Modules\Kernel\Api\WhatIsRunning;
use Modules\Sdk\Internal\WhatARefusalMeant;

/**
 * The one place this application asks a stack what it is running, and tells it
 * to change that.
 *
 * `N1-R16` says every call goes through the SDK, so this sits beside
 * {@see Stalls} and {@see Menders} and is written the same way: it asks
 * {@see PinnedClients} for the connection rather than building one, which is
 * what keeps the certificate pin in a single file. This class never names a
 * client constructor, so it cannot make a decision about whether a certificate
 * is checked.
 *
 * **The verb reaches the wire through {@see Api::action()}.** The path of an
 * action is one segment and a name, and the SDK composes it so no caller
 * spells either half. What names are offered is lemonfiber's own list and not
 * this client's to hold — a name it does not offer is refused by name, which is
 * an answer, where a copy of the list kept here would go stale in silence.
 *
 * **A form and a service are different arguments, not one narrowing.** Stopping
 * a whole form is `forms`, stopping one of its services is `services`, and the
 * surface reads them as two requests rather than one with an option — which is
 * why {@see AgreedTo} carries one or the other and this asks it which.
 *
 * **Four raises, two answers**, which is {@see Stalls}' collapse for its
 * reason: the endpoint refusing, the answer being unreadable, and the two ends
 * disagreeing about the API version are three faults with one meaning for
 * somebody holding a phone. The exception is a refused session, which is a
 * different sentence and is told apart by {@see WhatARefusalMeant}.
 *
 * **A `ConfigurationProblem` is deliberately not caught**, for {@see Stalls}'
 * reason: it means this app is holding a stack it should never have written
 * down, and catching it would turn a fault in retained state into an ordinary
 * screen about an unreachable machine.
 */
final readonly class Supervisors implements Supervising
{
    public function __construct(private PinnedClients $clients) {}

    public function running(Stack $stack, Session $session): WhatIsRunning
    {
        $client = $this->clients->client($stack, $session);

        try {
            $envelope = $client->read(Api::STATUS_ENDPOINT);

            // Inside the same `try` as the request, deliberately — the argument
            // {@see Stalls::stoppedOn()} makes. A payload the client fetched
            // and this side could not read is the same thing to an operator as
            // one that never arrived.
            return WhatIsRunning::these(Rosters::in($envelope));
        } catch (RequestFailed $why) {
            return WhatIsRunning::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|UnreadableResponse|UnexpectedKind|RosterIsUnreadable) {
            return WhatIsRunning::met(Obstacle::StackDidNotAnswer);
        }
    }

    public function told(Stack $stack, Session $session, AgreedTo $agreed): Underway
    {
        $client = $this->clients->client($stack, $session);

        try {
            $envelope = $client->act(Api::action($agreed->doing()->asked()), $this->about($agreed));

            return Underway::as(Handles::in($envelope));
        } catch (RequestFailed $why) {
            return Underway::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|UnreadableResponse|UnexpectedKind|HandleIsUnreadable) {
            return Underway::met(Obstacle::StackDidNotAnswer);
        }
    }

    /**
     * What the operator agreed about, under the argument that carries it.
     *
     * One key and not both. The surface takes an action's arguments with
     * `deny_unknown_fields` and every field defaulted, so naming the one that
     * applies says exactly what was agreed to — where sending an empty list
     * beside it would put a second, silent subject in every request.
     *
     * @return array<string, list<string>>
     */
    private function about(AgreedTo $agreed): array
    {
        $field = $agreed->isAboutAForm() ? WireField::Forms : WireField::Services;

        return [$field->value => [$agreed->named()]];
    }
}
