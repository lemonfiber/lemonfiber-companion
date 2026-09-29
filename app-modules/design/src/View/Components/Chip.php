<?php

declare(strict_types=1);

namespace Modules\Design\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

use function view;

/**
 * One choice in a row of chips, drawn as chosen or not: which part of a list
 * is showing.
 */
final class Chip extends Component
{
    public readonly string $named;

    public function __construct(
        public readonly string $label,
        public readonly string $tap,
        public readonly bool $chosen = false,
        string $answersTo = '',
    ) {
        $this->named = $answersTo === '' ? $label : $answersTo;
    }

    public function render(): View
    {
        return view('design::components.chip');
    }
}
