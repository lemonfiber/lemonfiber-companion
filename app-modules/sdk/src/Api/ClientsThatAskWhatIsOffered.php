<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Lemonfiber\Sdk\Client;
use Lemonfiber\Sdk\Exception\Unreachable;
use Modules\Kernel\Api\Ability;
use Modules\Kernel\Api\Clock;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheReadingWaitsAFrame;
use Modules\Sdk\Internal\WhatEachStackOffers;
use Throwable;

/**
 * Clients that ask a stack what it serves before sending it anything.
 *
 * The gate every request this app sends passes through: a request for a path
 * is answered from what {@see WhatEachStackOffers} holds of that stack for
 * that session, and refused before it is sent where the stack does not
 * declare it. A screen's reading and an action's request are gated the same
 * way, so a screen the stack is too old for opens and says so, and an action
 * it is too old for is never attempted.
 *
 * Only the two answers that are not a button are refused: a stack that does
 * not have it, and a credential it is not for. One not set up is sent, because
 * the stack has it and its answer says what is missing; one that could not be
 * asked about is sent, because the request is what reports why. A stack that
 * did not answer the asking at all is the exception: the request that asked is
 * given that silence rather than sent to wait as long again, and the next one
 * is sent.
 *
 * **A frame reads a stack once.** Where nothing is held of the stack, asking
 * it is the frame's one reading, so the request waits for the next frame
 * ({@see TheReadingWaitsAFrame}).
 *
 * **It opens nothing.** The client comes from the clients it is put in front
 * of and is handed on, so the one file that can open a connection is still
 * one, and what stood in the way of a reach is still theirs to say.
 */
final readonly class ClientsThatAskWhatIsOffered implements Clients
{
    public function __construct(
        private Clients $asking,
        private WhatEachStackOffers $held,
        private Clock $clock,
    ) {}

    public function client(Stack $stack, Session $session): Client
    {
        return $this->asking->client($stack, $session);
    }

    /**
     * @throws TheStackDoesNotOfferIt
     * @throws Unreachable
     * @throws TheReadingWaitsAFrame where the request's frame was spent asking the stack what it serves
     */
    public function towards(Stack $stack, Session $session, Ability $path): Client
    {
        $client = $this->asking->towards($stack, $session, $path);
        $offered = $this->held->at($stack, $session, $this->clock->now(), $client, $path);

        if (! $offered->offersAnAction()) {
            throw TheStackDoesNotOfferIt::at($path, $offered);
        }

        return $client;
    }

    public function whatStoodInTheWay(Stack $stack, Throwable $why): Obstacle
    {
        return $this->asking->whatStoodInTheWay($stack, $why);
    }
}
