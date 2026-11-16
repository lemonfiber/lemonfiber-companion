<?php

declare(strict_types=1);

namespace Modules\Household\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Household\Internal\ViewModels\WhatAChannelCameTo;

use function view;

/**
 * What came of pressing a channel, or how it stopped, said beside it: on Home's Live TV shelf and above the guide.
 *
 * Nothing where it plays. The core's own words are drawn as written, and only
 * this app's lines go through the catalogue.
 */
final class BesideAChannel extends Component
{
    public function __construct(public readonly ?WhatAChannelCameTo $came) {}

    public function render(): View
    {
        return view('household::components.beside-a-channel');
    }
}
