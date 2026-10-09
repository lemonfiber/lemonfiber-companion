<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use ArrayObject;

use function expect;
use function it;

use Modules\Kernel\Api\AGrant;
use Modules\Kernel\Api\AnEpisode;
use Modules\Kernel\Api\ASeason;
use Modules\Kernel\Api\ATitle;
use Modules\Kernel\Api\ATitleToPlay;
use Modules\Kernel\Api\Episodes;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Genres;
use Modules\Kernel\Api\HearIn;
use Modules\Kernel\Api\Holding;
use Modules\Kernel\Api\HoldingId;
use Modules\Kernel\Api\HowFarIn;
use Modules\Kernel\Api\HowLongItRuns;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\ItsDetails;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Location;
use Modules\Kernel\Api\Medium;
use Modules\Kernel\Api\NumberedAs;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\PlaybackIs;
use Modules\Kernel\Api\ReadIn;
use Modules\Kernel\Api\Seasons;
use Modules\Kernel\Api\Sentence;
use Modules\Kernel\Api\TheirLanguages;
use Modules\Kernel\Api\ThePlace;
use Modules\Kernel\Api\WhatOpeningCameTo;
use Modules\Kernel\Api\WhatPlayPlays;
use Modules\Kernel\Api\WhatThePlaceCameTo;
use Modules\Kernel\Api\WhenItCameOut;
use Modules\Kernel\Api\WhenItWasReleased;
use Modules\Kernel\Api\WhereItPlays;
use Modules\Kernel\Api\WherePlayingStands;
use Modules\Kernel\Api\WhyPlayingDidNotStart;

use function sprintf;
use function str_repeat;

/** Where the core says something streams, at the door. Named for this file. */
function streamingFor(string $id): WhereItPlays
{
    return WhereItPlays::at(Location::of(sprintf('https://door.example/%s', $id)), Fingerprint::of(str_repeat('d', Fingerprint::CHARACTERS)));
}

/** A title answered with these, and these seasons. Named for this file. */
function aTitleThatPlays(WhereItPlays $plays, Seasons $seasons): ATitle
{
    return ATitle::of(Holding::of(HoldingId::called('t1'), 'The title', Medium::Series, WhenItCameOut::unstated()), ItsDetails::of('', HowLongItRuns::unstated(), Genres::of(), '', WhenItWasReleased::unstated()), $plays, $seasons);
}

/** One season of two episodes, the first `e1` and the second `e2`. Named for this file. */
function twoEpisodes(): Seasons
{
    return Seasons::of(ASeason::of('Season 1', Episodes::of(
        AnEpisode::of(HoldingId::called('e1'), 'One', NumberedAs::number(1), HowLongItRuns::unstated(), '', streamingFor('e1')),
        AnEpisode::of(HoldingId::called('e2'), 'Two', NumberedAs::number(2), HowLongItRuns::unstated(), '', WhereItPlays::cannot(Sentence::of('Not yet.'))),
    )));
}

/** What a Play plays, as `<id> <name>`, or `nothing`. Named for this file. */
function whatItPlays(WhatPlayPlays $plays): string
{
    return $plays->either(
        one: static fn(HoldingId $id, string $titled): ArrayObject => new ArrayObject([sprintf('%s %s', $id->named(), $titled)]),
        nothing: static fn(): ArrayObject => new ArrayObject(['nothing']),
    )->getArrayCopy()[0];
}

it('counts a place in whole seconds from the start, and a place before the start as the start', function (): void {
    expect(HowFarIn::at(61)->seconds())->toBe(61)
        ->and(HowFarIn::at(61)->isTheStart())->toBeFalse()
        ->and(HowFarIn::at(-5)->seconds())->toBe(0)
        ->and(HowFarIn::at(0)->isTheStart())->toBeTrue()
        ->and(HowFarIn::theStart()->seconds())->toBe(0);
});

it('says a place reached the end only where it was told so', function (): void {
    $partWay = ThePlace::in(HoldingId::called('a1'), HowFarIn::at(61));
    $atTheEnd = ThePlace::atTheEndOf(HoldingId::called('a1'), HowFarIn::at(5_400));

    expect($partWay->holding()->named())->toBe('a1')
        ->and($partWay->howFarIn()->seconds())->toBe(61)
        ->and($partWay->isTheEnd())->toBeFalse()
        ->and($atTheEnd->isTheEnd())->toBeTrue();
});

