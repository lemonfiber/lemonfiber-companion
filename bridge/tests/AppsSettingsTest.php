<?php

declare(strict_types=1);

use Lemonfiber\Native\AppsSettings;
use Native\Mobile\Testing\FakeBridge;

// The settings page's PHP face, driven through the real bridge call.
//
// The bridge name is written out as a literal, for `LinkTest`'s reason.

beforeEach(function (): void {
    FakeBridge::disable();
});

it('reads an opened answer as the page having been asked for', function (): void {
    $bridge = FakeBridge::enable()->respondTo('Lemonfiber.Settings.Open', ['outcome' => 'opened']);

    expect(new AppsSettings()->open())->toBeTrue();

    $bridge->assertCalled('Lemonfiber.Settings.Open');
});

it('reads a refusal, a word it does not know, or no answer as the page not having opened', function (array $answered): void {
    FakeBridge::enable()->respondTo('Lemonfiber.Settings.Open', $answered);

    expect(new AppsSettings()->open())->toBeFalse();
})->with([
    'refused' => [['outcome' => 'refused']],
    'a word it does not know' => [['outcome' => 'perhaps']],
    'no outcome' => [[]],
]);

it('reads no bridge at all as the page not having opened', function (): void {
    expect(new AppsSettings()->open())->toBeFalse();
});
