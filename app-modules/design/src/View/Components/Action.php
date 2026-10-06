<?php

declare(strict_types=1);

namespace Modules\Design\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Design\View\Prominence;

use function view;

/**
 * A filled button, as tall as a thumb needs, drawn as loudly as its {@see Prominence} says.
 *
 * `carries` is what a button that goes somewhere hands the screen it opens,
 * besides the route, as a link's does.
 */
final class Action extends Component
{
    public readonly string $named;

    public readonly string $variant;

    public function __construct(
        public readonly string $label,
        public readonly string $tap = '',
        public readonly string $goes = '',
        public readonly bool $disabled = false,
        string $tone = 'primary',
        string $answersTo = '',
        /** @var array<string, mixed> what the screen it opens is handed, besides the route */
        public readonly array $carries = [],
    ) {
        $this->named = $answersTo === '' ? $label : $answersTo;
        $this->variant = Prominence::from($tone)->variant();
    }

    public function render(): View
    {
        return view('design::components.action');
    }
}
