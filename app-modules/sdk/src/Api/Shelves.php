<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Lemonfiber\Sdk\Contract\Api;
use Lemonfiber\Sdk\Exception\ApiVersionMismatch;
use Lemonfiber\Sdk\Exception\RequestFailed;
use Lemonfiber\Sdk\Exception\UnexpectedKind;
use Lemonfiber\Sdk\Exception\Unreachable;
use Lemonfiber\Sdk\Exception\UnreadableResponse;
use Modules\Kernel\Api\HoldingIsUnnamed;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\SentenceSaysNothing;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Watching;
use Modules\Kernel\Api\WhatTheyMayWatch;
use Modules\Kernel\Api\Whose;
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
                => WhatTheyMayWatch::refused(Obstacle::NotForThisAccount),
            member: fn(string $member): WhatTheyMayWatch => $this->read($stack, $session, $member),
        );
    }

    /** The shelf that member's account can see, or why it could not be had. */
    private function read(Stack $stack, Session $session, string $member): WhatTheyMayWatch
    {
        $client = $this->clients->client($stack, $session);

        try {
            $envelope = $client->read(Api::HELD_ENDPOINT, ['member' => $member]);

            // Inside the same `try` as the request, for {@see TheirOwn}'s
            // reason: a payload the client fetched and this side could not
            // read is the same thing to whoever is looking at the screen as
            // one that never arrived.
            return Holdings::in($envelope);
        } catch (RequestFailed $why) {
            return WhatTheyMayWatch::refused(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse|UnexpectedKind|ShelfIsUnreadable|HoldingIsUnnamed|SentenceSaysNothing) {
            return WhatTheyMayWatch::refused(Obstacle::StackDidNotAnswer);
        }
    }
}
