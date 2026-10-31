<?php

declare(strict_types=1);

namespace Tests\Support;

use function implode;

use Modules\Kernel\Api\ASettingChange;
use Modules\Kernel\Api\ASettingMove;
use Modules\Kernel\Api\ASettingValue;
use Modules\Kernel\Api\TheSettingMoves;

use function sprintf;

/** A change to settings, as the stack reports it moved and as a test reads back what was asked. */
final readonly class ASettingChangeAsReported
{
    /** Each setting the change moves, from what it held to what the change asks, each at this cost. */
    public static function moves(ASettingChange $change, ASettingValue $was, string $consequence = ''): TheSettingMoves
    {
        $moves = [];

        foreach ($change as $setting => $value) {
            $moves[] = ASettingMove::said($setting, $was, $value, $consequence);
        }

        return TheSettingMoves::these(...$moves);
    }

    /** The act and each setting it moves: `settings-set a=1 b=2`, or `settings-clear a b`. */
    public static function said(ASettingChange $change): string
    {
        $said = [$change->how()->asked()];

        foreach ($change as $setting => $value) {
            $said[] = $value->isHeld() ? sprintf('%s=%s', $setting, $value->said()) : $setting;
        }

        return implode(' ', $said);
    }
}
