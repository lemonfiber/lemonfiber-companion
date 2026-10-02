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
use Modules\Kernel\Api\CheckIsUnnamed;
use Modules\Kernel\Api\ReadingNews;
use Modules\Kernel\Api\RequestIsUnnumbered;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\VersionIsBlank;
use Modules\Kernel\Api\WhatWasFoundOfTheNews;
use Modules\Sdk\Internal\WhatARefusalMeant;

/**
 * The one place this application asks a stack what it can mark as new.
 *
 * Written the way {@see Doorkeepers} is, against the news endpoint, which is a
 * read and changes nothing: a list that could not be read is never a stack with
 * nothing new.
 */
final readonly class Newsreaders implements ReadingNews
{
    public function __construct(private Clients $clients) {}

    public function newsOn(Stack $stack, Session $session): WhatWasFoundOfTheNews
    {
        $client = $this->clients->client($stack, $session);

        try {
            $envelope = $client->read(Api::NEWS_ENDPOINT);

            // Inside the same `try` as the request, for the argument
            // {@see Recorders::recordedOn()} makes.
            return WhatWasFoundOfTheNews::found(WhatIsListedAsNew::in($envelope));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return WhatWasFoundOfTheNews::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse|UnexpectedKind|NewsIsUnreadable|VersionIsBlank|RequestIsUnnumbered|CheckIsUnnamed $why) {
            return WhatWasFoundOfTheNews::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }
}
