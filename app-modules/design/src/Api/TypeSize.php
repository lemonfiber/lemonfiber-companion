<?php

declare(strict_types=1);

namespace Modules\Design\Api;

/**
 * Every size this surface sets text at, named as the brand's tokens name it.
 *
 * The points are the brand's sizes at a sixteen-point root, copied from its
 * token file because a module may not read a file;
 * `tests/Arch/BrandMeasuresParityTest.php` checks them against it, and checks
 * that every size a template sets is one of these. Each is the size at the
 * platform's default text size: iOS scales it with Dynamic Type and Android
 * with its font scale.
 */
enum TypeSize: string
{
    /** A section's label over what it holds. */
    case Eyebrow = 'eyebrow';

    /** A quieter line, and what a machine wrote. */
    case Caption = 'caption';

    /** Running text, a heading and a line that is tapped. */
    case Body = 'body';

    /** A screen's lead line. */
    case DisplayM = 'displayM';

    /** A title lettered across the width of the screen, where it stands in for its artwork. */
    case DisplayL = 'displayL';

    /**
     * The brand's sizes in points, its token file's `size` block at a sixteen-point root.
     *
     * Eyebrow 0.75rem, caption 0.8125rem, body 0.9375rem, displayM 1.6875rem and displayL 2.375rem.
     */
    private const array POINTS = ['eyebrow' => 12, 'caption' => 13, 'body' => 15, 'displayM' => 27, 'displayL' => 38];

    /** The size in points at the platform's default text size. */
    public function points(): int
    {
        return self::POINTS[$this->value];
    }
}
