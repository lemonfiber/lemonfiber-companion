<?php

declare(strict_types=1);

use Illuminate\Contracts\Translation\Translator;
use Modules\Kernel\Api\APartTheWordsLeaveOut;
use Modules\Kernel\Api\Cost;
use Modules\Kernel\Api\Findings;
use Modules\Kernel\Api\HowItRunsWithoutLemonfiber;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Overall;
use Modules\Kernel\Api\ProposedChange;
use Modules\Kernel\Api\Report;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Setting;
use Modules\Kernel\Api\Settings;
use Modules\Kernel\Api\Stance;
use Modules\Kernel\Api\TheCommandLine;
use Modules\Kernel\Api\ThePartsTheWordsLeaveOut;
use Modules\Kernel\Api\WhatAChangeComesTo;
use Modules\Kernel\Api\WhatASettingHolds;
use Modules\Kernel\Api\WhatItHoldsNow;
use Modules\Kernel\Api\WhereTheChangeStands;
use Modules\Kernel\Api\WhoPutItThere;
use Modules\Kernel\Api\Whose;
use Modules\Kernel\Api\WhoseStackItIs;
use Modules\Kernel\Api\WhyNothingWasShared;
use Modules\Operator\Internal\Screens\WhatThisStackIsSetTo;
use Tests\Support\AroundThePhone;
use Tests\Support\Catalogue;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AShareSheetThatWasOffered;
use Tests\Support\Fakes\AStackThatIsSet;
use Tests\Support\Fakes\AStackThatSaysHowItRunsWithout;
use Tests\Support\Fakes\AStackThatWasAsked;
use Tests\Support\Fakes\AStackToldToChangeSomething;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\TheHealthScreenOfTheLoft;
use Tests\Support\WhatTheDeviceWouldDraw;

// How a stack runs without lemonfiber, on its settings, and whose stack it is, on its home.

/** The operator's own stack in /home/anna/stack, with one part the plain words leave out. */
function theOperatorsOwnStack(): HowItRunsWithoutLemonfiber
{
    return HowItRunsWithoutLemonfiber::said(
        WhoseStackItIs::theOperators('/home/anna/stack'),
        '/home/anna/stack',
        '/home/anna/stack/.env',
        TheCommandLine::of('docker', 'compose', '--project-directory', '/home/anna/stack', 'up', '-d'),
        'lemonfiber --yes --no-input',
        ThePartsTheWordsLeaveOut::these(APartTheWordsLeaveOut::because('gluetun', 'It needs the VPN key from my password manager first')),
    );
}

/** The settings screen of the loft, saying how it runs without lemonfiber as the fake says. */
function theSettingsOfAStackThatRunsWithout(AStackThatSaysHowItRunsWithout $without, ?AShareSheetThatWasOffered $sheet = null): WhatThisStackIsSetTo
{
    $stack = TheHealthScreenOfTheLoft::theStackBeingLookedAt();
    $keychain = AKeychainInMemory::working();
    $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());

    $screen = new WhatThisStackIsSetTo(
        AStackThatIsSet::to(Settings::of(Setting::called('BIND', WhatASettingHolds::shown('lan'), WhoPutItThere::bundled()))),
        AStackToldToChangeSomething::saying(WhereTheChangeStands::at(ProposedChange::of('BIND', 'any', WhatItHoldsNow::shown('lan'), Cost::Cheap), Stance::Pending, WhatAChangeComesTo::nothing())),
        $keychain,
        AroundThePhone::holding(StacksInMemory::holding($stack)),
        new AppsSettingsThatOpen(),
        AroundThePhone::listening(),
        $without,
        $sheet ?? AShareSheetThatWasOffered::working(),
        app(Translator::class),
    );
    $screen->setParams(['stack' => $stack->id()->stored()]);

    return $screen;
}

/**
 * Whether any line says the operator runs their own stack, whatever its directory.
 *
 * @param list<string> $said
 */
function saysItIsTheirOwn(array $said): bool
{
    $opening = rtrim(aLineWithout('without.own_stack', ['path' => '']), ')');

    return array_filter($said, static fn(string $line): bool => str_starts_with($line, $opening)) !== [];
}

/**
 * A line of these screens, as the catalogue says it.
 *
 * @param array<string, string> $with
 */
function aLineWithout(string $key, array $with = []): string
{
    return Catalogue::words()->for($key, $with);
}

it('says the stack runs without lemonfiber, with the words that bring it up, where it lives, how an action asks nothing, and what the words leave out', function (): void {
    $said = WhatTheDeviceWouldDraw::onTheSecondFrame(theSettingsOfAStackThatRunsWithout(AStackThatSaysHowItRunsWithout::with(theOperatorsOwnStack())))->said();

    expect($said)->toContain(aLineWithout('without.runs_without'))
        ->and($said)->toContain('docker compose --project-directory /home/anna/stack up -d')
        ->and($said)->toContain(aLineWithout('without.project', ['path' => '/home/anna/stack']))
        ->and($said)->toContain(aLineWithout('without.env', ['path' => '/home/anna/stack/.env']))
        ->and($said)->toContain(aLineWithout('without.non_interactive'))
        ->and($said)->toContain('lemonfiber --yes --no-input')
        ->and($said)->toContain('gluetun')
        ->and($said)->toContain('It needs the VPN key from my password manager first')
        ->and($said)->not->toContain(aLineWithout('without.nothing_left_out'));
});

