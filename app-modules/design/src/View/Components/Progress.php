<?php

declare(strict_types=1);

namespace Modules\Design\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

use function max;
use function min;
use function view;

/**
 * How far something has got, as the platform's own bar, read aloud as the words it is given.
 *
 * The bar is never the only place the figure is: a reader hears `said`, and a
 * screen draws the same words beside it. A value below 0 or past 1 is drawn at
 * the nearer end.
 */
final class Progress extends Component
{
    public readonly float $value;

    /**
     * @param float  $done how much is done, from 0 to 1
     * @param string $said what a screen reader says for the bar
     */
    public function __construct(float $done, public readonly string $said)
    {
        $this->value = max(0.0, min(1.0, $done));
    }

    public function render(): View
    {
        return view('design::components.progress');
    }
}
