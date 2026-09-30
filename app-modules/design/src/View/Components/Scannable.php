<?php

declare(strict_types=1);

namespace Modules\Design\View\Components;

use function count;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Design\View\ADarkRun;

use function view;

/**
 * A code another device scans off this screen.
 *
 * Dark squares on the accent, the two roles that hold in light and dark alike,
 * so the code reads the same whichever the phone is set to, inside a border of
 * the accent four squares wide, which is the margin a reader finds the code
 * by. Where there is no code it draws the words it is given instead, because an
 * empty square is one somebody would try to scan.
 *
 * Each row's dark squares are drawn as runs, each placed on its own: a pairing line is a code of some fifty squares a side, and a square
 * apiece is more elements than one frame carries.
 */
final class Scannable extends Component
{
    /** @var list<ADarkRun> every run of dark squares, row by row */
    public readonly array $runs;

    /** How wide and tall the code is drawn with its margin, in points. */
    public readonly int $side;

    /** Whether there is a code to draw, which there is not where no square is dark. */
    public readonly bool $drawn;

    /**
     * @param list<list<bool>> $rows    the code's rows, top to bottom, dark where true
     * @param string           $missing what is said where there is no code to draw
     * @param int              $square  how wide and tall one square is drawn, in points
     * @param int              $margin  how many squares of the accent surround the code
     */
    public function __construct(
        array $rows,
        public readonly string $missing,
        public readonly int $square = 4,
        int $margin = 4,
    ) {
        $this->side = (count($rows) + 2 * $margin) * $square;
        $runs = [];

        foreach ($rows as $y => $row) {
            $runs = [...$runs, ...$this->runsOf($row, $margin + $y, $margin)];
        }

        $this->runs = $runs;
        $this->drawn = $runs !== [];
    }

    public function render(): View
    {
        return view('design::components.scannable');
    }

    /**
     * The dark runs of one row, left to right.
     *
     * @param  list<bool>     $row
     * @return list<ADarkRun>
     */
    private function runsOf(array $row, int $down, int $margin): array
    {
        $runs = [];
        $from = null;

        foreach ([...$row, false] as $x => $dark) {
            if ($dark && $from === null) {
                $from = $x;
            }

            if (! $dark && $from !== null) {
                $width = ($x - $from) * $this->square;
                $runs[] = new ADarkRun(
                    ($margin + $from) * $this->square - ($this->side - $width) / 2,
                    $down * $this->square - ($this->side - $this->square) / 2,
                    $width,
                );
                $from = null;
            }
        }

        return $runs;
    }
}
