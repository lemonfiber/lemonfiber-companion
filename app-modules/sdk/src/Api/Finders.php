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
use Modules\Kernel\Api\FindingTitles;
use Modules\Kernel\Api\FoundTitleSaysNothing;
use Modules\Kernel\Api\HowASearchWent;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatToLookFor;
use Modules\Kernel\Api\WhereTheNextPageBegins;
use Modules\Sdk\Internal\GatedClient;
use Modules\Sdk\Internal\WhatARefusalMeant;
use Override;

/**
 * A member's search, asked of the core's `/api/askable` on the session that searches.
 *
 * No `member` is named: a member's own session is searched as that member, and
 * an operator's, which names nobody, is refused by the core in its own words.
 */
final readonly class Finders implements FindingTitles
{
    public function __construct(private Clients $clients) {}

    #[Override]
    public function firstPage(Stack $stack, Session $session, WhatToLookFor $words): HowASearchWent
    {
        return $this->read($stack, $session, ['term' => $words->said()]);
    }

    #[Override]
    public function pageAfter(Stack $stack, Session $session, WhatToLookFor $words, WhereTheNextPageBegins $next): HowASearchWent
    {
        return $this->read($stack, $session, ['term' => $words->said(), 'after' => $next->said()]);
    }

    /** @param array<string, string> $query */
    private function read(Stack $stack, Session $session, array $query): HowASearchWent
    {
        $client = GatedClient::of($this->clients, $stack, $session);

        try {
            return HowASearchWent::found(WhatASearchCameTo::in($client->read(Api::ASKABLE_ENDPOINT, $query)));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return HowASearchWent::refused(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|TheStackDoesNotOfferIt|UnreadableResponse|UnexpectedKind|SearchIsUnreadable|FoundTitleSaysNothing $why) {
            return HowASearchWent::refused($this->clients->whatStoodInTheWay($stack, $why));
        }
    }
}
