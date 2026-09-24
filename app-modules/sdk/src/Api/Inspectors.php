<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Lemonfiber\Sdk\Contract\Api;
use Lemonfiber\Sdk\Exception\ApiVersionMismatch;
use Lemonfiber\Sdk\Exception\RequestFailed;
use Lemonfiber\Sdk\Exception\UnexpectedKind;
use Lemonfiber\Sdk\Exception\UnreadableResponse;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\SelfChecking;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatWasFoundOfItself;
use Modules\Sdk\Api\Fields\SelfUpdateField;
use Modules\Sdk\Internal\WhatARefusalMeant;

/**
 * The one place this application asks a stack about its running copy of lemonfiber.
 *
 * Written the way {@see Surveyors} is. It asks the update endpoint about
 * `self`, which is a read: the core answers with the command that would update
 * this copy and replaces nothing.
 */
final readonly class Inspectors implements SelfChecking
{
    public function __construct(private Clients $clients) {}

    public function checkedOn(Stack $stack, Session $session): WhatWasFoundOfItself
    {
        $client = $this->clients->client($stack, $session);

        try {
            $envelope = $client->read(Api::UPDATE_ENDPOINT, [WireField::What->value => SelfUpdateField::ThisCopy->value]);

            // Inside the same `try` as the request, for the argument
            // {@see Recorders::recordedOn()} makes.
            return WhatWasFoundOfItself::found(WhereThisCopyIs::in($envelope));
        } catch (RequestFailed $why) {
            return WhatWasFoundOfItself::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|UnreadableResponse|UnexpectedKind|SelfUpdateIsUnreadable) {
            return WhatWasFoundOfItself::met(Obstacle::StackDidNotAnswer);
        }
    }
}
