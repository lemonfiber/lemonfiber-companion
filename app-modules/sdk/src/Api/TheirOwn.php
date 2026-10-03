<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Lemonfiber\Sdk\Contract\Api;
use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Exception\ApiVersionMismatch;
use Lemonfiber\Sdk\Exception\CertificateWasRefused;
use Lemonfiber\Sdk\Exception\RequestFailed;
use Lemonfiber\Sdk\Exception\UnexpectedKind;
use Lemonfiber\Sdk\Exception\Unreachable;
use Lemonfiber\Sdk\Exception\UnreadableResponse;
use Modules\Kernel\Api\Owing;
use Modules\Kernel\Api\RequestHasNobodyBehindIt;
use Modules\Kernel\Api\SentenceSaysNothing;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatTheirAskingSaid;
use Modules\Kernel\Api\WhatTheyAreOwed;
use Modules\Kernel\Api\WhatTheyAsked;
use Modules\Sdk\Internal\WhatARefusalMeant;
use Modules\Sdk\Internal\WhatStoodInTheWayOfTheHousehold;

/**
 * What the stack says the signed-in member is owed.
 *
 * The same endpoint {@see Requests} reads and a different question of it. That
 * one flattens the house into every request anybody made, which is what an
 * operator deciding on six requests needs. This one keeps the member, because
 * what a member is owed is a reading *about them* and the flattening is exactly
 * where it is lost.
 *
 * The reading itself is {@see Tellings}', which is {@see Requests}' shape over
 * the same payload: this file asks and answers, and the fold turns what came
 * back into something the kernel has a name for.
 *
 * **Four raises, two answers**, which is {@see Requests}' collapse for its
 * reason: the endpoint refusing, the answer being unreadable, and the two ends
 * disagreeing about the API version are three faults with one meaning for
 * somebody holding a phone. A blank sentence among real ones joins them, and is
 * the one worth naming: it is something this app cannot show, which is the same
 * to a member as an answer that never arrived.
 *
 * **A `ConfigurationProblem` is deliberately not caught**, for {@see Requests}'
 * reason: the SDK raises one where a stored stack cannot be pinned, which means
 * this app is holding a stack it should never have written down.
 */
final readonly class TheirOwn implements Owing
{
    public function __construct(private Clients $clients) {}

    public function toHandOver(Stack $stack, Session $session): WhatTheyAreOwed
    {
        return $this->theirRequests($stack, $session)->owed();
    }

    /**
     * What they are owed and what they have asked for, from one reading.
     *
     * The endpoint is read once and folded twice. Where it could not be read,
     * both halves are refused for that reason; where it was read, each fold
     * refuses on its own, so a stack that said one and not the other has
     * answered half.
     *
     * The raises collapse the way {@see Requests}' do: a refused endpoint, an
     * unreadable answer and two ends disagreeing about the API version are
     * three faults with one meaning to somebody holding a phone.
     */
    public function theirRequests(Stack $stack, Session $session): WhatTheirAskingSaid
    {
        $client = $this->clients->client($stack, $session);

        try {
            $envelope = $client->read(Api::REQUESTS_ENDPOINT);

            return WhatTheirAskingSaid::of($this->owedIn($stack, $envelope), $this->askedIn($stack, $envelope));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return WhatTheirAskingSaid::bothRefused(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse $why) {
            return WhatTheirAskingSaid::bothRefused($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    /**
     * What the answer says they are owed, or what stood in the way of reading it.
     *
     * {@see SentenceSaysNothing} is here rather than guarded against above, so
     * one type decides what a blank sentence means: a stack that sent a blank
     * line among real ones sent something this app cannot show, which is the
     * same to a member as an answer that never arrived.
     *
     * @param Envelope<mixed> $envelope
     */
    private function owedIn(Stack $stack, Envelope $envelope): WhatTheyAreOwed
    {
        try {
            return WhatTheyAreOwed::told(Tellings::in($envelope));
        } catch (UnexpectedKind|HouseholdIsUnreadable|HouseholdWentUnread|SentenceSaysNothing $why) {
            return WhatTheyAreOwed::refused(WhatStoodInTheWayOfTheHousehold::ofTheHousehold($this->clients, $stack, $why));
        }
    }

    /**
     * What the answer says they have asked for, or what stood in the way of reading it.
     *
     * The fold is {@see Households::theirOwnIn()}, the operator's own reading of
     * a request row with the subject changed, so a member and an operator cannot
     * be told different things about the same refusal.
     *
     * @param Envelope<mixed> $envelope
     */
    private function askedIn(Stack $stack, Envelope $envelope): WhatTheyAsked
    {
        try {
            return WhatTheyAsked::told(Households::theirOwnIn($envelope));
        } catch (UnexpectedKind|HouseholdIsUnreadable|HouseholdWentUnread|RequestHasNobodyBehindIt $why) {
            return WhatTheyAsked::refused(WhatStoodInTheWayOfTheHousehold::ofTheHousehold($this->clients, $stack, $why));
        }
    }
}
