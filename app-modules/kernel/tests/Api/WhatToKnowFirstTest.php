<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\SomethingItCannotTake;
use Modules\Kernel\Api\SomethingNotLemonfibers;
use Modules\Kernel\Api\SomethingStillComing;
use Modules\Kernel\Api\WhatIsNotLemonfibers;
use Modules\Kernel\Api\WhatIsStillComing;
use Modules\Kernel\Api\WhatItCannotTake;
use Modules\Kernel\Api\WhatToKnowFirst;

it('carries each list and each sentence as the stack said it', function (): void {
    $foreign = WhatIsNotLemonfibers::of(SomethingNotLemonfibers::at('photos', 12, 4096));
    $coming = WhatIsStillComing::of(SomethingStillComing::named('A film', 40));
    $outside = WhatItCannotTake::of(SomethingItCannotTake::found('Docker', 'lemonfiber did not install it', 'Uninstall Docker Desktop'));
    $first = WhatToKnowFirst::said($foreign, $coming, $outside, volume: 'On a network share', copyFirst: 'A copy is taken first');

    expect($first->foreign())->toBe($foreign)
        ->and($first->coming())->toBe($coming)
        ->and($first->outside())->toBe($outside)
        ->and($first->volume())->toBe('On a network share')
        ->and($first->copyFirst())->toBe('A copy is taken first');
});

it('says nothing of a volume or a copy the stack said nothing about', function (): void {
    $first = WhatToKnowFirst::said(WhatIsNotLemonfibers::of(), WhatIsStillComing::of(), WhatItCannotTake::of());

    expect($first->volume())->toBe('')
        ->and($first->copyFirst())->toBe('');
});
