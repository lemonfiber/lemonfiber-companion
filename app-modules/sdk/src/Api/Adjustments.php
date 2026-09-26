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
use Modules\Kernel\Api\Adjusting;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\SettingIsUnnamed;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatTheStackMadeOfIt;
use Modules\Kernel\Api\WhatToChange;
use Modules\Kernel\Api\WhatToSet;
use Modules\Sdk\Internal\WhatARefusalMeant;

/**
 * {@see Adjusting}, answered by asking the stack.
 *
 * **One action, two calls, and the difference is the `agreed` argument.** The
 * core reads the same `config-set` unconfirmed as a review and confirmed as a
 * write, so both methods here reach the same name and differ by one value —
 * which is exactly why the port has two methods rather than one with a flag.
 * The flag exists; it is spelled once, in a named private method, and no
 * caller ever holds it.
 */
final readonly class Adjustments implements Adjusting
{
    public function __construct(private Clients $clients) {}

    public function wouldBe(Stack $stack, Session $session, WhatToSet $asked): WhatTheStackMadeOfIt
    {
        return $this->asking($stack, $session, $asked, agreed: false);
    }

    public function agreedTo(Stack $stack, Session $session, WhatToSet $asked): WhatTheStackMadeOfIt
    {
        return $this->asking($stack, $session, $asked, agreed: true);
    }

    /**
     * The one call both methods make.
     *
     * `agreed` is a named argument at both call sites above and never a
     * positional one, which is the whole protection: the two lines that decide
     * whether somebody's stack is written to read as `agreed: false` and
     * `agreed: true` rather than as `false` and `true` at the end of an
     * argument list.
     */
    private function asking(
        Stack $stack,
        Session $session,
        WhatToSet $asked,
        bool $agreed,
    ): WhatTheStackMadeOfIt {
        $client = $this->clients->client($stack, $session);

        try {
            $envelope = $client->act(Api::action(WhatToChange::Setting->asked()), [
                'key' => $asked->key,
                'value' => $asked->value,
                'agreed' => $agreed,
            ]);

            // Inside the same `try` as the call, for {@see Arrangements}'
            // reason: an answer the client fetched and this side could not
            // read is the same thing to the operator as one that never
            // arrived — and on a write, *did it happen* is the question.
            return WhatTheStackMadeOfIt::said(Dials::reviewIn($envelope));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return WhatTheStackMadeOfIt::refused(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse|UnexpectedKind|SettingIsUnreadable|SettingIsUnnamed) {
            return WhatTheStackMadeOfIt::refused(Obstacle::StackDidNotAnswer);
        }
    }
}
