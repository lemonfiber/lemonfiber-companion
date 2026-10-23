<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\DiagnoseAction;
use Modules\Kernel\Api\ACheckThatDisturbs;
use Modules\Kernel\Api\ADisturbanceSaysNothing;
use Modules\Kernel\Api\CheckGaveNoReason;
use Modules\Kernel\Api\CheckIsUnnamed;
use Modules\Kernel\Api\CheckSaidNothing;
use Modules\Kernel\Api\CodeIsBlank;
use Modules\Kernel\Api\Entropy;
use Modules\Kernel\Api\FindingHasNoTitle;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\RemedySaysNothing;
use Modules\Kernel\Api\Report;
use Modules\Kernel\Api\RunningACheck;
use Modules\Kernel\Api\ServiceIsUnnamed;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatBecameOfTheWork;
use Modules\Sdk\Internal\TheWorkAsked;

/**
 * Running a check that disturbs through the SDK: `diagnose` narrowed to what the stack named for it, with the checks that disturb included, followed to the report it answers with.
 */
final readonly class CheckRunners implements RunningACheck
{
    public function __construct(private Clients $clients, private Entropy $entropy) {}

    /**
     * @return WhatBecameOfTheWork<Report>
     */
    public function run(Stack $stack, Session $session, ACheckThatDisturbs $check): WhatBecameOfTheWork
    {
        return $this->work()->sent($stack, $session, new DiagnoseAction(disruptive: true, only: $check->narrowedTo()));
    }

    /**
     * @return WhatBecameOfTheWork<Report>
     */
    public function whatBecameOf(Stack $stack, Session $session, Job $job): WhatBecameOfTheWork
    {
        try {
            return $this->work()->followed($stack, $session, $job, static fn(Envelope $envelope): Report => Reports::in($envelope));
        } catch (ReportIsUnreadable|CheckIsUnnamed|ServiceIsUnnamed|FindingHasNoTitle|CodeIsBlank|CheckSaidNothing|RemedySaysNothing|CheckGaveNoReason|ADisturbanceSaysNothing $why) {
            return WhatBecameOfTheWork::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    private function work(): TheWorkAsked
    {
        return new TheWorkAsked($this->clients, $this->entropy);
    }
}
