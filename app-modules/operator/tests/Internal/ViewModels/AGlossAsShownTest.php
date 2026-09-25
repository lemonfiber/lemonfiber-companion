<?php

declare(strict_types=1);

namespace Modules\Operator\Tests\Internal\ViewModels;

use function expect;
use function it;

use Modules\Operator\Internal\ViewModels\AGlossAsShown;

it('is explained where the glossary gave the word a short gloss', function (): void {
    expect(new AGlossAsShown(word: 'grab', short: 'Taking a release', goes: '')->isExplained())->toBeTrue();
});

it('is not explained where the glossary carries no such word, so nothing is drawn under it', function (): void {
    expect(new AGlossAsShown(word: 'grabber', short: '', goes: '')->isExplained())->toBeFalse();
});
