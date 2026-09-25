<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Lemonfiber\Sdk\Contract\Api;
use Lemonfiber\Sdk\Exception\ApiVersionMismatch;
use Lemonfiber\Sdk\Exception\RequestFailed;
use Lemonfiber\Sdk\Exception\UnexpectedKind;
use Lemonfiber\Sdk\Exception\Unreachable;
use Lemonfiber\Sdk\Exception\UnreadableResponse;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Owing;
use Modules\Kernel\Api\RequestHasNobodyBehindIt;
use Modules\Kernel\Api\SentenceSaysNothing;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatTheyAreOwed;
use Modules\Kernel\Api\WhatTheyAsked;
use Modules\Sdk\Internal\WhatARefusalMeant;

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
        $client = $this->clients->client($stack, $session);

        try {
            $envelope = $client->read(Api::REQUESTS_ENDPOINT);

            // Inside the same `try` as the request, for {@see Requests}' reason:
            // a payload the client fetched and this side could not read is the
            // same thing to the person reading the screen as one that never
            // arrived.
            return WhatTheyAreOwed::told(Tellings::in($envelope));
        } catch (RequestFailed $why) {
            return WhatTheyAreOwed::refused(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse|UnexpectedKind|HouseholdIsUnreadable|SentenceSaysNothing) {
            // {@see SentenceSaysNothing} is in this list rather than guarded
            // against above, so one type decides what a blank sentence means
            // and this one decides what an unreadable answer means to whoever
            // is looking at it. A stack that sent a blank line among real ones
            // sent something this app cannot show, which is the same to a
            // member as an answer that never arrived.
            return WhatTheyAreOwed::refused(Obstacle::StackDidNotAnswer);
        }
    }

    /**
     * What the signed-in member has asked this stack for.
     *
     * The same endpoint and the same narrowing as {@see toHandOver()}, read for
     * the other half of what a member's screen shows. Asked separately rather
     * than folded into one call, because the two are two answers: a stack that
     * said what somebody is owed and could not say what they asked for has
     * answered half, and a reading that returned both together would have to
     * throw one away to report the other.
     *
     * The fold is {@see Households::theirOwnIn()}, which is the operator's own
     * parsing of a request row with the subject changed — one reading of what a
     * row means, so a member and an operator cannot be told different things
     * about the same refusal.
     *
     * The raises collapse the way {@see toHandOver()}'s do, and for the same
     * reason: a refused endpoint, an unreadable answer and two ends disagreeing
     * about the API version are three faults with one meaning to somebody holding
     * a phone.
     */
    public function whatTheyAsked(Stack $stack, Session $session): WhatTheyAsked
    {
        $client = $this->clients->client($stack, $session);

        try {
            $envelope = $client->read(Api::REQUESTS_ENDPOINT);

            // Inside the same `try` as the request, for {@see toHandOver()}'s
            // reason: a payload the client fetched and this side could not read
            // is the same thing to the person reading the screen as one that
            // never arrived.
            return WhatTheyAsked::told(Households::theirOwnIn($envelope));
        } catch (RequestFailed $why) {
            return WhatTheyAsked::refused(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse|UnexpectedKind|HouseholdIsUnreadable|RequestHasNobodyBehindIt) {
            // {@see SentenceSaysNothing} is not in this list, and its absence is
            // the difference between the two readings: no sentence is read here,
            // so there is no blank one to meet. A request row this app cannot
            // show raises {@see HouseholdIsUnreadable} instead, which is already
            // here.
            return WhatTheyAsked::refused(Obstacle::StackDidNotAnswer);
        }
    }
}
