<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Lemonfiber\Sdk\Contract\Api;
use Lemonfiber\Sdk\Exception\ApiVersionMismatch;
use Lemonfiber\Sdk\Exception\RequestFailed;
use Lemonfiber\Sdk\Exception\UnexpectedKind;
use Lemonfiber\Sdk\Exception\UnreadableResponse;
use Modules\Kernel\Api\Arranging;
use Modules\Kernel\Api\HowItIsSet;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\SettingIsUnnamed;
use Modules\Kernel\Api\Stack;
use Modules\Sdk\Internal\WhatARefusalMeant;

/**
 * {@see Arranging}, answered by asking the stack.
 *
 * Holds nothing between calls. The listing is the stack's and is fetched every
 * time it is shown, because a cached one is exactly the silent subset the
 * requirement forbids: it would be right until the stack gained a setting and
 * then be quietly short, with nothing on the screen saying when it was taken.
 */
final readonly class Arrangements implements Arranging
{
    public function __construct(private Clients $clients) {}

    public function asItStands(Stack $stack, Session $session): HowItIsSet
    {
        $client = $this->clients->client($stack, $session);

        try {
            $envelope = $client->read(Api::CONFIG_ENDPOINT);

            // Inside the same `try` as the request, for {@see TheirOwn}'s
            // reason: a payload the client fetched and this side could not read
            // is the same thing to the operator as one that never arrived.
            return HowItIsSet::told(Dials::in($envelope));
        } catch (RequestFailed $why) {
            return HowItIsSet::refused(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|UnreadableResponse|UnexpectedKind|SettingIsUnreadable|SettingIsUnnamed) {
            // {@see SettingIsUnnamed} is here rather than guarded against
            // above, so the kernel type keeps deciding what a nameless setting
            // means and this decides what an unreadable answer looks like to
            // whoever is at the screen. A stack that sent a row this app
            // cannot show sent a listing this app cannot claim is complete,
            // and an incomplete listing is the one thing this screen must
            // never present as a whole one.
            return HowItIsSet::refused(Obstacle::StackDidNotAnswer);
        }
    }
}
