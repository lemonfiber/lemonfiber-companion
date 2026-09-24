<?php

declare(strict_types=1);

use Tests\Support\WhatMarkupDraws;
use Tests\TestCase;

// The application is booted here, unlike most component tests: a render needs
// the view factory, the component namespace and the precompiler.
uses(TestCase::class);

// What a screen puts in the component's slot is drawn inside the padded column.
//
// Asserted on a rendered tree rather than on the templates, because the
// question is where the renderer puts the elements, and the text of a template
// cannot answer it.

it('draws its slot inside one padded column', function (): void {
    expect(WhatMarkupDraws::outline(
        '<x-operator::content><native:text>Inside</native:text></x-operator::content>',
    ))->toBe('column{"width":"fill","padding":[16,24,16,24],"gap":16}[Inside]');
});

it('draws what follows it beside the column rather than in it', function (): void {
    expect(WhatMarkupDraws::outline(
        '<x-operator::content><native:text>Inside</native:text></x-operator::content>'
        . '<native:text>After</native:text>',
    ))->toBe(
        'column{"width":"fill","height":"fill"}'
        . '[column{"width":"fill","padding":[16,24,16,24],"gap":16}[Inside], After]',
    );
});

it('draws a component in its slot inside the column too', function (): void {
    expect(WhatMarkupDraws::outline(
        '<x-operator::content><x-operator::entry><native:text>Inside</native:text></x-operator::entry></x-operator::content>'
        . '<native:text>After</native:text>',
    ))->toBe(
        'column{"width":"fill","height":"fill"}'
        . '[column{"width":"fill","padding":[16,24,16,24],"gap":16}[column{"width":"fill","gap":4}[Inside]], After]',
    );
});
