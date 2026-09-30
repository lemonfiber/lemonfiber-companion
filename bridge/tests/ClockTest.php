<?php

declare(strict_types=1);

use Lemonfiber\Native\Clock;
use Native\Mobile\Testing\FakeBridge;

// The clock capability's PHP face, driven through the real bridge call.
//
// The bridge name is written out as a literal for the reason `LinkTest` gives:
// a test spelling it through `Call` would agree with a wrong enum.

beforeEach(function (): void {
    FakeBridge::disable();
});

it('reads the name of the zone the phone is set to', function (): void {
    $bridge = FakeBridge::enable()->respondTo('Lemonfiber.Clock.Zone', ['outcome' => 'known', 'zone' => 'Europe/Amsterdam']);

    expect(new Clock()->zone())->toBe('Europe/Amsterdam');

    $bridge->assertCalled('Lemonfiber.Clock.Zone');
});

it('reads no answer at all as no name', function (): void {
    FakeBridge::enable();

    expect(new Clock()->zone())->toBe('');
});

it('reads an answer with no zone in it as no name', function (): void {
    FakeBridge::enable()->respondTo('Lemonfiber.Clock.Zone', ['outcome' => 'known']);

    expect(new Clock()->zone())->toBe('');
});

it('reads a zone that is not a word as no name', function (): void {
    FakeBridge::enable()->respondTo('Lemonfiber.Clock.Zone', ['outcome' => 'known', 'zone' => 42]);

    expect(new Clock()->zone())->toBe('');
});
