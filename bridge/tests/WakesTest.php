<?php

declare(strict_types=1);

use Lemonfiber\Native\Wakes;
use Native\Mobile\Testing\FakeBridge;

// Being woken while away, driven through the real bridge call.
//
// The bridge names are written out as literals, for `LinkTest`'s reason.

beforeEach(function (): void {
    FakeBridge::disable();
});

it('asks the platform, and reads a will-wake answer as taken', function (): void {
    $bridge = FakeBridge::enable()->respondTo('Lemonfiber.Wakes.Ask', ['outcome' => 'will_wake']);

    expect(new Wakes()->ask())->toBeTrue();

    $bridge->assertCalled('Lemonfiber.Wakes.Ask');
});

it('reads a refusal, a word it does not know, or no answer as not taken', function (array $answered): void {
    FakeBridge::enable()->respondTo('Lemonfiber.Wakes.Ask', $answered);

    expect(new Wakes()->ask())->toBeFalse();
})->with([
    'refused' => [['outcome' => 'refused', 'because' => 'unavailable']],
    'a word it does not know' => [['outcome' => 'perhaps']],
    'no outcome' => [[]],
]);

it('reads no bridge at all as not taken', function (): void {
    expect(new Wakes()->ask())->toBeFalse();
});

it('stops asking, and reads a will-not-wake answer as let go of', function (): void {
    $bridge = FakeBridge::enable()->respondTo('Lemonfiber.Wakes.Stop', ['outcome' => 'will_not_wake']);

    expect(new Wakes()->stop())->toBeTrue();

    $bridge->assertCalled('Lemonfiber.Wakes.Stop');
});

it('reads no answer to stopping as not let go of', function (): void {
    expect(new Wakes()->stop())->toBeFalse();
});

/**
 * The iOS identifiers the plugin's manifest permits to wake the app.
 *
 * Named for this package: a test file's helpers land in the global namespace beside every other suite's.
 *
 * @return list<string>
 */
function theWakesThePluginPermits(): array
{
    /** @var array{ios: array{info_plist: array{BGTaskSchedulerPermittedIdentifiers: list<string>}}} $manifest */
    $manifest = json_decode((string) file_get_contents(sprintf('%s/nativephp.json', dirname(__DIR__))), associative: true, flags: JSON_THROW_ON_ERROR);

    return $manifest['ios']['info_plist']['BGTaskSchedulerPermittedIdentifiers'];
}

it('runs the command each native half names, under the name iOS permits', function (): void {
    $kotlin = (string) file_get_contents(sprintf('%s/resources/android/WakeRule.kt', dirname(__DIR__)));
    $swift = (string) file_get_contents(sprintf('%s/resources/ios/WakeRule.swift', dirname(__DIR__)));
    $permitted = theWakesThePluginPermits();

    expect($kotlin)->toContain(sprintf('COMMAND: String = "%s"', Wakes::COMMAND))
        ->and($swift)->toContain(sprintf('command = "%s"', Wakes::COMMAND))
        ->and($permitted)->toHaveCount(1)
        ->and($swift)->toContain(sprintf('name = "%s"', $permitted[0]))
        ->and($kotlin)->toContain(sprintf('NAME: String = "%s"', $permitted[0]));
});
