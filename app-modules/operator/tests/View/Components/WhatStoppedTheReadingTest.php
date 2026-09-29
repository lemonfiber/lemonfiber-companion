<?php

declare(strict_types=1);

use Modules\Kernel\Api\Obstacle;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Tests\Support\WhatMarkupDraws;
use Tests\TestCase;

// The application is booted here, unlike most component tests: a render needs
// the view factory, the component namespace and the precompiler.
uses(TestCase::class);

// Where a reading did not come back and the screen has nothing else to draw,
// what stood in the way is the screen's whole body: one padded column that
// scrolls, like every other screen's content.

it('draws what stood in the way inside one padded column that scrolls', function (): void {
    $met = Obstacle::StackDidNotAnswer;

    expect(WhatMarkupDraws::outline(
        '<x-operator::what-stopped-the-reading :went="$went" sign-in-goes-to="/sign-in" />',
        ['went' => HowTheReadingWent::somethingStopped($met)],
    ))->toBe(sprintf(
        'scroll_view{"overflow":2,"width":"fill"}[column{"width":"fill","padding":[16,24,16,24],"gap":16}'
        . '[%s, %s, button{"width":"fill"}[]]]',
        WhatMarkupDraws::words($met->said()),
        WhatMarkupDraws::words($met->remedy()),
    ));
});

it('draws an ended session inside one padded column that scrolls', function (): void {
    expect(WhatMarkupDraws::outline(
        '<x-operator::what-stopped-the-reading :went="$went" sign-in-goes-to="/sign-in" />',
        ['went' => HowTheReadingWent::theSessionEnded()],
    ))->toBe(sprintf(
        'scroll_view{"overflow":2,"width":"fill"}[column{"width":"fill","padding":[16,24,16,24],"gap":16}'
        . '[%s, button{"width":"fill"}[]]]',
        WhatMarkupDraws::words('connection.session_has_ended'),
    ));
});
