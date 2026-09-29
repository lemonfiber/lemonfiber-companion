<?php

declare(strict_types=1);

use Modules\Design\View\Prominence;

it('draws a primary action in the platform\'s primary variant and a tonal one in its second', function (): void {
    expect(Prominence::Primary->variant())->toBe('primary')
        ->and(Prominence::Tonal->variant())->toBe('secondary');
});
