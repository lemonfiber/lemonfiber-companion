<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AFootprint;
use Modules\Kernel\Api\AgreedTo;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowAServiceRuns;
use Modules\Kernel\Api\HowTheStackIsRunning;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Services;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheServicesLeftOut;
use Modules\Kernel\Api\WhatIsAlreadyRunning;
use Modules\Kernel\Api\WhatStartingItWouldComeTo;
use Modules\Kernel\Api\WhatToDoWithIt;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\WhatThisStackRuns;
use Modules\Operator\Internal\Screens\WhatToDoWithThis;
use Modules\Services\Api\KeepingWhatItRuns;
use Tests\Support\AroundThePhone;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AStackThatRehearses;
use Tests\Support\Fakes\AStackThatSaysWhatItWaitsOn;
use Tests\Support\Fakes\AStackThatSupervises;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\WhatAMachineRuns;
use Tests\Support\WhatIsKeptOfServices;
use Tests\Support\WhatTheDeviceWouldDraw;

// What the phone kept of what a stack runs, drawn on opening for what it is,
// and nothing on it acted on until the stack answers.
//
// The Services tab opens on the listing kept from an earlier session, with
// when it was read, before the stack is asked anything; the fresh listing
// replaces it. While the kept one is drawn, start, stop and restart, by form
// and by service, are drawn and cannot be used, with the listing's age beside
// them, and a tap that reaches the screen anyway is refused.
//
// Asked of rendered frames, because every one of these is a claim about what
// the glass shows.

/** The moment the screen is opened at. Named for this file (`G10`). */
const WHEN_THE_SERVICES_WERE_OPENED = 1_790_000_000;

/** Two hours: how long before the opening the kept listing was read. */
const TWO_HOURS_BEFORE_THE_SERVICES = 7_200;

/** The machine whose listing was kept. */
function theStackWhoseListingWasKept(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('f', Nonce::SHORTEST))),
        StackName::of('The attic'),
        Address::of('https://192.168.1.47:8443'),
        Fingerprint::of(str_repeat('c', Fingerprint::CHARACTERS)),
    );
}

/** What the phone kept of the attic, read two hours before it was opened: every part a listing can have. */
function whatTheAtticsListingKept(): KeepingWhatItRuns
{
    $kept = WhatIsKeptOfServices::onAPhoneThatSealsAt(Instant::atEpochSeconds(WHEN_THE_SERVICES_WERE_OPENED - TWO_HOURS_BEFORE_THE_SERVICES));
    $kept->keeping->keep(theStackWhoseListingWasKept()->id(), WhatIsKeptOfServices::aListingWithEveryPart());
    $kept->clock->moveTo(Instant::atEpochSeconds(WHEN_THE_SERVICES_WERE_OPENED));

    return $kept->keeping;
}

/** Nothing kept of the attic, on a phone whose clock reads the opening. */
function nothingKeptOfTheAttic(): KeepingWhatItRuns
{
    return WhatIsKeptOfServices::onAPhoneThatSealsAt(Instant::atEpochSeconds(WHEN_THE_SERVICES_WERE_OPENED))->keeping;
}

/** A keychain holding the attic's session, or not. */
function theAtticsKeychain(bool $signedIn): AKeychainInMemory
{
    $keychain = AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep(theStackWhoseListingWasKept()->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    }

    return $keychain;
}

/** The attic's Services tab, over a stack that answers as a test says, signed in or not. */
function theAtticsServices(AStackThatSupervises $stack, KeepingWhatItRuns $kept, bool $signedIn = true): WhatThisStackRuns
{
    $attic = theStackWhoseListingWasKept();
    $screen = new WhatThisStackRuns(
        $stack,
        theAtticsKeychain($signedIn),
        AroundThePhone::holding(StacksInMemory::holding($attic)),
        new AppsSettingsThatOpen(),
        AroundThePhone::listening(),
        $kept,
    );
    $screen->setParams(['stack' => $attic->id()->stored()]);

    return $screen;
}

/** The attic's screen about one service or form, over a stack that answers as a test says. */
function oneThingInTheAttic(AStackThatSupervises $stack, KeepingWhatItRuns $kept, string $named, ?AStackThatRehearses $rehearsing = null): WhatToDoWithThis
{
    $attic = theStackWhoseListingWasKept();
    $screen = new WhatToDoWithThis(
        $stack,
        $rehearsing ?? AStackThatRehearses::with(WhatStartingItWouldComeTo::rehearsed(Services::none(), TheServicesLeftOut::of(), AFootprint::estimated(0, Services::none()), WhatIsAlreadyRunning::these(Services::none()))),
        theAtticsKeychain(signedIn: true),
        AroundThePhone::holding(StacksInMemory::holding($attic)),
        new AppsSettingsThatOpen(),
        AStackThatSaysWhatItWaitsOn::saying(),
        AroundThePhone::listening(),
        $kept,
    );
    $screen->setParams(['stack' => $attic->id()->stored(), 'service' => $named]);

    return $screen;
}

