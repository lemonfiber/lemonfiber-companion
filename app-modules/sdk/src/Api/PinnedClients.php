<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Lemonfiber\Sdk\Client;
use Modules\Kernel\Api\Ability;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Sdk\Internal\HowLongACallWaits;
use Modules\Sdk\Internal\WhatTheReachMet;
use Throwable;

/**
 * The one place in this application that opens a connection to a stack.
 *
 * Every call to a stack goes through the SDK, and this app issues no request
 * of its own, and every connection is checked against the
 * certificate pairing material promised, whether or not the platform's trust
 * store would accept it. Both are structural here rather than remembered:
 * `tests/Feature/NothingReachesAStackUnpinnedTest.php` refuses any file outside
 * this one that names the SDK's transport, and this file names exactly one
 * constructor.
 *
 * **`Client::pinnedAt()` and nothing else.** The SDK offers three ways in.
 * `onPort()` and `at()` both build a client with no pin — correct for the
 * surfaces that talk to a stack over loopback, and wrong for every connection
 * this application makes, because a phone is never on the machine. Naming only
 * the pinned one means *"reach it unpinned"* has no spelling here rather than
 * being a mistake somebody could make.
 *
 * That is also why the pin is not a parameter. It comes off the {@see Stack},
 * which got it from the pairing material, which is where it has to come
 * from — never from the network, because a fingerprint learned from the
 * connection it is meant to validate proves nothing. A signature taking a stack
 * and a digest would let a caller supply a digest from somewhere else.
 *
 * **What it refuses is the SDK's to refuse.** A stack whose address is not
 * `https` cannot be pinned — a pin compares against a certificate and plain
 * HTTP presents none — and `BaseUrl::pinned()` says so. It is not re-checked
 * here: a second copy of that rule is a second place for the two to disagree,
 * and the SDK's refusal already names the scheme it was given.
 */
final readonly class PinnedClients implements Clients
{
    /**
     * `Client` rather than the port's `object`: this file may name the SDK, so
     * saying what it answers with costs nothing and tells a caller that already
     * depends on the SDK what it has. The port stays `object` because `kernel`
     * does not name the SDK at all, which keeps every capability testable
     * without a network.
     */
    public function client(Stack $stack, Session $session): Client
    {
        return Client::pinnedAt(
            $stack->at()->forTheClient(),
            $session->forTheHeader(),
            $stack->presents()->forComparingByEye(),
            HowLongACallWaits::ordinarily(),
        );
    }

    /**
     * The same client, whatever the path: nothing here asks the stack what it
     * serves. {@see ClientsThatAskWhatIsOffered} is what puts that question in
     * front of every request the application sends.
     *
     * @throws void
     */
    public function towards(Stack $stack, Session $session, Ability $path): Client
    {
        return $this->client($stack, $session);
    }

    /** What the reach says by itself, with the address it tried; {@see ClientsThatAskTheDevice} is what asks the device. */
    public function whatStoodInTheWay(Stack $stack, Throwable $why): Obstacle
    {
        return WhatTheReachMet::byItself($why)->whenTriedAt($stack->at());
    }
}
