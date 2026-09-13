<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Lemonfiber\Sdk\Client;
use Modules\Kernel\Api\Reaching;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;

/**
 * The one place in this application that opens a connection to a stack.
 *
 * `N1-R16` says every call goes through the SDK and this app issues no request
 * of its own; `ADR-0018` says every connection is checked against the
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
 * which got it from the pairing material, which is where `N1-R18` says it comes
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
final readonly class PinnedClients implements Reaching
{
    /**
     * `Client` rather than the port's `object`, which rector asked for and is
     * right about: this file may name the SDK, so saying what it answers with
     * costs nothing and tells a caller that already depends on the SDK what it
     * has. The port stays `object` because `kernel` may not name the SDK at
     * all — that is `A7`, and it is what keeps every capability testable
     * without a network.
     */
    public function client(Stack $stack, Session $session): Client
    {
        return Client::pinnedAt(
            $stack->at()->forTheClient(),
            $session->forTheHeader(),
            $stack->presents()->forComparingByEye(),
        );
    }
}
