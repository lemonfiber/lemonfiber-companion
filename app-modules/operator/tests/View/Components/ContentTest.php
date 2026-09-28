<?php

declare(strict_types=1);

use Tests\Support\WhatMarkupDraws;
use Tests\TestCase;

// The application is booted here, unlike most component tests: a render needs
// the view factory, the component namespace and the precompiler.
uses(TestCase::class);

// What a screen puts in the component's slot is drawn inside the padded column,
// and the column inside a scroll view, so a screen longer than the handset
// scrolls.
//
// Asserted on a rendered tree rather than on the templates, because the
// question is where the renderer puts the elements, and the text of a template
// cannot answer it.

it('draws its slot inside one padded column that scrolls', function (): void {
    expect(WhatMarkupDraws::outline(
        '<x-operator::content><native:text>Inside</native:text></x-operator::content>',
    ))->toBe('scroll_view{"overflow":2,"width":"fill"}[column{"width":"fill","padding":[16,24,16,24],"gap":16}[Inside]]');
});

it('draws what follows it beside the column rather than in it', function (): void {
    expect(WhatMarkupDraws::outline(
        '<x-operator::content><native:text>Inside</native:text></x-operator::content>'
        . '<native:text>After</native:text>',
    ))->toBe(
        'column{"width":"fill","height":"fill"}'
        . '[scroll_view{"overflow":2,"width":"fill"}[column{"width":"fill","padding":[16,24,16,24],"gap":16}[Inside]], After]',
    );
});

it('draws a component in its slot inside the column too', function (): void {
    expect(WhatMarkupDraws::outline(
        '<x-operator::content><x-operator::entry><native:text>Inside</native:text></x-operator::entry></x-operator::content>'
        . '<native:text>After</native:text>',
    ))->toBe(
        'column{"width":"fill","height":"fill"}'
        . '[scroll_view{"overflow":2,"width":"fill"}[column{"width":"fill","padding":[16,24,16,24],"gap":16}[column{"width":"fill","gap":4}[Inside]]], After]',
    );
});

it('opens at its top', function (): void {
    expect(data_get(WhatMarkupDraws::drawn(
        '<x-operator::content><native:text>Inside</native:text></x-operator::content>',
    ), 'props.scroll_anchor'))->toBeNull();
});

it('opens at its end when asked to, around the same column', function (): void {
    $markup = '<x-operator::content from-the-end><native:text>Inside</native:text></x-operator::content>';

    expect(data_get(WhatMarkupDraws::drawn($markup), 'props.scroll_anchor'))->toBe('bottom')
        ->and(WhatMarkupDraws::outline($markup))
        ->toBe('scroll_view{"overflow":2,"width":"fill"}[column{"width":"fill","padding":[16,24,16,24],"gap":16}[Inside]]');
});
