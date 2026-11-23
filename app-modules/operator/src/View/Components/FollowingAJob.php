<?php

declare(strict_types=1);

namespace Modules\Operator\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Operator\Internal\ViewModels\WhatAJobSaidAsShown;

use function view;

/** What a running job followed has said, in the stack's words: its newest line, and every line on request. */
final class FollowingAJob extends Component
{
    public function __construct(public readonly WhatAJobSaidAsShown $said) {}

    public function render(): View
    {
        return view('operator::components.following-a-job');
    }
}
