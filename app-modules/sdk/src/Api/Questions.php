<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Lemonfiber\Sdk\Contract\Api;
use Lemonfiber\Sdk\Exception\ApiVersionMismatch;
use Lemonfiber\Sdk\Exception\RequestFailed;
use Lemonfiber\Sdk\Exception\UnexpectedKind;
use Lemonfiber\Sdk\Exception\UnreadableResponse;
use Modules\Kernel\Api\Asking;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatCameBack;
use Modules\Sdk\Internal\WhatARefusalMeant;

/**
 * The one place this application asks a stack how it is.
 *
 * Every call to a stack goes through the SDK, so this sits beside
 * {@see PinnedClients} and {@see Admissions} for the reason the module boundary
 * exists: *reach a stack another way* has no spelling outside this directory.
 *
 * **It asks {@see PinnedClients} for the connection rather than building one.**
 * That is what keeps the pin in one file: `PinnedClients` names the SDK's
 * pinned constructor and nothing else does, so a second adapter reaching for
 * `Client::at()` would be a second decision about whether a certificate is
 * checked. This one cannot make that decision because it never names a client
 * constructor.
 *
 * **The class rather than the {@see Reaching} port it implements**, which is
 * the one place in this application that is right. The port answers `object`
 * so the kernel never names the SDK's client — that is what the port is *for*
 * — and a caller needing to call a method on it would have to narrow, which is
 * a branch nothing can reach and nothing can test. Inside this module the real
 * type is available and no boundary is crossed by using it.
 *
 * **Four raises, three answers, and the collapse is deliberate.** The endpoint
 * refusing, the answer being unreadable, and the client and the stack
 * disagreeing about the API version are three faults with one meaning for
 * somebody looking at a phone: they cannot see their stack and the machine is
 * where to look. An obstacle's distinctions are the ones an operator can act on
 * differently, and *upgrade one of the two halves* is not advice a companion
 * app can give from here — it is what the diagnostic report itself would say.
 *
 * The exception is a refused session, which is `401` and is a different
 * sentence: the operator signs in again, and nothing about the machine is
 * wrong. That distinction is the reason this reads a status rather than
 * treating every `RequestFailed` alike.
 *
 * **A `ConfigurationProblem` is deliberately not caught**, for the reason
 * {@see Admissions} gives at more length: the SDK raises one where a stored
 * stack cannot be pinned, which means this app is holding a stack it should
 * never have written down. Catching it would turn a fault in retained state
 * into an ordinary screen about an unreachable machine.
 */
final readonly class Questions implements Asking
{
    public function __construct(private Clients $clients) {}

    public function about(Stack $stack, Session $session): WhatCameBack
    {
        $client = $this->clients->client($stack, $session);

        try {
            $envelope = $client->read(Api::CHECKS_ENDPOINT);

            // Inside the same `try` as the request, deliberately. A report the
            // client fetched and this side could not read is the same thing to
            // an operator as one that never arrived: they cannot see their
            // stack. Reading it outside would make an unreadable answer an
            // uncaught raise on a screen, which is the one outcome that is
            // worse than either.
            return WhatCameBack::report(Reports::in($envelope));
        } catch (RequestFailed $why) {
            return WhatCameBack::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|UnreadableResponse|UnexpectedKind|ReportIsUnreadable) {
            return WhatCameBack::met(Obstacle::StackDidNotAnswer);
        }
    }
}
