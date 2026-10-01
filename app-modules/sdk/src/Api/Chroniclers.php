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
use Modules\Kernel\Api\ReadingVersions;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\VersionIsBlank;
use Modules\Kernel\Api\WhatWasFoundOfTheVersions;
use Modules\Sdk\Internal\WhatARefusalMeant;

/**
 * The one place this application asks a stack which versions it runs.
 *
 * Written the way {@see Inspectors} is, against the version endpoint, which is a
 * read and changes nothing.
 */
final readonly class Chroniclers implements ReadingVersions
{
    public function __construct(private Clients $clients) {}

    public function versionsOn(Stack $stack, Session $session): WhatWasFoundOfTheVersions
    {
        $client = $this->clients->client($stack, $session);

        try {
            $envelope = $client->read(Api::VERSION_ENDPOINT);

            // Inside the same `try` as the request, for the argument
            // {@see Recorders::recordedOn()} makes.
            return WhatWasFoundOfTheVersions::found(TheVersions::in($envelope));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return WhatWasFoundOfTheVersions::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse|UnexpectedKind|ChangelogIsUnreadable|VersionsAreUnreadable|VersionIsBlank $why) {
            return WhatWasFoundOfTheVersions::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }
}
