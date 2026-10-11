<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Lemonfiber\Sdk\Exception\ApiVersionMismatch;
use Lemonfiber\Sdk\Exception\CertificateWasRefused;
use Lemonfiber\Sdk\Exception\RequestFailed;
use Lemonfiber\Sdk\Exception\UnexpectedKind;
use Lemonfiber\Sdk\Exception\Unreachable;
use Lemonfiber\Sdk\Exception\UnreadableResponse;
use Lemonfiber\Sdk\Generated\AskAction;
use Modules\Kernel\Api\AFoundTitle;
use Modules\Kernel\Api\AskingForTitles;
use Modules\Kernel\Api\Entropy;
use Modules\Kernel\Api\FoundTitleSaysNothing;
use Modules\Kernel\Api\HowAnAskWent;
use Modules\Kernel\Api\IdempotencyKey;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Sdk\Internal\GatedClient;
use Modules\Sdk\Internal\WhatARefusalMeant;
use Override;

/**
 * Asking for a title through the core's `ask` action, which answers at once rather than with a job.
 *
 * No `name` is sent, so the core asks as the member the session belongs to.
 * No season is named either, which asks for every season not already here or
 * on its way. The rehearsal sends nothing and carries a key of its own, since a
 * rehearsal sent twice still changes nothing.
 */
final readonly class Askers implements AskingForTitles
{
    public function __construct(private Clients $clients, private Entropy $entropy) {}

    #[Override]
    public function rehearse(Stack $stack, Session $session, AFoundTitle $title): HowAnAskWent
    {
        return $this->act($stack, $session, $this->askFor($title)->rehearsed(), IdempotencyKey::from($this->entropy->nonce()));
    }

    #[Override]
    public function ask(Stack $stack, Session $session, AFoundTitle $title, IdempotencyKey $key): HowAnAskWent
    {
        return $this->act($stack, $session, $this->askFor($title), $key);
    }

    private function askFor(AFoundTitle $title): AskAction
    {
        return new AskAction(id: $title->id(), kind: $title->kind()->value);
    }

    private function act(Stack $stack, Session $session, AskAction $action, IdempotencyKey $key): HowAnAskWent
    {
        $client = GatedClient::of($this->clients, $stack, $session);

        try {
            return HowAnAskWent::answered(WhatAnAskCameToRead::in($client->act($action, $key->sent())));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return HowAnAskWent::refused(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|TheStackDoesNotOfferIt|UnreadableResponse|UnexpectedKind|SearchIsUnreadable|FoundTitleSaysNothing $why) {
            return HowAnAskWent::refused($this->clients->whatStoodInTheWay($stack, $why));
        }
    }
}
