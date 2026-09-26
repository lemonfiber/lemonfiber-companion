<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\AFormatInForce;
use Modules\Kernel\Api\APresetInForce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\QualitySaysNothing;
use Modules\Kernel\Api\ThePresetsInForce;
use Modules\Kernel\Api\TheQualityChosen;
use Modules\Kernel\Api\WhatBecameOfTheChoice;
use Modules\Kernel\Api\WhatMusicIsSetTo;
use Modules\Kernel\Api\WhatWasFoundOfTheQuality;

use function sprintf;

/** One line carried out of an arm. */
final readonly class WhichArmTheQualityTook
{
    public function __construct(public string $said) {}
}

/** A preset with every word said, and one of them replaced where a test names it. */
function aPresetSaying(string $scope = 'movies', string $preset = 'Maximum', string $means = '4K where it exists', string $resolution = '2160p', string $size = '~15 GB', string $transcoding = 'Needs a strong client'): APresetInForce
{
    return APresetInForce::reported($scope, $preset, $means, $resolution, $size, $transcoding, transcodesHere: true);
}

/** A format with every word said, and one of them replaced where a test names it. */
function aFormatSaying(string $scope = 'music', string $format = 'Lossless', string $means = 'Nothing thrown away', string $targets = 'FLAC', string $size = '~300 MB', string $note = 'Some players convert it'): AFormatInForce
{
    return AFormatInForce::reported($scope, $format, $means, $targets, $size, $note);
}

/** What music is set to, as one line. */
function whatMusicIsSetToAsText(WhatMusicIsSetTo $music): string
{
    return $music->either(
        set: static fn(AFormatInForce $format): WhichArmTheQualityTook => new WhichArmTheQualityTook($format->format()),
        unset: static fn(): WhichArmTheQualityTook => new WhichArmTheQualityTook('unset'),
    )->said;
}

it('keeps every word of a preset as it was reported, and whether it transcodes here', function (): void {
    $preset = aPresetSaying();
    $direct = APresetInForce::reported('everything', 'Balanced', 'On a TV', '1080p', '~2 GB', 'Plays directly', transcodesHere: false);

    expect([$preset->scope(), $preset->preset(), $preset->means(), $preset->resolution(), $preset->sizePerHour(), $preset->transcoding(), $preset->transcodesHere()])
        ->toBe(['movies', 'Maximum', '4K where it exists', '2160p', '~15 GB', 'Needs a strong client', true])
        ->and($direct->transcodesHere())->toBeFalse();
});

it('refuses a preset with any word left blank, naming the word', function (string $field): void {
    expect(static fn(): APresetInForce => match ($field) {
        'scope' => aPresetSaying(scope: ' '),
        'preset' => aPresetSaying(preset: ''),
        'means' => aPresetSaying(means: ' '),
        'resolution' => aPresetSaying(resolution: ''),
        'size_per_hour' => aPresetSaying(size: ' '),
        default => aPresetSaying(transcoding: ''),
    })->toThrow(QualitySaysNothing::class, sprintf('`%s` blank', $field));
})->with(['scope', 'preset', 'means', 'resolution', 'size_per_hour', 'transcoding']);

it('keeps every word of a music format as it was reported', function (): void {
    $format = aFormatSaying();

    expect([$format->scope(), $format->format(), $format->means(), $format->targets(), $format->sizePerHour(), $format->note()])
        ->toBe(['music', 'Lossless', 'Nothing thrown away', 'FLAC', '~300 MB', 'Some players convert it']);
});

it('refuses a music format with any word left blank, naming the word', function (string $field): void {
    expect(static fn(): AFormatInForce => match ($field) {
        'scope' => aFormatSaying(scope: ''),
        'format' => aFormatSaying(format: ' '),
        'means' => aFormatSaying(means: ''),
        'targets' => aFormatSaying(targets: ' '),
        'size_per_hour' => aFormatSaying(size: ''),
        default => aFormatSaying(note: ' '),
    })->toThrow(QualitySaysNothing::class, sprintf('`%s` blank', $field));
})->with(['scope', 'format', 'means', 'targets', 'size_per_hour', 'note']);

it('keeps the presets in the order the stack gave them, and counts them', function (): void {
    $overall = aPresetSaying(scope: 'everything');
    $movies = aPresetSaying();
    $presets = ThePresetsInForce::of(...['first' => $overall, 'second' => $movies]);

    expect(iterator_to_array($presets, preserve_keys: true))->toBe([$overall, $movies])
        ->and($presets)->toHaveCount(2)
        ->and(ThePresetsInForce::of())->toHaveCount(0);
});

it('tells music with a format from music with none', function (): void {
    expect(whatMusicIsSetToAsText(WhatMusicIsSetTo::set(aFormatSaying())))->toBe('Lossless')
        ->and(whatMusicIsSetToAsText(WhatMusicIsSetTo::unset()))->toBe('unset');
});

it('keeps everything the quality was reported with', function (): void {
    $presets = ThePresetsInForce::of(aPresetSaying());
    $music = WhatMusicIsSetTo::unset();
    $chosen = TheQualityChosen::reported($presets, $music, WhatBecameOfTheChoice::Held, customised: true);

    expect([$chosen->presets(), $chosen->music(), $chosen->became(), $chosen->customised()])
        ->toBe([$presets, $music, WhatBecameOfTheChoice::Held, true])
        ->and(TheQualityChosen::reported($presets, $music, WhatBecameOfTheChoice::Shown, customised: false)->customised())->toBeFalse();
});

it('tells the quality found from what stood in the way', function (): void {
    $chosen = TheQualityChosen::reported(ThePresetsInForce::of(), WhatMusicIsSetTo::unset(), WhatBecameOfTheChoice::Shown, customised: false);
    $found = static fn(WhatWasFoundOfTheQuality $answer): string => $answer->either(
        found: static fn(TheQualityChosen $read): WhichArmTheQualityTook => new WhichArmTheQualityTook($read === $chosen ? 'found' : 'another'),
        met: static fn(Obstacle $why): WhichArmTheQualityTook => new WhichArmTheQualityTook($why->value),
    )->said;

    expect($found(WhatWasFoundOfTheQuality::found($chosen)))->toBe('found')
        ->and($found(WhatWasFoundOfTheQuality::met(Obstacle::StackDidNotAnswer)))->toBe('no_answer');
});

it('keys what became of a choice by the stack\'s own word', function (): void {
    expect(WhatBecameOfTheChoice::Held->saidOnTheScreen())->toBe('quality.became.held')
        ->and(WhatBecameOfTheChoice::WouldReapply->saidOnTheScreen())->toBe('quality.became.would-reapply');
});
