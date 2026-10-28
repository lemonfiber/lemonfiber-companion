<?php

declare(strict_types=1);

namespace Modules\Operator\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

use function view;

/**
 * One figure given a block of its own: what it is above it, and what it means under it.
 *
 * The figure is set in the figures face at the brand's smallest display size,
 * with its unit and the whole it is part of beside it, small and faint. A
 * figure nobody measured is said in words instead, and then carries neither
 * a unit nor a whole, which beside words would read as a measurement of them.
 * The figure is always set in the text role: a figure that wants the operator
 * says so through the port or the state's glyph beside it, because a severity
 * colours a glyph and never the words or the figure.
 */
final class Figure extends Component
{
    /**
     * @param string      $figure  the figure as it reads
     * @param string      $eyebrow what the figure is, above it
     * @param string|null $unit    what the figure is measured in, beside it, where it is measured in anything
     * @param string      $caption what the figure means, under it
     */
    public function __construct(
        public readonly string $figure,
        public readonly string $eyebrow = '',
        public readonly ?string $unit = null,
        public readonly string $caption = '',
    ) {}

    public function render(): View
    {
        return view('operator::components.figure');
    }
}
