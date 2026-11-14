<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function expect;
use function it;

use Lemonfiber\Sdk\Envelope\Envelope;
use Modules\Kernel\Api\WhatCanBeAskedFor;
use Modules\Sdk\Api\SearchIsUnreadable;
use Modules\Sdk\Api\WhatCanBeAskedForCameTo;

it('names a unit that is not a word as an empty one, beside the words it reads', function (mixed $unit, string $said): void {
    // `askable-kinds` is stood in for and not judged: each unit is one no stack sends, which is the refusal under test.
    expect(static fn(): WhatCanBeAskedFor => WhatCanBeAskedForCameTo::in(new Envelope(1, 'askable-kinds', ['member' => 'Robin', 'kinds' => [['kind' => 'music', 'units' => [$unit]]]])))
        ->toThrow(SearchIsUnreadable::class, $said);
})->with([
    'a number' => [7, 'says `units` is ``, and this app reads'],
    'a word it does not know' => ['single', 'says `units` is `single`, and this app reads'],
]);
