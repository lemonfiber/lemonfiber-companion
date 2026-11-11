<?php

declare(strict_types=1);

namespace Modules\Design\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

use function view;

/**
 * A setting that is on or off, with its label beside it.
 *
 * The platform's own switch, so the state is drawn as the platform draws it and
 * read aloud as on or off rather than by colour. The tap names a method, and
 * the platform hands it the switch's new state as its last argument. A switch
 * that cannot be used now is drawn, never hidden.
 */
final class Toggle extends Component
{
    public readonly string $named;

    public function __construct(
        public readonly string $label,
        public readonly string $tap,
        public readonly bool $on = false,
        string $answersTo = '',
        public readonly bool $disabled = false,
    ) {
        $this->named = $answersTo === '' ? $label : $answersTo;
    }

    public function render(): View
    {
        return view('design::components.toggle');
    }
}
