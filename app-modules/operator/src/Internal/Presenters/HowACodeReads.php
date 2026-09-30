<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use function mb_str_split;

use Modules\Kernel\Api\AScannableCode;

/**
 * A code another device scans, as the squares `x-design::scannable` draws.
 *
 * One place, because every screen that hands something over as a code draws it
 * the same way, and the letter a dark square is written with is the code's to
 * say rather than each screen's.
 */
final readonly class HowACodeReads
{
    /** How a dark square is written in an {@see AScannableCode}'s rows. */
    private const string A_DARK_SQUARE = '1';

    /**
     * The code as rows of squares, dark where true, and none where there is no code.
     *
     * @return list<list<bool>>
     */
    public function squares(AScannableCode $code): array
    {
        $rows = [];

        foreach ($code as $row) {
            $squares = [];

            foreach (mb_str_split($row) as $square) {
                $squares[] = $square === self::A_DARK_SQUARE;
            }

            $rows[] = $squares;
        }

        return $rows;
    }
}
