<?php

declare(strict_types=1);

namespace Modules\Operator\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Operator\Internal\ViewModels\AGlossAsShown;

use function view;

/**
 * What a word just drawn means, drawn under it.
 *
 * One component rather than the same three lines on every screen that draws
 * one of lemonfiber's words, so the short gloss is in place and the longer one
 * a tap away in the same shape everywhere.
 */
final class Gloss extends Component
{
    public function __construct(public readonly AGlossAsShown $gloss) {}

    public function render(): View
    {
        return view('operator::components.gloss');
    }
}
