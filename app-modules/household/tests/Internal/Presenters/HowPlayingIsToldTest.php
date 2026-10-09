<?php

declare(strict_types=1);

use Modules\Household\Internal\Presenters\HowPlayingIsTold;
use Modules\Kernel\Api\PlaybackIs;
use Modules\Kernel\Api\WhyPlayingDidNotStart;
use Tests\TestCase;

// The catalogue is read, so the application is booted.
uses(TestCase::class);

it('tells each stop that cannot go on in its own words, in every language', function (string $locale): void {
    $said = [];

    foreach ([PlaybackIs::StoppedOutOfReach, PlaybackIs::StoppedByThePin, PlaybackIs::StoppedOnTheFormat, PlaybackIs::StoppedAtTheDoor] as $is) {
        $key = HowPlayingIsTold::stopped($is);
        $line = __($key, locale: $locale);

        expect($line)->not->toBe($key, $is->name);
        $said[] = is_string($line) ? $line : '';
    }

    expect(array_unique($said))->toHaveCount(4);
})->with(['en', 'nl']);

it('tells nothing where playback did not stop', function (PlaybackIs $is): void {
    expect(HowPlayingIsTold::stopped($is))->toBe('');
})->with([PlaybackIs::Opening, PlaybackIs::Playing, PlaybackIs::Paused, PlaybackIs::Stalled, PlaybackIs::Ended, PlaybackIs::Closed]);

it('tells a device with no player from one that would not play what the house said, in every language', function (string $locale): void {
    $noPlayer = HowPlayingIsTold::notStarted(WhyPlayingDidNotStart::ThereIsNoPlayerHere);
    $cannot = HowPlayingIsTold::notStarted(WhyPlayingDidNotStart::WhatTheHouseSaidCannotBePlayed);

    expect(__($noPlayer, locale: $locale))->not->toBe($noPlayer)
        ->and(__($cannot, locale: $locale))->not->toBe($cannot)
        ->and(__($noPlayer, locale: $locale))->not->toBe(__($cannot, locale: $locale));
})->with(['en', 'nl']);
