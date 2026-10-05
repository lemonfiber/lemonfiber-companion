<?php

declare(strict_types=1);

namespace Modules\Design\Api;

/**
 * Every corner radius this surface draws, named as the brand's tokens name it.
 *
 * `60-brand/surface-mapping.md` allows two radii, `sm` and `md`, and the
 * `pill` on a chip or a button alone. The points are copied from the brand's
 * token file because a module may not read a file;
 * `tests/Arch/BrandMeasuresParityTest.php` checks them against it, and checks
 * that every radius a template draws is `sm` or `md`.
 */
enum Radius: string
{
    /** The smaller corner. */
    case Small = 'sm';

    /** The corner of a card, a notice, a field and a sheet. */
    case Medium = 'md';

    /** A shape rounded end to end, for a chip or a button. */
    case Pill = 'pill';

    /**
     * The brand's radii in points, as its token file's `radius` block holds them.
     *
     * The pill is past half of anything it rounds, so it rounds a shape end to end.
     */
    private const array POINTS = ['sm' => 3, 'md' => 4, 'pill' => 999];

    /** The radius in points, which each platform scales as it scales its own. */
    public function points(): int
    {
        return self::POINTS[$this->value];
    }
}
