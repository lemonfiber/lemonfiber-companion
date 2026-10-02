<?php

declare(strict_types=1);

namespace Modules\News\Tests\Internal;

use function expect;
use function it;

use Modules\News\Api\AnItem;
use Modules\News\Api\KindOfNews;
use Modules\News\Internal\WhatIsKeptOfNews;

it('marks every kind and has seen nothing where nothing is kept', function (): void {
    $kept = WhatIsKeptOfNews::nothing();

    foreach (KindOfNews::cases() as $kind) {
        expect($kept->isMarked($kind))->toBeTrue($kind->value)
            ->and($kept->hasSeen($kind))->toBeFalse($kind->value);
    }
});

it('holds the newest of each kind seen apart, the later in place of the earlier', function (): void {
    $kept = WhatIsKeptOfNews::nothing()->seeing(AnItem::aRequest(3))->seeing(AnItem::aRequest(5))->seeing(AnItem::anUpdate('0.17.0'));

    expect($kept->seen(KindOfNews::Request)->named())->toBe('5')
        ->and($kept->seen(KindOfNews::Update)->named())->toBe('0.17.0')
        ->and($kept->hasSeen(KindOfNews::Problem))->toBeFalse();
});

it('forgets the newest of a kind it stops marking, and marks it again without bringing it back', function (): void {
    $kept = WhatIsKeptOfNews::nothing()->seeing(AnItem::aRequest(3))->seeing(AnItem::anUpdate('0.17.0'))->notMarking(KindOfNews::Request)->notMarking(KindOfNews::Request);
    $again = $kept->marking(KindOfNews::Request);

    expect($kept->isMarked(KindOfNews::Request))->toBeFalse()
        ->and($kept->switchedOff())->toBe([KindOfNews::Request])
        ->and($kept->hasSeen(KindOfNews::Request))->toBeFalse()
        ->and($kept->hasSeen(KindOfNews::Update))->toBeTrue()
        ->and($again->isMarked(KindOfNews::Request))->toBeTrue()
        ->and($again->hasSeen(KindOfNews::Request))->toBeFalse();
});