it('plays a title itself where the core says where it plays or why it cannot', function (): void {
    expect(whatItPlays(aTitleThatPlays(streamingFor('t1'), twoEpisodes())->whatPlayPlays()))->toBe('t1 The title')
        ->and(whatItPlays(aTitleThatPlays(WhereItPlays::cannot(Sentence::of('No door.')), twoEpisodes())->whatPlayPlays()))->toBe('t1 The title');
});

it('plays the first episode the core lists where the title does not stream itself, and nothing where it lists none', function (): void {
    expect(whatItPlays(aTitleThatPlays(WhereItPlays::doesNotStream(), twoEpisodes())->whatPlayPlays()))->toBe('e1 One')
        ->and(whatItPlays(aTitleThatPlays(WhereItPlays::doesNotStream(), Seasons::none())->whatPlayPlays()))->toBe('nothing')
        ->and(whatItPlays(aTitleThatPlays(WhereItPlays::doesNotStream(), Seasons::of(ASeason::of('Empty', Episodes::of())))->whatPlayPlays()))->toBe('nothing');
});

it('plays an episode it holds by its id, and nothing by an id it does not', function (): void {
    $title = aTitleThatPlays(WhereItPlays::doesNotStream(), twoEpisodes());

    expect(whatItPlays($title->theEpisode(HoldingId::called('e2'))))->toBe('e2 Two')
        ->and(whatItPlays($title->theEpisode(HoldingId::called('e3'))))->toBe('nothing');
});

it('hands a title to play over as it was given', function (): void {
    $grant = AGrant::of(str_repeat('a', 32), Instant::atEpochSeconds(1));
    $title = ATitleToPlay::of(Location::of('https://door.example/a1'), Fingerprint::of(str_repeat('d', 64)), $grant, HowFarIn::at(61), 'Alien', TheirLanguages::of(HearIn::English, ReadIn::Nothing));

    expect($title->location()->forThePlayer())->toBe('https://door.example/a1')
        ->and($title->door()->forThePlayer())->toBe(str_repeat('d', 64))
        ->and($title->grant())->toBe($grant)
        ->and($title->startAt()->seconds())->toBe(61)
        ->and($title->named())->toBe('Alien')
        ->and($title->languages())->toEqual(TheirLanguages::of(HearIn::English, ReadIn::Nothing));
});

it('says where the player stands and how far in', function (): void {
    $stands = WherePlayingStands::of(PlaybackIs::Paused, HowFarIn::at(45));

    expect($stands->is())->toBe(PlaybackIs::Paused)
        ->and($stands->howFarIn()->seconds())->toBe(45);
});

it('answers opening on the opened arm and its refusal on the refused one', function (): void {
    $opened = WhatOpeningCameTo::opened()->either(
        opened: static fn(): ArrayObject => new ArrayObject(['opened']),
        refused: static fn(WhyPlayingDidNotStart $why): ArrayObject => new ArrayObject([$why]),
    );
    $refused = WhatOpeningCameTo::refused(WhyPlayingDidNotStart::ThereIsNoPlayerHere)->either(
        opened: static fn(): ArrayObject => new ArrayObject(['opened']),
        refused: static fn(WhyPlayingDidNotStart $why): ArrayObject => new ArrayObject([$why]),
    );

    expect($opened->getArrayCopy())->toBe(['opened'])
        ->and($refused->getArrayCopy())->toBe([WhyPlayingDidNotStart::ThereIsNoPlayerHere]);
});

it('answers a place kept on the kept arm and an obstacle on the refused one', function (): void {
    $why = Obstacle::of(KindOfObstacle::CredentialWasRefused);
    $place = ThePlace::in(HoldingId::called('a1'), HowFarIn::at(61));
    $kept = WhatThePlaceCameTo::kept($place)->either(
        kept: static fn(ThePlace $given): ArrayObject => new ArrayObject([$given]),
        refused: static fn(Obstacle $given): ArrayObject => new ArrayObject([$given]),
    );
    $refused = WhatThePlaceCameTo::refused($why)->either(
        kept: static fn(): ArrayObject => new ArrayObject(['kept']),
        refused: static fn(Obstacle $given): ArrayObject => new ArrayObject([$given]),
    );

    expect($kept->getArrayCopy())->toBe([$place])
        ->and($refused->getArrayCopy())->toBe([$why]);
});