/** The stack the attic's screens meet: it has no network. */
function theAtticOutOfReach(): AStackThatSupervises
{
    return AStackThatSupervises::met(Obstacle::of(KindOfObstacle::DeviceHasNoNetwork));
}

/** What the attic runs when it answers: one service the kept listing never named. */
function theAtticAnswering(): AStackThatSupervises
{
    return AStackThatSupervises::with(WhatAMachineRuns::oneThing('radarr', HowAServiceRuns::Running, HowTheStackIsRunning::Active));
}

/**
 * What a key says in the catalogue, as a screen draws it.
 *
 * @param array<string, string> $replace
 */
function asTheAtticSaysIt(string $key, array $replace = []): string
{
    $said = __($key, $replace);

    return is_string($said) ? $said : $key;
}

/** How old the kept listing is, as the screen says it. */
function readTwoHoursAgo(): string
{
    return asTheAtticSaysIt('health.summary.as_of', ['ago' => trans_choice('health.ago.hours', 2)]);
}

/** Why a control waits, as the screen says it beside one. */
function usableOnceTheAtticAnswers(): string
{
    return asTheAtticSaysIt('connection.usable_once_the_stack_answers', ['ago' => trans_choice('health.ago.hours', 2)]);
}

/**
 * The labels these verbs are drawn under, in the order they are drawn.
 *
 * @param list<WhatToDoWithIt> $verbs
 *
 * @return list<string>
 */
function theVerbsDrawn(array $verbs): array
{
    return array_map(static fn(WhatToDoWithIt $verb): string => asTheAtticSaysIt($verb->saidOnTheScreen()), $verbs);
}

it('draws the kept listing with its age on the first frame, and the fresh one replaces it', function (): void {
    $stack = theAtticAnswering();
    $screen = theAtticsServices($stack, whatTheAtticsListingKept());

    $first = WhatTheDeviceWouldDraw::whileItOpens($screen);

    expect($first->said())->toContain(readTwoHoursAgo())
        ->and($first->said())->toContain('Jellyfin')
        ->and($first->said())->toContain('Sonarr')
        ->and($stack->askings())->toBe(0);

    $screen->mount();
    $fresh = WhatTheDeviceWouldDraw::by($screen);

    expect($fresh->said())->toContain('Radarr')
        ->and($fresh->said())->not->toContain('Jellyfin')
        ->and($fresh->said())->not->toContain(readTwoHoursAgo())
        ->and($stack->askings())->toBe(1);
});

it('keeps the fresh listing for the next opening', function (): void {
    $kept = nothingKeptOfTheAttic();
    $screen = theAtticsServices(theAtticAnswering(), $kept);
    $screen->mount();
    WhatTheDeviceWouldDraw::by($screen);

    $next = WhatTheDeviceWouldDraw::whileItOpens(theAtticsServices(theAtticOutOfReach(), $kept));

    expect($next->said())->toContain(__('health.summary.as_of', ['ago' => trans_choice('health.ago.minutes', 0)]))
        ->and($next->said())->toContain('Radarr');
});

it('keeps the kept listing, with its age, beside what stopped the stack answering', function (): void {
    $drawn = WhatTheDeviceWouldDraw::onTheSecondFrame(theAtticsServices(theAtticOutOfReach(), whatTheAtticsListingKept()));

    expect($drawn->said())->toContain(__('connection.no_network'))
        ->and($drawn->said())->toContain(readTwoHoursAgo())
        ->and($drawn->said())->toContain('Jellyfin')
        // The forms are those the kept listing names, so the form an operator
        // came to start is still there to open.
        ->and($drawn->offers())->toContain(__('health.open_form', ['name' => 'watching']));
});

it('keeps the kept listing beside the way back in where the session has ended', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theAtticsServices(theAtticAnswering(), whatTheAtticsListingKept(), signedIn: false));

    expect($drawn->said())->toContain(__('connection.session_has_ended'))
        ->and($drawn->said())->toContain(readTwoHoursAgo())
        ->and($drawn->said())->toContain('Jellyfin');
});

it('opens on the platform\'s indicator where nothing was kept, and draws what stopped the reading alone', function (): void {
    expect(WhatTheDeviceWouldDraw::whileItOpens(theAtticsServices(theAtticAnswering(), nothingKeptOfTheAttic()))->said())->toBe([]);

    $drawn = WhatTheDeviceWouldDraw::by(theAtticsServices(theAtticOutOfReach(), nothingKeptOfTheAttic()));

    expect($drawn->said())->toContain(__('connection.no_network'))
        ->and($drawn->said())->not->toContain('Jellyfin')
        ->and($drawn->offersThatWait())->toBe([]);
});

it('does not ask again on its own while it draws a kept listing caught settling', function (): void {
    $kept = WhatIsKeptOfServices::onAPhoneThatSealsAt(Instant::atEpochSeconds(WHEN_THE_SERVICES_WERE_OPENED));
    $kept->keeping->keep(theStackWhoseListingWasKept()->id(), WhatAMachineRuns::oneThing('sonarr', HowAServiceRuns::Starting, HowTheStackIsRunning::Partial));
    $stack = theAtticOutOfReach();
    $screen = theAtticsServices($stack, $kept->keeping);

    WhatTheDeviceWouldDraw::by($screen);
    $screen->whileItSettles();
    WhatTheDeviceWouldDraw::by($screen);

    expect($screen->answer()->waitsForTheStack)->toBeTrue()
        ->and($screen->answer()->isSettling)->toBeFalse()
        ->and($stack->askings())->toBe(1);
});

