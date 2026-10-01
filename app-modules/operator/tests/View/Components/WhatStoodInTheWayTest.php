<?php

declare(strict_types=1);

use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Tests\Support\WhatMarkupDraws;
use Tests\TestCase;

// The application is booted here, unlike most component tests: a render needs
// the view factory, the component namespace and the precompiler.
uses(TestCase::class);

// What stood in the way is drawn as lines in the column it stands in, with no
// container of its own: a screen draws it inside content it has already
// opened, and a scroll view there would be a scroll view inside a scroll view.

it('draws what was met, what to do about it and the action in the column it stands in', function (): void {
    $met = Obstacle::of(KindOfObstacle::StackDidNotAnswer);

    expect(WhatMarkupDraws::outline(
        '<native:column><x-operator::what-stood-in-the-way :went="$went" sign-in-goes-to="/sign-in" /></native:column>',
        ['went' => HowTheReadingWent::somethingStopped($met)],
    ))->toBe(sprintf(
        'column[][%s, %s, button{"width":"fill"}[]]',
        WhatMarkupDraws::words($met->said()),
        WhatMarkupDraws::words($met->remedy()),
    ));
});

it('draws an ended session and the way back in the column it stands in', function (): void {
    expect(WhatMarkupDraws::outline(
        '<native:column><x-operator::what-stood-in-the-way :went="$went" sign-in-goes-to="/sign-in" /></native:column>',
        ['went' => HowTheReadingWent::theSessionEnded()],
    ))->toBe(sprintf(
        'column[][%s, button{"width":"fill"}[]]',
        WhatMarkupDraws::words('connection.session_has_ended'),
    ));
});

it('opens no scroll view inside the content it is drawn in', function (): void {
    $met = Obstacle::of(KindOfObstacle::StackDidNotAnswer);

    expect(WhatMarkupDraws::outline(
        '<x-operator::content><native:text>Before</native:text>'
        . '<x-operator::what-stood-in-the-way :went="$went" sign-in-goes-to="/sign-in" />'
        . '</x-operator::content>',
        ['went' => HowTheReadingWent::somethingStopped($met)],
    ))->toBe(sprintf(
        'scroll_view{"overflow":2,"width":"fill","height":"fill"}[column{"width":"fill","padding":[16,24,16,24],"gap":16}'
        . '[Before, %s, %s, button{"width":"fill"}[]]]',
        WhatMarkupDraws::words($met->said()),
        WhatMarkupDraws::words($met->remedy()),
    ));
});
