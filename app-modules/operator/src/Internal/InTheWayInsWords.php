<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use function sprintf;

/**
 * Where the member's way in has its lines in the catalogue, under the stem each one names.
 */
final readonly class InTheWayInsWords
{
    private const string SAID = 'household.joining.%s';

    private const string EXPLAINED = 'household.joining.%s_explained';

    private const string REMEDY = 'household.joining.%s_action';

    /** The key for what a stem says. */
    public static function said(string $stem): string
    {
        return sprintf(self::SAID, $stem);
    }

    /** The key for how to go about it. */
    public static function explained(string $stem): string
    {
        return sprintf(self::EXPLAINED, $stem);
    }

    /** The key for what to do about it. */
    public static function remedy(string $stem): string
    {
        return sprintf(self::REMEDY, $stem);
    }
}
