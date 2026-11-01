<?php

declare(strict_types=1);

namespace Modules\Dx\Tests\Api;

use function expect;
use function it;

use Modules\Dx\Api\ACellDrawing;
use Modules\Dx\Api\WhatAndroidDrawsOfTheBrand;

use function sprintf;

/** A square drawing of the given cells, one painted in its corner. */
function aBrandFileOf(int $cells): ACellDrawing
{
    return ACellDrawing::fromSvg(sprintf('<svg viewBox="0 0 %1$d %1$d"><rect x="0" y="0" width="1" height="1" fill="#17160F"/></svg>', $cells));
}

it('draws each drawable from its brand file, on its side, at its cell size', function (WhatAndroidDrawsOfTheBrand $drawable, int $cells, string $source, float $sideDp, float $dpPerCell, string $corner): void {
    $drawn = $drawable->drawn(aBrandFileOf($cells));

    expect($drawable->source())->toBe($source)
        ->and($drawable->sideDp())->toBe($sideDp)
        ->and($drawable->dpPerCell(aBrandFileOf($cells)))->toBe($dpPerCell)
        ->and($drawn)->toContain(sprintf('Drawn from %s ', $source))
        ->and($drawn)->toContain(sprintf('android:pathData="%s"', $corner));
})->with([
    'the notification icon, the 16-cell tray across 24dp' => [WhatAndroidDrawsOfTheBrand::TheNotificationIcon, 16, 'brand/tray-knockout.svg', 24.0, 1.5, sprintf('M%d,0h1v1h-1z', 0)],
    'the launcher foreground, the 32-cell lemon at 2dp in the middle of 108dp' => [WhatAndroidDrawsOfTheBrand::TheLauncherForeground, 32, 'brand/mark-primary.svg', 108.0, 2.0, sprintf('M%d,11h1v1h-1z', 11)],
    'the launch mark, the 32-cell lemon across 96dp' => [WhatAndroidDrawsOfTheBrand::TheLaunchMark, 32, 'brand/mark-primary.svg', 96.0, 3.0, sprintf('M%d,0h1v1h-1z', 0)],
]);
