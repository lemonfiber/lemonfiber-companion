<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\AWordInUse;
use Modules\Kernel\Api\WordsSayNothing;

it('keeps the word as it was drawn', function (): void {
    expect(AWordInUse::named('Ratio')->said())->toBe('Ratio');
});

it('is a name whatever its case, and not a longer or shorter one', function (): void {
    $word = AWordInUse::named('Ratio');

    expect($word->is(AWordInUse::named('ratio')))->toBeTrue()
        ->and($word->is(AWordInUse::named('RATIO')))->toBeTrue()
        ->and($word->is(AWordInUse::named('ratios')))->toBeFalse()
        ->and($word->is(AWordInUse::named('rat')))->toBeFalse();
});

it('refuses a blank word', function (): void {
    expect(fn(): AWordInUse => AWordInUse::named(' '))->toThrow(WordsSayNothing::class, '`word`');
});
