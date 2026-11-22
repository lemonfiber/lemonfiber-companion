<?php

declare(strict_types=1);

use Modules\Kernel\Api\AnAreaLeftAlone;
use Modules\Kernel\Api\AThingLeftAlone;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\TheAreasLeftAlone;
use Modules\Kernel\Api\WhatAnAreaCovers;
use Modules\Kernel\Api\WhatIsLeftAlone;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\WhatIsLeftAloneHere;
use Modules\Stacks\Api\AStacksScreen;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\AroundThePhone;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AStackThatSaysWhatIsLeftAlone;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\TheHealthScreenOfTheLoft;
use Tests\Support\WhatTheDeviceWouldDraw;

/** Two areas the loft was told to leave alone, one cut short and covering a thing of every kind and more, one covering nothing, and three more unlisted. */
function whatTheLoftLeavesAlone(): TheAreasLeftAlone
{
    return TheAreasLeftAlone::these(
        3,
        AnAreaLeftAlone::declared('recyclarr profiles', areaCut: true, why: 'my own profiles live in here', whyCut: false, covers: WhatAnAreaCovers::these(
            5,
            AThingLeftAlone::of(WhatIsLeftAlone::Service, 'recyclarr'),
            AThingLeftAlone::of(WhatIsLeftAlone::Path, 'config/recyclarr/profiles.yml'),
            AThingLeftAlone::of(WhatIsLeftAlone::Setting, 'RECYCLARR_CRON'),
        )),
        AnAreaLeftAlone::declared('sonar', areaCut: false, why: 'tuned by hand for the seedbox', whyCut: true, covers: WhatAnAreaCovers::these(0)),
    );
}

/** The screen, asking the fake, signed in as the operator unless said. */
function theLeftAloneScreen(AStackThatSaysWhatIsLeftAlone $asking, ?AKeychainInMemory $keychain = null, bool $signedIn = true): WhatIsLeftAloneHere
{
    $stack = TheHealthScreenOfTheLoft::theStackBeingLookedAt();
    $keychain ??= AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    }

    $screen = new WhatIsLeftAloneHere($asking, $keychain, AroundThePhone::holding(StacksInMemory::holding($stack)), new AppsSettingsThatOpen(), AroundThePhone::listening());
    $screen->setParams(['stack' => $stack->id()->stored()]);

    return $screen;
}

/**
 * Every line the screen draws from its heading on, as many as asked for.
 *
 * @return list<string>
 */
function whatTheLeftAloneScreenSays(WhatIsLeftAloneHere $screen, int $lines): array
{
    $said = WhatTheDeviceWouldDraw::by($screen)->said();
    $from = array_search(__('stacks.left_alone.heading'), $said, strict: true);

    return is_int($from) ? array_slice($said, $from, $lines) : [];
}

it('is served at its own path and renders its own view', function (): void {
    $path = str_replace('{stack}', TheHealthScreenOfTheLoft::theStackBeingLookedAt()->id()->stored(), AStacksScreen::LeftAlone->value);

    expect(data_get(NativeRouter::resolve($path), 'class'))->toBe(WhatIsLeftAloneHere::class)
        ->and(theLeftAloneScreen(AStackThatSaysWhatIsLeftAlone::with(whatTheLoftLeavesAlone()))->render()->name())->toBe('operator::what-is-left-alone-here');
});

it('draws every area in the order declared, in the operator\'s words, each covered thing with its own kind, and how many more there are', function (): void {
    expect(whatTheLeftAloneScreenSays(theLeftAloneScreen(AStackThatSaysWhatIsLeftAlone::with(whatTheLoftLeavesAlone())), 25))->toBe([
        __('stacks.left_alone.heading'),
        __('stacks.left_alone.intro'),
        trans_choice('stacks.left_alone.declared', 5),
        'recyclarr profiles',
        __('stacks.left_alone.area_cut'),
        __('stacks.left_alone.your_reason'),
        'my own profiles live in here',
        __('stacks.left_alone.covers'),
        'recyclarr',
        __('stacks.left_alone.kind.service'),
        'config/recyclarr/profiles.yml',
        __('stacks.left_alone.kind.path'),
        'RECYCLARR_CRON',
        __('stacks.left_alone.kind.setting'),
        trans_choice('stacks.left_alone.covers_more', 5),
        'sonar',
        __('stacks.left_alone.your_reason'),
        'tuned by hand for the seedbox',
        __('stacks.left_alone.why_cut'),
        __('stacks.left_alone.covers_nothing'),
        trans_choice('stacks.left_alone.more', 3),
        __('stacks.left_alone.one_setting'),
        __('stacks.left_alone.to_the_settings'),
        __('stacks.left_alone.to_the_settings'),
        __('health.ask_again'),
    ]);
});

