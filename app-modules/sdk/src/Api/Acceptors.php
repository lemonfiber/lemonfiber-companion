<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\AcceptAction;
use Modules\Kernel\Api\Accepting;
use Modules\Kernel\Api\Check;
use Modules\Kernel\Api\CheckGaveNoReason;
use Modules\Kernel\Api\CheckIsUnnamed;
use Modules\Kernel\Api\CheckSaidNothing;
use Modules\Kernel\Api\CodeIsBlank;
use Modules\Kernel\Api\Entropy;
use Modules\Kernel\Api\FindingHasNoTitle;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\RemedySaysNothing;
use Modules\Kernel\Api\Report;
use Modules\Kernel\Api\ServiceIsUnnamed;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatBecameOfTheWork;
use Modules\Sdk\Internal\TheWorkAsked;

/**
 * Accepting a warning through the SDK: the `accept` action for one check, and following it to the report it answers with.
 *
 * The checks that disturb the running system are left out of the run it
 * answers with: accepting a warning is not agreeing to them.
 */
final readonly class Acceptors implements Accepting
{
    public function __construct(private Clients $clients, private Entropy $entropy) {}

    /**
     * @return WhatBecameOfTheWork<Report>
     */
    public function accept(Stack $stack, Session $session, Check $check): WhatBecameOfTheWork
    {
        return $this->work()->sent($stack, $session, new AcceptAction(disruptive: false, check: $check->shown()));
    }

    /**
     * @return WhatBecameOfTheWork<Report>
     */
    public function whatBecameOf(Stack $stack, Session $session, Job $job): WhatBecameOfTheWork
    {
        try {
            return $this->work()->followed($stack, $session, $job, static fn(Envelope $envelope): Report => Reports::in($envelope));
        } catch (ReportIsUnreadable|CheckIsUnnamed|ServiceIsUnnamed|FindingHasNoTitle|CodeIsBlank|CheckSaidNothing|RemedySaysNothing|CheckGaveNoReason $why) {
            return WhatBecameOfTheWork::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    private function work(): TheWorkAsked
    {
        return new TheWorkAsked($this->clients, $this->entropy);
    }
}
