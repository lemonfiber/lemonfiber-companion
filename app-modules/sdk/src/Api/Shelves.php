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
use Modules\Kernel\Api\HoldingId;
use Modules\Kernel\Api\HoldingIsUnnamed;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\SentenceSaysNothing;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Watching;
use Modules\Kernel\Api\WhatTheTitleIs;
use Modules\Kernel\Api\WhatTheyArePartWayThrough;
use Modules\Kernel\Api\WhatTheyMayWatch;
use Modules\Kernel\Api\Whose;
use Modules\Sdk\Internal\AsTheHouseholdsDefaults;
use Modules\Sdk\Internal\GatedClient;
use Modules\Sdk\Internal\OneTitle;
use Modules\Sdk\Internal\WhatARefusalMeant;
use Override;

/**
 * What the stack says one member may watch.
 *
 * {@see TheirOwn} one endpoint over and written the same way: it asks
 * {@see Clients} for the connection rather than building one, which is what
 * keeps the certificate pin in a single file.
 *
 * **The operator has no shelf, and that is an answer rather than an error.**
 * The reading is made *as* an account, so there is nobody to read it as when
 * the session is the operator's. Folded rather than guarded, so the branch
 * cannot be the one somebody forgets — and answered as not theirs to ask,
 * which is what it is.
 */
final readonly class Shelves implements Watching
{
    public function __construct(private Clients $clients) {}

    #[Override]
    public function theShelfOf(Stack $stack, Session $session, Whose $whose): WhatTheyMayWatch
    {
        return $whose->either(
            operator: static fn(): WhatTheyMayWatch
                => WhatTheyMayWatch::refused(Obstacle::of(KindOfObstacle::NotForThisAccount)),
            member: fn(string $member): WhatTheyMayWatch => $this->read($stack, $session, ['member' => $member]),
        );
    }

    #[Override]
    public function theTitle(Stack $stack, Session $session, Whose $whose, HoldingId $title): WhatTheTitleIs
    {
        return $whose->either(
            operator: static fn(): WhatTheTitleIs => WhatTheTitleIs::refused(Obstacle::of(KindOfObstacle::NotForThisAccount)),
            member: fn(string $member): WhatTheTitleIs => $this->readTheTitle($stack, $session, $title, $member),
        );
    }

    #[Override]
    public function partWayThrough(Stack $stack, Session $session, Whose $whose): WhatTheyArePartWayThrough
    {
        return $whose->either(
            operator: static fn(): WhatTheyArePartWayThrough => WhatTheyArePartWayThrough::refused(Obstacle::of(KindOfObstacle::NotForThisAccount)),
            member: fn(string $member): WhatTheyArePartWayThrough => $this->readWhereTheyLeftOff($stack, $session, $member),
        );
    }

    #[Override]
    public function theDefaultShelf(Stack $stack, Session $session): WhatTheyMayWatch
    {
        return $this->read($stack, $session, AsTheHouseholdsDefaults::QUERY);
    }

    /** What that member's account was part-way through, or why it could not be had. */
    private function readWhereTheyLeftOff(Stack $stack, Session $session, string $member): WhatTheyArePartWayThrough
    {
        $client = GatedClient::of($this->clients, $stack, $session);

        try {
            return WhatTheyArePartWayThrough::told(WhereTheyLeftOff::in($client->read(Api::WATCHING_ENDPOINT, ['member' => $member])));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return WhatTheyArePartWayThrough::refused(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|TheStackDoesNotOfferIt|UnreadableResponse|UnexpectedKind|PartWayIsUnreadable|HoldingIsUnnamed|SentenceSaysNothing $why) {
            return WhatTheyArePartWayThrough::refused($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    /** One title as that member's account reads it, or why it could not be had. */
    private function readTheTitle(Stack $stack, Session $session, HoldingId $title, string $member): WhatTheTitleIs
    {
        $client = GatedClient::of($this->clients, $stack, $session);

        try {
            return Titles::in($client->readOneOf(OneTitle::asDeclared(), Api::title($title->named()), ['member' => $member]));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return WhatTheTitleIs::refused(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|TheStackDoesNotOfferIt|UnreadableResponse|UnexpectedKind|TitleIsUnreadable|HoldingIsUnnamed|SentenceSaysNothing $why) {
            return WhatTheTitleIs::refused($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    /**
     * The shelf that member's account can see, or why it could not be had.
     *
     * @param array<string, string> $query whose shelf, as the read is asked
     */
    private function read(Stack $stack, Session $session, array $query): WhatTheyMayWatch
    {
        $client = GatedClient::of($this->clients, $stack, $session);

        try {
            $envelope = $client->read(Api::HELD_ENDPOINT, $query);

            // Inside the same `try` as the request, for {@see TheirOwn}'s
            // reason: a payload the client fetched and this side could not
            // read is the same thing to whoever is looking at the screen as
            // one that never arrived.
            return Holdings::in($envelope);
        } catch (CertificateWasRefused|RequestFailed $why) {
            return WhatTheyMayWatch::refused(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|TheStackDoesNotOfferIt|UnreadableResponse|UnexpectedKind|ShelfIsUnreadable|HoldingIsUnnamed|SentenceSaysNothing $why) {
            return WhatTheyMayWatch::refused($this->clients->whatStoodInTheWay($stack, $why));
        }
    }
}