it('draws every verb on a service the kept listing names, waiting, and refuses them', function (): void {
    $stack = theAtticOutOfReach();
    $screen = oneThingInTheAttic($stack, whatTheAtticsListingKept(), 'sonarr');

    $drawn = WhatTheDeviceWouldDraw::by($screen);
    $verbs = $screen->thing()->verbs;

    expect($verbs)->not->toBe([])
        ->and($drawn->said())->toContain(__('connection.no_network'))
        ->and($drawn->said())->toContain(readTwoHoursAgo())
        ->and($drawn->said())->toContain(usableOnceTheAtticAnswers())
        ->and($drawn->offersThatWait())->toBe(theVerbsDrawn($verbs));

    // The control is the glass's guard; the screen is the other one. A tap
    // that reaches it is refused, and so is an agreement already being asked.
    $screen->wouldYouLike($verbs[0]->value);

    expect($screen->asking())->toBeNull();

    $screen->asking = AgreedTo::theService(WhatToDoWithIt::Restart, ServiceId::called('sonarr'));
    $asked = WhatTheDeviceWouldDraw::by($screen);
    $screen->agree();

    expect($asked->offersThatWait())->toBe([__('health.go_ahead')])
        ->and($asked->said())->toContain(usableOnceTheAtticAnswers())
        ->and($screen->whatItTakesAway())->toBeNull()
        ->and($stack->whatItWasToldToDo())->toBe([]);
});

it('draws every verb on a form the kept listing names, waiting, and rehearses nothing', function (): void {
    $rehearsing = AStackThatRehearses::met(Obstacle::of(KindOfObstacle::DeviceHasNoNetwork));
    $screen = oneThingInTheAttic(theAtticOutOfReach(), whatTheAtticsListingKept(), 'watching', $rehearsing);

    $drawn = WhatTheDeviceWouldDraw::onTheSecondFrame($screen);
    $screen->render();

    expect($screen->thing()->isAForm)->toBeTrue()
        ->and($drawn->offersThatWait())->toBe(theVerbsDrawn(WhatToDoWithIt::cases()))
        ->and($drawn->said())->toContain(usableOnceTheAtticAnswers())
        ->and($rehearsing->asked())->toBe([]);
});

it('offers the verbs once the stack answers, and acts on one agreed to', function (): void {
    $stack = AStackThatSupervises::with(WhatAMachineRuns::twoThings());
    $screen = oneThingInTheAttic($stack, whatTheAtticsListingKept(), 'sonarr');

    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($drawn->offersThatWait())->toBe([])
        ->and($drawn->said())->not->toContain(readTwoHoursAgo())
        ->and($drawn->said())->not->toContain(usableOnceTheAtticAnswers());

    $screen->wouldYouLike(WhatToDoWithIt::Stop->value);
    $screen->agree();

    expect($stack->whatItWasToldToDo())->toHaveCount(1);
});

it('draws one control for each action, and asking again once, in every state the screens can be in', function (): void {
    $states = [
        'the tab, kept, before the stack is asked' => WhatTheDeviceWouldDraw::whileItOpens(theAtticsServices(theAtticAnswering(), whatTheAtticsListingKept())),
        'the tab, fresh' => WhatTheDeviceWouldDraw::onTheSecondFrame(theAtticsServices(theAtticAnswering(), whatTheAtticsListingKept())),
        'the tab, kept, beside what stopped the reading' => WhatTheDeviceWouldDraw::onTheSecondFrame(theAtticsServices(theAtticOutOfReach(), whatTheAtticsListingKept())),
        'the tab, kept, beside the way back in' => WhatTheDeviceWouldDraw::by(theAtticsServices(theAtticAnswering(), whatTheAtticsListingKept(), signedIn: false)),
        'the tab, nothing kept, and the stack not read' => WhatTheDeviceWouldDraw::by(theAtticsServices(theAtticOutOfReach(), nothingKeptOfTheAttic())),
        'a service, kept, beside what stopped the reading' => WhatTheDeviceWouldDraw::by(oneThingInTheAttic(theAtticOutOfReach(), whatTheAtticsListingKept(), 'sonarr')),
        'a form, kept, beside what stopped the reading' => WhatTheDeviceWouldDraw::onTheSecondFrame(oneThingInTheAttic(theAtticOutOfReach(), whatTheAtticsListingKept(), 'watching')),
    ];

    foreach ($states as $which => $drawn) {
        $offers = $drawn->offers();
        $askingAgain = array_filter($offers, static fn(string $control): bool => $control === __('health.ask_again'));

        expect($offers)->toBe(array_values(array_unique($offers)), $which)
            ->and(count($askingAgain))->toBeLessThanOrEqual(1, $which);
    }
});