it('says on the settings that the operator runs their own stack, and nothing of the kind for the bundled one', function (): void {
    $own = WhatTheDeviceWouldDraw::onTheSecondFrame(theSettingsOfAStackThatRunsWithout(AStackThatSaysHowItRunsWithout::with(theOperatorsOwnStack())))->said();
    $bundled = WhatTheDeviceWouldDraw::onTheSecondFrame(theSettingsOfAStackThatRunsWithout(AStackThatSaysHowItRunsWithout::bundled()))->said();

    expect($own)->toContain(aLineWithout('without.own_stack', ['path' => '/home/anna/stack']))
        ->and(saysItIsTheirOwn($own))->toBeTrue()
        ->and(saysItIsTheirOwn($bundled))->toBeFalse()
        ->and($bundled)->toContain(aLineWithout('without.nothing_left_out'));
});

it('reads the settings on one frame and how it runs without lemonfiber on the next', function (): void {
    $without = AStackThatSaysHowItRunsWithout::bundled();
    $screen = theSettingsOfAStackThatRunsWithout($without);
    $first = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($first)->toContain(aLineWithout('without.reading'))
        ->and($without->askings())->toBe(0);

    WhatTheDeviceWouldDraw::by($screen);

    expect($without->askings())->toBe(1);

    $screen->again();
    WhatTheDeviceWouldDraw::by($screen);
    WhatTheDeviceWouldDraw::by($screen);

    expect($without->askings())->toBe(2);
});

it('says how it runs without lemonfiber could not be read, rather than that there is nothing to say', function (): void {
    $said = WhatTheDeviceWouldDraw::onTheSecondFrame(theSettingsOfAStackThatRunsWithout(AStackThatSaysHowItRunsWithout::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))))->said();

    expect($said)->toContain(aLineWithout('without.unread'))
        ->and($said)->not->toContain(aLineWithout('without.runs_without'));
});

it('hands the words to the share sheet to copy out, under their name, and says what came of it', function (): void {
    $sheet = AShareSheetThatWasOffered::working();
    $screen = theSettingsOfAStackThatRunsWithout(AStackThatSaysHowItRunsWithout::with(theOperatorsOwnStack()), $sheet);
    $screen->copyTheWords();

    expect($sheet->copied())->toBeNull();

    WhatTheDeviceWouldDraw::onTheSecondFrame($screen);
    $screen->copyTheWords();

    expect($sheet->copied()?->text())->toBe('docker compose --project-directory /home/anna/stack up -d')
        ->and($sheet->copied()?->named())->toBe(aLineWithout('without.copy_named'))
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(aLineWithout('without.copied'));
});

it('says the words are on the screen where the share sheet would not open', function (): void {
    $screen = theSettingsOfAStackThatRunsWithout(AStackThatSaysHowItRunsWithout::bundled(), AShareSheetThatWasOffered::refusing(WhyNothingWasShared::TheDeviceWouldNotOffer));
    WhatTheDeviceWouldDraw::onTheSecondFrame($screen);
    $screen->copyTheWords();

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(aLineWithout('without.not_copied'));
});

it('says on the stack\'s home that the operator runs their own stack, on the frame after the findings', function (): void {
    $without = AStackThatSaysHowItRunsWithout::with(theOperatorsOwnStack());
    $screen = TheHealthScreenOfTheLoft::theHealthScreen(AStackThatWasAsked::saying(Report::of(Overall::Healthy, Findings::of())), without: $without);
    $first = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($first)->not->toContain(aLineWithout('without.own_stack', ['path' => '/home/anna/stack']))
        ->and($without->askings())->toBe(0)
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(aLineWithout('without.own_stack', ['path' => '/home/anna/stack']))
        ->and($without->askings())->toBe(1);
});

it('asks again how it runs without lemonfiber when the stack\'s home is asked again', function (): void {
    $without = AStackThatSaysHowItRunsWithout::with(theOperatorsOwnStack());
    $screen = TheHealthScreenOfTheLoft::theHealthScreen(AStackThatWasAsked::saying(Report::of(Overall::Healthy, Findings::of())), without: $without);

    WhatTheDeviceWouldDraw::onTheSecondFrame($screen);
    $screen->again();
    WhatTheDeviceWouldDraw::by($screen);
    WhatTheDeviceWouldDraw::by($screen);

    expect($without->askings())->toBe(2);
});

it('says nothing of whose stack it is on the home of the bundled one', function (): void {
    $screen = TheHealthScreenOfTheLoft::theHealthScreen(AStackThatWasAsked::saying(Report::of(Overall::Healthy, Findings::of())));

    expect(saysItIsTheirOwn(WhatTheDeviceWouldDraw::onTheSecondFrame($screen)->said()))->toBeFalse();
});
