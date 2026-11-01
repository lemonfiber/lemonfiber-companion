<?php

declare(strict_types=1);

namespace Modules\Dx\Api;

use function array_diff;
use function array_key_exists;
use function array_unique;
use function array_values;
use function implode;
use function preg_match;
use function preg_match_all;
use function sprintf;

/**
 * A brand file as the cells its generator painted: a grid of square cells, each one colour or none.
 *
 * The brand draws every mark as `<rect>` runs on a viewBox one unit a cell, so
 * what it holds is read back as cells and refused where it holds anything else:
 * a shape that is not a whole-cell run, or a cell painted twice.
 */
final readonly class ACellDrawing
{
    /** The viewBox of a cell drawing: whole cells from the origin. */
    private const string A_VIEW_BOX = '/<svg\b[^>]*\bviewBox="0 0 (\d+) (\d+)"/';

    /** Every element the drawing opens. */
    private const string AN_ELEMENT = '/<([a-zA-Z][\w:-]*)\b[^>]*>/';

    /** Every rect, whatever it holds. */
    private const string A_RECT = '/<rect\b/';

    /** A run of cells in one colour. */
    private const string A_RUN = '/<rect x="(\d+)" y="(\d+)" width="(\d+)" height="(\d+)" fill="(#[0-9A-F]{6})"\/>/';

    /** The elements a cell drawing is made of. */
    private const array ITS_ELEMENTS = ['svg', 'title', 'rect'];

    /**
     * @param int $wide cells across
     * @param int $high cells down
     * @param array<string, list<array{int, int, int, int}>> $runs each colour's runs, as x, y, width and height in cells, in the order the file paints them
     */
    private function __construct(
        public int $wide,
        public int $high,
        public array $runs,
    ) {}

    /** The cells a brand SVG paints, or a refusal naming what is not a cell. */
    public static function fromSvg(string $svg): self
    {
        if (preg_match(self::A_VIEW_BOX, $svg, $box) !== 1) {
            throw NotACellDrawing::withNoGrid();
        }

        preg_match_all(self::AN_ELEMENT, $svg, $elements);
        $strangers = array_values(array_unique(array_diff($elements[1], self::ITS_ELEMENTS)));

        if ($strangers !== []) {
            throw NotACellDrawing::holding(implode(', ', $strangers));
        }

        $wide = (int) $box[1];
        $high = (int) $box[2];

        if (preg_match_all(self::A_RUN, $svg, $found, PREG_SET_ORDER) !== preg_match_all(self::A_RECT, $svg)) {
            throw NotACellDrawing::withAStrayRect();
        }

        $painted = [];
        $runs = [];

        foreach ($found as [, $x, $y, $width, $height, $fill]) {
            $run = [(int) $x, (int) $y, (int) $width, (int) $height];
            $painted = self::painting($painted, $run, $wide, $high);
            $runs[$fill][] = $run;
        }

        return new self($wide, $high, $runs);
    }

    /**
     * The cells painted once a run is painted too, refusing one outside the grid or painted already.
     *
     * @param array<string, true> $painted
     * @param array{int, int, int, int} $run
     * @return array<string, true>
     */
    private static function painting(array $painted, array $run, int $wide, int $high): array
    {
        [$x, $y, $width, $height] = $run;

        if ($width < 1 || $height < 1 || $x + $width > $wide || $y + $height > $high) {
            throw NotACellDrawing::leavingTheGrid($x, $y, $wide, $high);
        }

        for ($row = $y; $row < $y + $height; $row++) {
            for ($column = $x; $column < $x + $width; $column++) {
                $cell = sprintf('%d,%d', $column, $row);

                if (array_key_exists($cell, $painted)) {
                    throw NotACellDrawing::paintingTwice($cell);
                }

                $painted[$cell] = true;
            }
        }

        return $painted;
    }
}
