<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\BandwidthAction;
use Modules\Kernel\Api\ALineChange;
use Modules\Kernel\Api\Entropy;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\LineSaysNothing;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\SharingTheLine;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheLineAsChanged;
use Modules\Kernel\Api\WhatBecameOfTheWork;
use Modules\Sdk\Internal\TheWorkAsked;

/**
 * Changing how a stack shares its line through the SDK: the `bandwidth` action, rehearsed, carried out, and followed.
 *
 * Every part of the change is sent as the operator wrote it and read in the
 * core. A refusal is the stack's own sentence and remedy where it sent a
 * problem document, and an obstacle everywhere else.
 */
final readonly class Linesmen implements SharingTheLine
{
    public function __construct(private Clients $clients, private Entropy $entropy) {}

    /**
     * @return WhatBecameOfTheWork<TheLineAsChanged>
     */
    public function rehearse(Stack $stack, Session $session, ALineChange $change): WhatBecameOfTheWork
    {
        return $this->work()->sent($stack, $session, $this->action($change)->rehearsed());
    }

    /**
     * @return WhatBecameOfTheWork<TheLineAsChanged>
     */
    public function carryOut(Stack $stack, Session $session, ALineChange $change): WhatBecameOfTheWork
    {
        return $this->work()->sent($stack, $session, $this->action($change));
    }

    /**
     * @return WhatBecameOfTheWork<TheLineAsChanged>
     */
    public function whatBecameOf(Stack $stack, Session $session, Job $job): WhatBecameOfTheWork
    {
        try {
            return $this->work()->followed($stack, $session, $job, static fn(Envelope $envelope): TheLineAsChanged => WhatTheLineCameTo::in($envelope));
        } catch (BandwidthIsUnreadable|LineSaysNothing $why) {
            return WhatBecameOfTheWork::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    private function action(ALineChange $change): BandwidthAction
    {
        $written = [];

        foreach ($change as $part) {
            $written[$part->part()->value] = $part->text();
        }

        return $change->lift()->either(
            for: static fn(int $minutes): BandwidthAction => new BandwidthAction(...$written, unrestrictedFor: $minutes),
            none: static fn(): BandwidthAction => new BandwidthAction(...$written),
        );
    }

    private function work(): TheWorkAsked
    {
        return new TheWorkAsked($this->clients, $this->entropy);
    }
}
