<?php

declare(strict_types=1);

namespace Modules\Operator\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Operator\Internal\ViewModels\HowPuttingARunBackWent;

use function view;

/**
 * What putting changes back would come to, or came to: what was left first, then what went back.
 *
 * One component for every report the rollback layer gives, a run put back
 * and the changes an install, an update or a removal takes off the machine,
 * because it is the one report and two drawings of it would come to word a
 * rehearsal as though it had happened.
 */
final class WhatGoingBackCameTo extends Component
{
    public function __construct(public readonly HowPuttingARunBackWent $report) {}

    public function render(): View
    {
        return view('operator::components.what-going-back-came-to');
    }
}
