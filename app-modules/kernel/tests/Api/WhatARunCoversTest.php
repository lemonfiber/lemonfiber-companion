<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\Category;
use Modules\Kernel\Api\Check;
use Modules\Kernel\Api\Conclusion;
use Modules\Kernel\Api\Finding;
use Modules\Kernel\Api\WhatARunCovers;
use Modules\Kernel\Api\WhatTheCheckSaid;
use Modules\Kernel\Api\WhoPutItThere;
use Tests\Support\TheWordCarriedOut;

/** A passing finding in one family. */
function aFindingAbout(Category $family): Finding
{
    return Finding::of(Check::of('a.check'), $family, 'A check', Conclusion::Passed, WhatTheCheckSaid::nothingWrong(), WhoPutItThere::bundled());
}

it('covers every finding when it runs every check, and only a family\'s when it runs a family', function (): void {
    expect(WhatARunCovers::everyCheck()->covers(aFindingAbout(Category::Storage)))->toBeTrue()
        ->and(WhatARunCovers::theFamily(Category::Vpn)->covers(aFindingAbout(Category::Vpn)))->toBeTrue()
        ->and(WhatARunCovers::theFamily(Category::Vpn)->covers(aFindingAbout(Category::Storage)))->toBeFalse();
});

it('takes the arm it was made on, handing a family its family', function (): void {
    $said = static fn(WhatARunCovers $covers): string => $covers->either(
        every: static fn(): TheWordCarriedOut => new TheWordCarriedOut('every'),
        family: static fn(Category $family): TheWordCarriedOut => new TheWordCarriedOut($family->value),
    )->said;

    expect($said(WhatARunCovers::everyCheck()))->toBe('every')
        ->and($said(WhatARunCovers::theFamily(Category::Network)))->toBe('network');
});
