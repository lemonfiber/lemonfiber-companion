<?php

declare(strict_types=1);

namespace Modules\Dx\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * A brand file that is not the whole-cell drawing the brand's generator writes, refused with what is wrong with it.
 */
final class NotACellDrawing extends InvalidArgumentException
{
    public static function withNoGrid(): self
    {
        return new self('The drawing has no viewBox of whole cells from the origin.');
    }

    /** @param string $elements the elements that are not cells, named */
    public static function holding(string $elements): self
    {
        return new self(sprintf('The drawing holds %s, which is not a cell.', $elements));
    }

    public static function withAStrayRect(): self
    {
        return new self('The drawing holds a rect that is not a run of whole cells in one colour.');
    }

    public static function leavingTheGrid(int $x, int $y, int $wide, int $high): self
    {
        return new self(sprintf('The run at %d,%d leaves the %dx%d grid.', $x, $y, $wide, $high));
    }

    public static function paintingTwice(string $cell): self
    {
        return new self(sprintf('The cell at %s is painted twice.', $cell));
    }

    public static function offCentre(int $wide, int $high, float $sideDp, float $dpPerCell): self
    {
        return new self(sprintf('A %dx%d drawing cannot be centred in whole cells of %sdp on a side of %sdp.', $wide, $high, $dpPerCell, $sideDp));
    }

    public static function withNoColourAt(string $path): self
    {
        return new self(sprintf('The tokens carry no colour at %s.', $path));
    }
}
