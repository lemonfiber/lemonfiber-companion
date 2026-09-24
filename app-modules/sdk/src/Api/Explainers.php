<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Lemonfiber\Sdk\Contract\Api;
use Lemonfiber\Sdk\Exception\ApiVersionMismatch;
use Lemonfiber\Sdk\Exception\RequestFailed;
use Lemonfiber\Sdk\Exception\UnexpectedKind;
use Lemonfiber\Sdk\Exception\UnreadableResponse;
use Modules\Kernel\Api\Explaining;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatWasFoundOfTheWords;
use Modules\Sdk\Internal\WhatARefusalMeant;

/**
 * The one place this application asks a stack what lemonfiber's words mean.
 *
 * Written the way {@see Inspectors} is. It asks the explain endpoint naming no
 * word, which is how the whole glossary is asked for.
 */
final readonly class Explainers implements Explaining
{
    public function __construct(private Clients $clients) {}

    public function glossaryOn(Stack $stack, Session $session): WhatWasFoundOfTheWords
    {
        $client = $this->clients->client($stack, $session);

        try {
            $envelope = $client->read(Api::EXPLAIN_ENDPOINT);

            // Inside the same `try` as the request, for the argument
            // {@see Recorders::recordedOn()} makes.
            return WhatWasFoundOfTheWords::found(TheWordsExplained::in($envelope));
        } catch (RequestFailed $why) {
            return WhatWasFoundOfTheWords::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|UnreadableResponse|UnexpectedKind|GlossaryIsUnreadable) {
            return WhatWasFoundOfTheWords::met(Obstacle::StackDidNotAnswer);
        }
    }
}
