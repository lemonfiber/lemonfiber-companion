<?php

declare(strict_types=1);

use Modules\Household\Internal\Screens\WhatThisTitleIs;
use Modules\Household\Internal\WhatATitleIsOpenedWith;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HoldingId;
use Modules\Kernel\Api\Medium;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Stacks\Api\AStacksScreen;
use Modules\Wayfinding\Api\TheHouseholdsTabs;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\AroundThePhone;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\WhatTheDeviceWouldDraw;

// One title's screen, opened from its poster or from Home's hero.
//
// It draws what opened it handed over and asks the house nothing, since the
// core answers no reading for one title. Here rather than in the household
// module's own tests because a screen renders, and rendering needs the
// application.

/** The machine the title is on. */
function theStackATitleIsOn(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('t', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('f', Fingerprint::CHARACTERS)),
    );
}

/**
 * The title's screen, handed what a poster hands it.
 *
 * @param array<array-key, mixed> $handed
 */
function theTitleScreen(array $handed): WhatThisTitleIs
{
    $stack = theStackATitleIsOn();
    $screen = new WhatThisTitleIs(AroundThePhone::holding(StacksInMemory::holding($stack)));
    $screen->setParams(['stack' => $stack->id()->stored(), 'service' => 'a1']);
    $screen->setData($handed);

    return $screen;
}

/**
 * What a poster for a dated film hands over.
 *
 * @return array<string, string>
 */
function whatAFilmsPosterHands(): array
{
    return [
        WhatATitleIsOpenedWith::Titled->value => 'Alien',
        WhatATitleIsOpenedWith::Medium->value => Medium::Film->value,
        WhatATitleIsOpenedWith::Year->value => '1979',
    ];
}

it('draws the title on its poster, under its name, with Play that waits and the reason beside it', function (): void {
    $screen = theTitleScreen(whatAFilmsPosterHands());
    $drawn = WhatTheDeviceWouldDraw::by($screen);
    $poster = array_search('Alien, Film, 1979', $drawn->said(), strict: true);

    expect($screen->title()->named)->toBe('Alien')
        ->and($poster)->toBeInt()
        ->and(array_slice($drawn->said(), (int) $poster, 5))->toBe([
            'Alien, Film, 1979', '1979 · Film', 'Alien',
            __('household.title.play'),
            __('household.title.cannot_play'),
        ])
        ->and($drawn->offersThatWait())->toBe([__('household.title.play')]);
});

it('draws an undated title undated', function (): void {
    $screen = theTitleScreen([...whatAFilmsPosterHands(), WhatATitleIsOpenedWith::Year->value => '']);

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->toContain('Alien, Film')
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->not->toContain('1979 · Film');
});

it('says to open it from Home again where it was opened without the title', function (array $handed): void {
    $screen = theTitleScreen($handed);

    expect($screen->title()->isKnown())->toBeFalse()
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('household.title.not_handed_over'))
        ->and(WhatTheDeviceWouldDraw::by($screen)->offers())->toBe([__('household.title.to_home')]);
})->with([
    'nothing handed over' => [[]],
    'no name' => [[...whatAFilmsPosterHands(), WhatATitleIsOpenedWith::Titled->value => '']],
    'a name that is not words' => [[...whatAFilmsPosterHands(), WhatATitleIsOpenedWith::Titled->value => 7]],
    'a kind this app has no word for' => [[...whatAFilmsPosterHands(), WhatATitleIsOpenedWith::Medium->value => 'podcast']],
    'a kind that is not words' => [[...whatAFilmsPosterHands(), WhatATitleIsOpenedWith::Medium->value => 1]],
]);

it('takes a year that is not words as no year', function (): void {
    expect(WhatTheDeviceWouldDraw::by(theTitleScreen([...whatAFilmsPosterHands(), WhatATitleIsOpenedWith::Year->value => 1979]))->said())
        ->toContain('Alien, Film');
});

it('is drawn under Home, opened over it, with the bar hidden', function (): void {
    $screen = theTitleScreen(whatAFilmsPosterHands());

    expect($screen->itsTab())->toBe(TheHouseholdsTabs::Home)
        ->and($screen->tabBarOptions())->not->toBeNull();
});

it('is the screen the router opens at a title\'s path', function (): void {
    $resolved = NativeRouter::resolve(AStacksScreen::Title->forTheStacksTitle(theStackATitleIsOn()->id(), HoldingId::called('a1')));

    expect($resolved['class'] ?? null)->toBe(WhatThisTitleIs::class);
});