it('says one declaration in the singular, with nothing more to count', function (): void {
    $one = TheAreasLeftAlone::these(0, AnAreaLeftAlone::declared('qbittorrent', areaCut: false, why: 'my own categories', whyCut: false, covers: WhatAnAreaCovers::these(1, AThingLeftAlone::of(WhatIsLeftAlone::Service, 'qbittorrent'))));

    expect(whatTheLeftAloneScreenSays(theLeftAloneScreen(AStackThatSaysWhatIsLeftAlone::with($one)), 12))->toBe([
        __('stacks.left_alone.heading'),
        __('stacks.left_alone.intro'),
        trans_choice('stacks.left_alone.declared', 1),
        'qbittorrent',
        __('stacks.left_alone.your_reason'),
        'my own categories',
        __('stacks.left_alone.covers'),
        'qbittorrent',
        __('stacks.left_alone.kind.service'),
        trans_choice('stacks.left_alone.covers_more', 1),
        __('stacks.left_alone.one_setting'),
        __('stacks.left_alone.to_the_settings'),
    ]);
});

it('says nothing is declared, and never that the stack is too old to say', function (): void {
    expect(whatTheLeftAloneScreenSays(theLeftAloneScreen(AStackThatSaysWhatIsLeftAlone::with(TheAreasLeftAlone::these(0))), 7))->toBe([
        __('stacks.left_alone.heading'),
        __('stacks.left_alone.intro'),
        __('stacks.left_alone.none'),
        __('stacks.left_alone.one_setting'),
        __('stacks.left_alone.to_the_settings'),
        __('stacks.left_alone.to_the_settings'),
        __('health.ask_again'),
    ]);
});

it('says an older stack does not say, never that nothing is declared, and leads to its settings', function (): void {
    $screen = theLeftAloneScreen(AStackThatSaysWhatIsLeftAlone::tooOldToSay());

    expect(whatTheLeftAloneScreenSays($screen, 7))->toBe([
        __('stacks.left_alone.heading'),
        __('stacks.left_alone.intro'),
        __('stacks.left_alone.not_carried'),
        __('stacks.left_alone.one_setting'),
        __('stacks.left_alone.to_the_settings'),
        __('stacks.left_alone.to_the_settings'),
        __('health.ask_again'),
    ])->and(WhatTheDeviceWouldDraw::by($screen)->offers())->toContain(__('stacks.left_alone.to_the_settings'));
});

it('draws markup, a template and an address the operator wrote as the words they are', function (): void {
    $written = TheAreasLeftAlone::these(0, AnAreaLeftAlone::declared('<b>loud</b> area', areaCut: false, why: '{{ 1 + 1 }} javascript:alert(1)', whyCut: false, covers: WhatAnAreaCovers::these(0, AThingLeftAlone::of(WhatIsLeftAlone::Path, '<script>gone()</script>'))));

    expect(whatTheLeftAloneScreenSays(theLeftAloneScreen(AStackThatSaysWhatIsLeftAlone::with($written)), 10))->toBe([
        __('stacks.left_alone.heading'),
        __('stacks.left_alone.intro'),
        trans_choice('stacks.left_alone.declared', 1),
        '<b>loud</b> area',
        __('stacks.left_alone.your_reason'),
        '{{ 1 + 1 }} javascript:alert(1)',
        __('stacks.left_alone.covers'),
        '<script>gone()</script>',
        __('stacks.left_alone.kind.path'),
        __('stacks.left_alone.one_setting'),
    ]);
});

it('asks the stack once a frame, and again only when the operator asks', function (): void {
    $asking = AStackThatSaysWhatIsLeftAlone::with(whatTheLoftLeavesAlone());
    $screen = theLeftAloneScreen($asking);

    WhatTheDeviceWouldDraw::by($screen);
    WhatTheDeviceWouldDraw::by($screen);

    expect($asking->askings())->toBe(1);

    $screen->askAgain();
    WhatTheDeviceWouldDraw::by($screen);

    expect($asking->askings())->toBe(2);
});

it('says what stood in the way rather than an empty list', function (): void {
    $screen = theLeftAloneScreen(AStackThatSaysWhatIsLeftAlone::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)));
    $answer = $screen->answer();

    expect([$answer->went->isSignedIn, $answer->went->met, $answer->said, $answer->howMany, $answer->areas, $answer->more])
        ->toBe([true, KindOfObstacle::StackDidNotAnswer->said(), '', 0, [], 0])
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->not->toContain(__('stacks.left_alone.none'), __('stacks.left_alone.not_carried'));
});

it('does not ask a stack this phone is no longer signed into', function (): void {
    $asking = AStackThatSaysWhatIsLeftAlone::with(whatTheLoftLeavesAlone());
    $answer = theLeftAloneScreen($asking, signedIn: false)->answer();

    expect([$answer->went->isSignedIn, $answer->went->met, $answer->said, $answer->howMany, $answer->areas, $answer->more])->toBe([false, '', '', 0, [], 0])
        ->and($asking->askings())->toBe(0);
});

it('lets the session go when the stack refuses it', function (): void {
    $keychain = AKeychainInMemory::working();
    $screen = theLeftAloneScreen(AStackThatSaysWhatIsLeftAlone::met(Obstacle::of(KindOfObstacle::CredentialWasRefused)), $keychain);

    expect($screen->answer()->went->isSignedIn)->toBeFalse()
        ->and($keychain->isHolding(TheHealthScreenOfTheLoft::theStackBeingLookedAt()->id()))->toBeFalse();
});
