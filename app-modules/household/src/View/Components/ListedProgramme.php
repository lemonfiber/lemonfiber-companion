<?php

declare(strict_types=1);

namespace Modules\Household\View\Components;

use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Household\Internal\ViewModels\WhatACellSays;

use function view;

/**
 * One programme in the list several drawn together open: its title, and when it starts and ends beneath, across the list, pressed as its own cell would be.
 *
 * Read as one element, as {@see GuideCell} is, and said as its cell would be.
 */
final class ListedProgramme extends Component
{
    /** What a screen reader says for it. */
    public readonly string $named;

    public function __construct(Translator $catalogue, public readonly WhatACellSays $programme, string $channel)
    {
        $this->named = GuideCell::said($catalogue, $programme, $channel);
    }

    public function render(): View
    {
        return view('household::components.listed-programme');
    }
}
