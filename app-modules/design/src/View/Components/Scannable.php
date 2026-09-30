<?php

declare(strict_types=1);

namespace Modules\Design\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

use function view;

/**
 * A code another device scans off this screen, drawn square by square.
 *
 * Dark squares on the accent, the two roles that hold in light and dark alike,
 * so the code reads the same whichever the phone is set to. Where there is no
 * code it draws the words it is given instead, because an empty square is one
 * somebody would try to scan.
 */
final class Scannable extends Component
{
    /**
     * @param list<list<bool>> $rows    the code's rows, top to bottom, dark where true
     * @param string           $missing what is said where there is no code to draw
     */
    public function __construct(public readonly array $rows, public readonly string $missing) {}

    public function render(): View
    {
        return view('design::components.scannable');
    }
}
