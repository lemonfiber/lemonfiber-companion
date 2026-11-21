<?php

declare(strict_types=1);

namespace Modules\Operator\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Operator\Internal\ViewModels\TheSettingChangeAsShown;

use function view;

/** What a change to the settings store comes to, as the stack reported it, on every screen that changes one through `settings-set`. */
final class WhatASettingChangeComesTo extends Component
{
    public function __construct(public readonly TheSettingChangeAsShown $change) {}

    public function render(): View
    {
        return view('operator::components.what-a-setting-change-comes-to');
    }
}
