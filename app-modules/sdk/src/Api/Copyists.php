<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Lemonfiber\Sdk\Contract\Api;
use Lemonfiber\Sdk\Exception\ApiVersionMismatch;
use Lemonfiber\Sdk\Exception\RequestFailed;
use Lemonfiber\Sdk\Exception\UnexpectedKind;
use Lemonfiber\Sdk\Exception\Unreachable;
use Lemonfiber\Sdk\Exception\UnreadableResponse;
use Modules\Kernel\Api\Copying;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatCopiesWereFound;
use Modules\Sdk\Internal\WhatARefusalMeant;

/**
 * The one place this application asks a stack which copies of itself it holds.
 *
 * Written as {@see Storekeepers} is. The listing is asked of the endpoint named
 * for what an operator calls these — their backups — and answers with the
 * archives the stack keeps, by name.
 *
 * **A listing that could not be read is never an empty one.** *No copy has been
 * taken* is the answer somebody would believe, and it is the one this refuses
 * to draw for a machine that did not answer.
 */
final readonly class Copyists implements Copying
{
    public function __construct(private Clients $clients) {}

    public function copiesOn(Stack $stack, Session $session): WhatCopiesWereFound
    {
        $client = $this->clients->client($stack, $session);

        try {
            $envelope = $client->read(Api::BACKUPS_ENDPOINT);

            // Inside the same `try` as the request, for the argument
            // {@see Recorders::recordedOn()} makes.
            return WhatCopiesWereFound::copies(TheArchives::in($envelope));
        } catch (RequestFailed $why) {
            return WhatCopiesWereFound::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse|UnexpectedKind|ArchivesAreUnreadable) {
            return WhatCopiesWereFound::met(Obstacle::StackDidNotAnswer);
        }
    }
}
