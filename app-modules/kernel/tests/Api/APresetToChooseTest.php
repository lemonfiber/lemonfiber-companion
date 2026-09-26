<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\AFormatChoiceMade;
use Modules\Kernel\Api\AFormatInForce;
use Modules\Kernel\Api\AHeldChoice;
use Modules\Kernel\Api\APresetToChoose;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\QualitySaysNothing;
use Modules\Kernel\Api\ThePresetsInForce;
use Modules\Kernel\Api\TheQualityChosen;
use Modules\Kernel\Api\ThereIsNothingToAgreeTo;
use Modules\Kernel\Api\WhatBecameOfAskingIt;
use Modules\Kernel\Api\WhatBecameOfTheChoice;
use Modules\Kernel\Api\WhatMusicIsSetTo;
use Modules\Kernel\Api\WhatTheChoiceCameTo;
use Modules\Kernel\Api\WhatToDoAboutQuality;

use function sprintf;

/** One line carried out of an arm. */
final readonly class WhichArmTheChoiceTook
{
    public function __construct(public string $said) {}
}

/** An answer about quality that became what is given. */
function anAnswerThatBecame(WhatBecameOfTheChoice $became): TheQualityChosen
{
    return TheQualityChosen::reported(ThePresetsInForce::of(), WhatMusicIsSetTo::unset(), $became, customised: false);
}

it('keeps the preset and the kind as named, trimmed', function (): void {
    $asked = APresetToChoose::named(' maximum ', ' movies ');

    expect([$asked->preset(), $asked->kind(), $asked->isForEverything()])->toBe(['maximum', 'movies', false]);
});

it('reads no kind as everything', function (): void {
    $asked = APresetToChoose::named('balanced', '  ');

    expect([$asked->kind(), $asked->isForEverything()])->toBe(['', true]);
});

it('refuses a choice that names no preset', function (): void {
    expect(static fn(): APresetToChoose => APresetToChoose::named(' ', 'movies'))
        ->toThrow(QualitySaysNothing::class, '`preset` blank');
});

it('makes a yes only out of an answer that held the choice, and keeps what was asked', function (): void {
    $asked = APresetToChoose::named('maximum', 'movies');

    expect(AHeldChoice::of($asked, anAnswerThatBecame(WhatBecameOfTheChoice::Held))->asked())->toBe($asked);
});

it('refuses a yes to an answer that held nothing, naming what it became', function (WhatBecameOfTheChoice $became): void {
    expect(static fn(): AHeldChoice => AHeldChoice::of(APresetToChoose::named('maximum', ''), anAnswerThatBecame($became)))
        ->toThrow(ThereIsNothingToAgreeTo::class, sprintf('answered `%s`', $became->value));
})->with([
    WhatBecameOfTheChoice::Shown,
    WhatBecameOfTheChoice::Recorded,
    WhatBecameOfTheChoice::Rehearsed,
    WhatBecameOfTheChoice::Reapplied,
    WhatBecameOfTheChoice::WouldReapply,
]);

it('tells a choice answered with what is in force from one for music and from what stood in the way', function (): void {
    $chosen = anAnswerThatBecame(WhatBecameOfTheChoice::Recorded);
    $made = AFormatChoiceMade::reported(
        AFormatInForce::reported('music', 'Lossless', 'Nothing thrown away', 'FLAC', '~300 MB', 'Some players convert it'),
        WhatBecameOfTheChoice::Rehearsed,
        WhatBecameOfAskingIt::notAsked(),
    );
    $arm = static fn(WhatTheChoiceCameTo $came): string => $came->either(
        inForce: static fn(TheQualityChosen $read): WhichArmTheChoiceTook => new WhichArmTheChoiceTook($read->became()->value),
        forMusic: static fn(AFormatChoiceMade $read): WhichArmTheChoiceTook => new WhichArmTheChoiceTook(sprintf('%s %s %s', $read->format()->format(), $read->became()->value, $read->applied()->saidOnTheScreen())),
        met: static fn(Obstacle $why): WhichArmTheChoiceTook => new WhichArmTheChoiceTook($why->value),
    )->said;

    expect($arm(WhatTheChoiceCameTo::inForce($chosen)))->toBe('recorded')
        ->and($arm(WhatTheChoiceCameTo::forMusic($made)))->toBe('Lossless rehearsed quality.asked.not-asked')
        ->and($arm(WhatTheChoiceCameTo::met(Obstacle::NotForThisAccount)))->toBe('not_for_this_account');
});

it('asks for each act about quality by lemonfiber\'s name for it', function (): void {
    expect(WhatToDoAboutQuality::Choose->asked())->toBe('quality-set')
        ->and(WhatToDoAboutQuality::Upgrade->asked())->toBe('quality-upgrade');
});
