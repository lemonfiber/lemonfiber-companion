<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_keys;
use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\ARequestOfOurs;
use Modules\Kernel\Api\OurRequests;
use Modules\Kernel\Api\WhatLemonfiberAsksFor;
use Modules\Kernel\Api\WhereItGoes;
use Modules\Kernel\Api\WhetherItIsAllowed;

/** One of lemonfiber's requests, named for what it asks for so an order can be read off. */
function aRequestFor(WhatLemonfiberAsksFor $asks): ARequestOfOurs
{
    return ARequestOfOurs::described($asks, WhereItGoes::to(), 'Why', 'What', WhetherItIsAllowed::Allowed, 'a.switch', 'What breaks');
}

it('keeps lemonfiber\'s requests in the stack\'s order', function (): void {
    $ours = OurRequests::of(aRequestFor(WhatLemonfiberAsksFor::Registry), aRequestFor(WhatLemonfiberAsksFor::Updates));
    $asked = [];

    foreach ($ours as $request) {
        $asked[] = $request->asksFor();
    }

    expect($asked)->toBe([WhatLemonfiberAsksFor::Registry, WhatLemonfiberAsksFor::Updates])
        ->and($ours)->toHaveCount(2);
});

it('is a list however it was handed its requests', function (): void {
    $ours = OurRequests::of(...['first' => aRequestFor(WhatLemonfiberAsksFor::Echo), 'second' => aRequestFor(WhatLemonfiberAsksFor::Guides)]);

    expect(array_keys(iterator_to_array($ours, preserve_keys: true)))->toBe([0, 1]);
});
