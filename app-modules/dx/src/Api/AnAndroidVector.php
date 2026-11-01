<?php

declare(strict_types=1);

namespace Modules\Dx\Api;

use function array_map;
use function fmod;
use function implode;
use function rtrim;
use function sprintf;

/**
 * A square cell drawing written as an Android vector drawable: one path a colour, a viewport one unit a cell, centred on a square side.
 *
 * Every cell edge lands on a whole viewport unit, so wherever a density makes
 * a cell a whole number of pixels the drawable paints whole pixels, and a
 * colour's runs are one path so no seam opens between them.
 */
final readonly class AnAndroidVector
{
    /** The cells across the drawable's side. */
    private int $side;

    /** The empty cells around the drawing on every side. */
    private int $margin;

    /**
     * @param ACellDrawing $drawing what is drawn
     * @param float $dpPerCell how wide a cell is, in dp
     * @param float $sideDp how wide the drawable is, in dp, a whole number of cells around the drawing on every side
     * @param string $from the brand file it is made from, named in the drawable
     */
    public function __construct(
        private ACellDrawing $drawing,
        private float $dpPerCell,
        float $sideDp,
        private string $from,
    ) {
        $side = $sideDp / $dpPerCell;
        $margin = ($side - $drawing->wide) / 2;

        if ($drawing->wide !== $drawing->high || $margin < 0 || fmod($margin, 1.0) !== 0.0) {
            throw NotACellDrawing::offCentre($drawing->wide, $drawing->high, $sideDp, $dpPerCell);
        }

        $this->side = (int) $side;
        $this->margin = (int) $margin;
    }

    /** The drawable's XML. */
    public function xml(): string
    {
        $paths = '';

        foreach ($this->drawing->runs as $fill => $runs) {
            $paths .= sprintf(
                "    <path\n        android:fillColor=\"%s\"\n        android:pathData=\"%s\" />\n",
                $fill,
                implode(' ', array_map($this->traced(...), $runs)),
            );
        }

        return sprintf(
            <<<'XML'
                <?xml version="1.0" encoding="utf-8"?>
                <!-- Drawn from %s by scripts/brand_drawables.php. Do not edit. -->
                <vector xmlns:android="http://schemas.android.com/apk/res/android"
                    android:width="%sdp"
                    android:height="%sdp"
                    android:viewportWidth="%d"
                    android:viewportHeight="%d">
                %s</vector>

                XML,
            $this->from,
            $this->dp($this->side * $this->dpPerCell),
            $this->dp($this->side * $this->dpPerCell),
            $this->side,
            $this->side,
            $paths,
        );
    }

    /**
     * One run as a closed path, moved in by the margin.
     *
     * @param array{int, int, int, int} $run
     */
    private function traced(array $run): string
    {
        [$x, $y, $width, $height] = $run;

        return sprintf('M%d,%dh%dv%dh-%dz', $x + $this->margin, $y + $this->margin, $width, $height, $width);
    }

    /** A length in dp as Android reads it, with no trailing zeros. */
    private function dp(float $length): string
    {
        return rtrim(rtrim(sprintf('%.2F', $length), '0'), '.');
    }
}
