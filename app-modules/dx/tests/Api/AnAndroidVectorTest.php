<?php

declare(strict_types=1);

namespace Modules\Dx\Tests\Api;

use function expect;
use function it;

use Modules\Dx\Api\ACellDrawing;
use Modules\Dx\Api\AnAndroidVector;
use Modules\Dx\Api\NotACellDrawing;

use function sprintf;

/** A square drawing of one dark cell in its corner. */
function aCornerCell(int $side): ACellDrawing
{
    return ACellDrawing::fromSvg(sprintf('<svg viewBox="0 0 %1$d %1$d"><rect x="0" y="0" width="1" height="1" fill="#17160F"/></svg>', $side));
}

it('writes one path a colour, its runs centred on the side, on a viewport one unit a cell', function (): void {
    $drawing = ACellDrawing::fromSvg(
        '<svg viewBox="0 0 2 2">'
        . '<rect x="0" y="0" width="2" height="1" fill="#17160F"/>'
        . '<rect x="1" y="1" width="1" height="1" fill="#F0C419"/>'
        . '<rect x="0" y="1" width="1" height="1" fill="#17160F"/>'
        . '</svg>',
    );

    expect(new AnAndroidVector($drawing, 1.5, 6.0, 'brand/a-mark.svg')->xml())->toBe(sprintf(
        <<<'XML'
            <?xml version="1.0" encoding="utf-8"?>
            <!-- Drawn from brand/a-mark.svg by scripts/brand_drawables.php. Do not edit. -->
            <vector xmlns:android="http://schemas.android.com/apk/res/android"
                android:width="6dp"
                android:height="6dp"
                android:viewportWidth="4"
                android:viewportHeight="4">
                <path
                    android:fillColor="#17160F"
                    android:pathData="M%1$d,1h2v1h-2z M%1$d,2h1v1h-1z" />
                <path
                    android:fillColor="#F0C419"
                    android:pathData="M%2$d,2h1v1h-1z" />
            </vector>

            XML,
        1,
        2,
    ));
});

it('writes a length in dp to two places, with no trailing zeros', function (float $dpPerCell, string $written): void {
    expect(new AnAndroidVector(aCornerCell(1), $dpPerCell, $dpPerCell, 'brand/a-mark.svg')->xml())
        ->toContain(sprintf("android:width=\"%sdp\"\n", $written));
})->with([
    'whole' => [24.0, '24'],
    'a tenth' => [2.5, '2.5'],
    'a hundredth' => [1.25, '1.25'],
    'beyond two places' => [1.126, '1.13'],
    'ten' => [10.0, '10'],
]);

it('refuses a drawing it cannot centre in whole cells on the side', function (ACellDrawing $drawing, float $sideDp, string $why): void {
    expect(static fn(): AnAndroidVector => new AnAndroidVector($drawing, 2.0, $sideDp, 'brand/a-mark.svg'))
        ->toThrow(NotACellDrawing::class, $why);
})->with([
    'an odd margin' => [aCornerCell(2), 6.0, 'A 2x2 drawing cannot be centred in whole cells of 2dp on a side of 6dp.'],
    'part of a cell' => [aCornerCell(2), 5.0, 'A 2x2 drawing cannot be centred in whole cells of 2dp on a side of 5dp.'],
    'smaller than the drawing' => [aCornerCell(4), 4.0, 'A 4x4 drawing cannot be centred in whole cells of 2dp on a side of 4dp.'],
    'not square' => [ACellDrawing::fromSvg('<svg viewBox="0 0 2 1"></svg>'), 4.0, 'A 2x1 drawing cannot be centred'],
]);

it('takes a drawing that fills the side', function (): void {
    expect(new AnAndroidVector(aCornerCell(2), 2.0, 4.0, 'brand/a-mark.svg')->xml())
        ->toContain(sprintf('M%d,0h1v1h-1z', 0))
        ->toContain("android:viewportWidth=\"2\"\n");
});
