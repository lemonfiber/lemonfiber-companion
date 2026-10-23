<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\SearchAction;
use Modules\Kernel\Api\AskingTheIndexers;
use Modules\Kernel\Api\Entropy;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheTraceSaysNothing;
use Modules\Kernel\Api\WhatBecameOfTheWork;
use Modules\Kernel\Api\WhatToFollow;
use Modules\Kernel\Api\WhereItGotTo;
use Modules\Sdk\Internal\TheTraceAsked;
use Modules\Sdk\Internal\TheWorkAsked;

/**
 * Asking the indexers through the SDK: `search` for the term and season a trace was read with, the search allowed to spend, followed to the trace it answers with.
 */
final readonly class Searchers implements AskingTheIndexers
{
    public function __construct(private Clients $clients, private Entropy $entropy) {}

    /**
     * @return WhatBecameOfTheWork<WhereItGotTo>
     */
    public function search(Stack $stack, Session $session, WhatToFollow $following): WhatBecameOfTheWork
    {
        $asked = TheTraceAsked::for($following);

        return $this->work()->sent($stack, $session, new SearchAction(disruptive: true, term: $asked->term, season: $asked->season));
    }

    /**
     * @return WhatBecameOfTheWork<WhereItGotTo>
     */
    public function whatBecameOf(Stack $stack, Session $session, Job $job): WhatBecameOfTheWork
    {
        try {
            return $this->work()->followed($stack, $session, $job, static fn(Envelope $envelope): WhereItGotTo => Traces::in($envelope));
        } catch (TraceIsUnreadable|TheTraceSaysNothing $why) {
            return WhatBecameOfTheWork::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    private function work(): TheWorkAsked
    {
        return new TheWorkAsked($this->clients, $this->entropy);
    }
}
