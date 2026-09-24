<?php

declare(strict_types=1);

use Tests\Support\WhatMarkupDraws;
use Tests\TestCase;

// The application is booted here, unlike most component tests: a render needs
// the view factory, the component namespace and the precompiler.
uses(TestCase::class);

// The lines an entry holds are drawn inside its column, and the entry after it
// is a column of its own.

it('draws the lines it holds inside its column', function (): void {
    expect(WhatMarkupDraws::outline(
        '<native:column>'
        . '<x-operator::entry><x-operator::emphasis>Name</x-operator::emphasis><native:text>Said</native:text></x-operator::entry>'
        . '<x-operator::entry><native:text>Next</native:text></x-operator::entry>'
        . '</native:column>',
    ))->toBe('column[][column{"width":"fill","gap":4}[Name, Said], column{"width":"fill","gap":4}[Next]]');
});
