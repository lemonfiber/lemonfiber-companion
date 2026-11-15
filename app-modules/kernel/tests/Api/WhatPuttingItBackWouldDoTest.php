<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\ACopy;
use Modules\Kernel\Api\AMomentAsWritten;
use Modules\Kernel\Api\ARelocation;
use Modules\Kernel\Api\KeepingSaysNothing;
use Modules\Kernel\Api\ScopeOfACopy;
use Modules\Kernel\Api\WhatACopyHolds;
use Modules\Kernel\Api\WhatPuttingItBackWouldDo;
use Modules\Kernel\Api\WhatWroteACopy;
use Modules\Kernel\Api\WhereTheDataGoes;
use Tests\Support\WhatAScopeSays;

/** A listing of one copy, with the three words it requires given as a case says. */
function aListingSaying(string $agreement, string $takenBy, AMomentAsWritten $takenAt, bool $older = false): WhatPuttingItBackWouldDo
{
    return WhatPuttingItBackWouldDo::listed(
        ACopy::named('lemonfiber-20260924-0300-full'),
        $agreement,
        ScopeOfACopy::theWholeStack(),
        WhatWroteACopy::of($takenBy, $takenAt),
        WhatACopyHolds::these('lemonfiber configuration'),
        older: $older,
        data: WhereTheDataGoes::elsewhere(ARelocation::from('/srv/old', '/srv/new')),
    );
}

it('carries every part of the listing as the stack gave it', function (): void {
    $listing = aListingSaying('restore-0f3a', '0.9.0', AMomentAsWritten::of('2026-09-24T03:00:00Z'), older: true);

    expect($listing->copy()->name())->toBe('lemonfiber-20260924-0300-full')
        ->and($listing->agreement())->toBe('restore-0f3a')
        ->and(WhatAScopeSays::of($listing->scope()))->toBe('whole')
        ->and($listing->takenBy())->toBe('0.9.0')
        ->and($listing->takenAt())->toEqual(AMomentAsWritten::of('2026-09-24T03:00:00Z'))
        ->and(iterator_to_array($listing->contents(), preserve_keys: false))->toBe(['lemonfiber configuration'])
        ->and($listing->isOlder())->toBeTrue()
        ->and($listing->whereTheDataGoes()->isElsewhere())->toBeTrue();
});

it('says a copy of this version is not older', function (): void {
    expect(aListingSaying('restore-0f3a', '0.9.0', AMomentAsWritten::of('2026-09-24T03:00:00Z'))->isOlder())->toBeFalse();
});

it('refuses a listing missing a word it needs, naming which', function (string $agreement, string $takenBy, string $named): void {
    expect(fn(): WhatPuttingItBackWouldDo => aListingSaying($agreement, $takenBy, AMomentAsWritten::of('2026-09-24T03:00:00Z')))
        ->toThrow(KeepingSaysNothing::class, $named);
})->with([
    'the agreement' => [' ', '0.9.0', '`agreement`'],
    'the version' => ['restore-0f3a', ' ', '`product_version`'],
]);
