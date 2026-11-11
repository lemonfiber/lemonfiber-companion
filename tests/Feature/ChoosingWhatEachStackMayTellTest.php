<?php

declare(strict_types=1);

use Modules\Connection\Api\ChoosingWhatReachesThePhone;
use Modules\Connection\Api\TurningNotificationsOn;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KindOfNotification;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\KindsOfNotification;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\SetApart;
use Modules\Kernel\Api\Severity;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheReadingWaitsAFrame;
use Modules\Kernel\Api\WhatMayReachThePhone;
use Modules\Kernel\Api\WhatTheAlertsWere;
use Modules\Kernel\Api\WhatTheOperatorIsTold;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Presenters\HowWhatAStackMayTellReads;
use Modules\Operator\Internal\Screens\HowThisPhoneIsSet;
use Modules\Operator\Internal\Screens\WhatEachStackMayTellThisPhone;
use Modules\Operator\Internal\ViewModels\AChoiceOfASettingAsShown;
use Modules\Operator\Internal\ViewModels\AKindOfNotificationAsShown;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\WhatAStackMayTellAsShown;
use Modules\Wayfinding\Api\AScreenWithoutAStack;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\Catalogue;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\ANotifierInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\ASealInMemory;
use Tests\Support\Fakes\AStackThatOffers;
use Tests\Support\Fakes\AStackThatSaysWhatItTells;
use Tests\Support\Fakes\ConnectionSettingsInMemory;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\NotifyingKeptInMemory;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\Fakes\WakesInMemory;
use Tests\Support\WhatTheDeviceWouldDraw;

// The notification page: the phone's switch, and what each stack may tell it.

/** A stack on the phone, called `$called`. */
function aStackThatMayTell(string $seed, string $called): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat($seed, Nonce::SHORTEST))),
        StackName::of($called),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat($seed, Fingerprint::CHARACTERS)),
    );
}

/** What a stack raising these kinds says its operator is told about. */
function aStackRaising(KindOfNotification ...$kinds): WhatTheAlertsWere
{
    return WhatTheAlertsWere::told(WhatTheOperatorIsTold::byPreset('quiet', 'Only what needs you today', SetApart::of(), KindsOfNotification::these(...$kinds)));
}

/** What the operator chose for each stack, kept in memory. */
function theChoicesForEachStack(?NotifyingKeptInMemory $kept = null): ChoosingWhatReachesThePhone
{
    return new ChoosingWhatReachesThePhone(ASealInMemory::working(), $kept ?? NotifyingKeptInMemory::empty(), FrozenClock::at(Instant::atEpochSeconds(0)));
}

/**
 * The notification page over these stacks, each signed in unless named, with notifications on unless said, drawn until every stack is read.
 *
 * @param list<Stack> $stacks
 */
function theNotificationPage(
    array $stacks,
    AStackThatSaysWhatItTells $telling,
    ?ChoosingWhatReachesThePhone $choosing = null,
    bool $notifying = true,
    ?Stack $signedOut = null,
    ?AStackThatOffers $offering = null,
): WhatEachStackMayTellThisPhone {
    return everyStackRead(theNotificationPageUndrawn($stacks, $telling, $choosing, $notifying, $signedOut, $offering));
}

/**
 * The notification page over these stacks, as {@see theNotificationPage()} makes it, before its first frame.
 *
 * @param list<Stack> $stacks
 */
function theNotificationPageUndrawn(
    array $stacks,
    AStackThatSaysWhatItTells $telling,
    ?ChoosingWhatReachesThePhone $choosing = null,
    bool $notifying = true,
    ?Stack $signedOut = null,
    ?AStackThatOffers $offering = null,
    ?StacksInMemory $held = null,
): WhatEachStackMayTellThisPhone {
    $keychain = AKeychainInMemory::working();

    foreach ($stacks as $stack) {
        if ($stack !== $signedOut) {
            $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
        }
    }

    $switch = new TurningNotificationsOn(ASealInMemory::working(), ConnectionSettingsInMemory::empty(), ANotifierInMemory::allowed(), FrozenClock::at(Instant::atEpochSeconds(0)), WakesInMemory::thatWake());

    if ($notifying) {
        $switch->turnOn();
    }

    return new WhatEachStackMayTellThisPhone(
        $held ?? StacksInMemory::holding(...$stacks),
        $keychain,
        $telling,
        $choosing ?? theChoicesForEachStack(),
        $offering ?? AStackThatOffers::everything(),
        $switch,
        new AppsSettingsThatOpen(),
    );
}

/** The page drawn a frame at a time until it has read every stack, as the frames it asks for would. */
function everyStackRead(WhatEachStackMayTellThisPhone $page): WhatEachStackMayTellThisPhone
{
    do {
        WhatTheDeviceWouldDraw::by($page);
    } while ($page->waitsForTheNextFrame());

    return $page;
}


/**
 * Each switch a section draws, as kind and whether it is on.
 *
 * @return array<string, bool>
 */
function theSwitchesOf(WhatEachStackMayTellThisPhone $page, int $section): array
{
    $switches = [];

    foreach ($page->sections()[$section]->kinds as $kind) {
        $switches[$kind->kind] = $kind->isOn;
    }

    return $switches;
}

it('asks each stack again what it offers when the page opens', function (): void {
    $offering = AStackThatOffers::everything();
    $page = theNotificationPage([aStackThatMayTell('a', 'Home')], AStackThatSaysWhatItTells::eachAs(static fn(): WhatTheAlertsWere => aStackRaising(KindOfNotification::Problem)), offering: $offering);

    $page->mount();

    expect($offering->screensOpened())->toBe(1);
});

it('is served at its own path, and opened from App settings', function (): void {
    expect(data_get(NativeRouter::resolve(AScreenWithoutAStack::Notifications->value), 'class'))->toBe(WhatEachStackMayTellThisPhone::class)
        ->and(app(HowThisPhoneIsSet::class)->notificationPageIsAt())->toBe(AScreenWithoutAStack::Notifications->value);
});

it('draws a section for each stack, in the operator\'s order, with a switch on for each kind it raises and none other', function (): void {
    $loft = aStackThatMayTell('a', 'The loft');
    $shed = aStackThatMayTell('b', 'The shed');
    $page = theNotificationPage([$loft, $shed], AStackThatSaysWhatItTells::eachAs(static fn(Stack $asked): WhatTheAlertsWere => $asked === $loft
        ? aStackRaising(KindOfNotification::Member, KindOfNotification::Problem, KindOfNotification::Backup)
        : aStackRaising(KindOfNotification::Update)));

    expect(array_map(static fn(WhatAStackMayTellAsShown $section): string => $section->name, $page->sections()))->toBe(['The loft', 'The shed'])
        ->and(theSwitchesOf($page, 0))->toBe(['problem' => true, 'backup' => true, 'member' => true])
        ->and(theSwitchesOf($page, 1))->toBe(['update' => true])
        ->and(array_map(static fn(AKindOfNotificationAsShown $kind): string => $kind->said, $page->sections()[1]->kinds))->toBe(['notifications.kind.update']);
});

it('reads one stack a frame, asking for the next at once, and draws each read so far', function (): void {
    $loft = aStackThatMayTell('a', 'The loft');
    $shed = aStackThatMayTell('b', 'The shed');
    $telling = AStackThatSaysWhatItTells::eachAs(static fn(): WhatTheAlertsWere => aStackRaising(KindOfNotification::Update));
    $page = theNotificationPageUndrawn([$loft, $shed], $telling, notifying: false);
    $first = WhatTheDeviceWouldDraw::by($page);

    expect(array_map(static fn(WhatAStackMayTellAsShown $section): string => $section->name, $page->sections()))->toBe(['The loft'])
        ->and($page->waitsForTheNextFrame())->toBeTrue()
        ->and($telling->askings())->toBe(1)
        ->and($first->said())->toContain('The loft')
        ->and($first->said())->not->toContain('The shed');

    WhatTheDeviceWouldDraw::by($page);

    expect(array_map(static fn(WhatAStackMayTellAsShown $section): string => $section->name, $page->sections()))->toBe(['The loft', 'The shed'])
        ->and($page->waitsForTheNextFrame())->toBeFalse()
        ->and($telling->askings())->toBe(2);

    WhatTheDeviceWouldDraw::by($page);

    expect($telling->askings())->toBe(2);
});

it('reads the stacks on the phone once a frame, however often the page asks for its sections', function (): void {
    $loft = aStackThatMayTell('a', 'The loft');
    $held = StacksInMemory::holding($loft);
    $page = theNotificationPageUndrawn([$loft], AStackThatSaysWhatItTells::eachAs(static fn(): WhatTheAlertsWere => aStackRaising(KindOfNotification::Update)), held: $held);

    WhatTheDeviceWouldDraw::by($page);
    $page->sections();
    $page->sections();

    expect($held->timesAsked())->toBe(1);
});

it('asks for the next frame at once where its one stack had to be asked what it serves, and draws it there', function (): void {
    $waited = false;
    $telling = AStackThatSaysWhatItTells::eachAs(static function () use (&$waited): WhatTheAlertsWere {
        if (! $waited) {
            $waited = true;

            throw TheReadingWaitsAFrame::becauseTheStackWasAskedWhatItServes();
        }

        return aStackRaising(KindOfNotification::Update);
    });
    $page = theNotificationPageUndrawn([aStackThatMayTell('a', 'The loft')], $telling);

    WhatTheDeviceWouldDraw::by($page);

    expect($page->sections())->toBe([])
        ->and($page->waitsForTheNextFrame())->toBeTrue();

    WhatTheDeviceWouldDraw::by($page);

    expect(array_map(static fn(WhatAStackMayTellAsShown $section): string => $section->name, $page->sections()))->toBe(['The loft'])
        ->and($page->waitsForTheNextFrame())->toBeFalse();
});

it('asks each stack once a frame, however often the page draws', function (): void {
    $telling = AStackThatSaysWhatItTells::with(WhatTheOperatorIsTold::byPreset('quiet', 'Only', SetApart::of(), KindsOfNotification::these(KindOfNotification::Problem)));
    $page = theNotificationPage([aStackThatMayTell('a', 'The loft')], $telling);

    WhatTheDeviceWouldDraw::by($page);
    $page->sections();
    $page->kindTurned(aStackThatMayTell('a', 'The loft')->id()->stored(), 'problem', on: false);

    expect($telling->askings())->toBe(1);
});

it('keeps a kind switched off for its stack alone, and draws it off without asking the stack again', function (): void {
    $loft = aStackThatMayTell('a', 'The loft');
    $shed = aStackThatMayTell('b', 'The shed');
    $choosing = theChoicesForEachStack();
    $page = theNotificationPage([$loft, $shed], AStackThatSaysWhatItTells::eachAs(static fn(): WhatTheAlertsWere => aStackRaising(KindOfNotification::Backup, KindOfNotification::Update)), $choosing);
    $page->sections();

    $page->kindTurned($loft->id()->stored(), 'backup', on: false);

    expect(theSwitchesOf($page, 0))->toBe(['update' => true, 'backup' => false])
        ->and(theSwitchesOf($page, 1))->toBe(['update' => true, 'backup' => true])
        ->and($choosing->forTheStack($loft->id())->isOn(KindOfNotification::Backup))->toBeFalse()
        ->and($choosing->forTheStack($shed->id())->isOn(KindOfNotification::Backup))->toBeTrue();

    $page->kindTurned($loft->id()->stored(), 'backup', on: true);

    expect(theSwitchesOf($page, 0))->toBe(['update' => true, 'backup' => true]);
});

it('keeps the choice for a kind the stack stops raising, and applies it again when the stack raises it again', function (): void {
    $loft = aStackThatMayTell('a', 'The loft');
    $choosing = theChoicesForEachStack();
    $choosing->switchOff($loft->id(), KindOfNotification::Member);

    $without = theNotificationPage([$loft], AStackThatSaysWhatItTells::with(WhatTheOperatorIsTold::byPreset('quiet', 'Only', SetApart::of(), KindsOfNotification::these(KindOfNotification::Problem))), $choosing);
    $with = theNotificationPage([$loft], AStackThatSaysWhatItTells::with(WhatTheOperatorIsTold::byPreset('quiet', 'Only', SetApart::of(), KindsOfNotification::these(KindOfNotification::Problem, KindOfNotification::Member))), $choosing);

    expect(theSwitchesOf($without, 0))->toBe(['problem' => true])
        ->and(theSwitchesOf($with, 0))->toBe(['problem' => true, 'member' => false]);
});

it('offers the floor under problems, slightest first, every problem told until one is chosen, and keeps a choice', function (): void {
    $loft = aStackThatMayTell('a', 'The loft');
    $choosing = theChoicesForEachStack();
    $page = theNotificationPage([$loft], AStackThatSaysWhatItTells::with(WhatTheOperatorIsTold::byPreset('quiet', 'Only', SetApart::of(), KindsOfNotification::these(KindOfNotification::Problem))), $choosing);
    $floor = $page->sections()[0]->floor;

    expect($floor?->said)->toBe('notifications.floor.advisory')
        ->and(array_map(static fn(AChoiceOfASettingAsShown $choice): array => [$choice->word, $choice->chosen], $floor->offered ?? []))->toBe([['advisory', true], ['warning', false], ['error', false], ['critical', false]]);

    $page->problemsFrom($loft->id()->stored(), 'error');

    expect($page->sections()[0]->floor?->said)->toBe('notifications.floor.error')
        ->and($choosing->forTheStack($loft->id())->floor())->toBe(Severity::Error);
});

it('offers no floor on a stack that raises no problems', function (): void {
    $page = theNotificationPage([aStackThatMayTell('a', 'The loft')], AStackThatSaysWhatItTells::with(WhatTheOperatorIsTold::byPreset('quiet', 'Only', SetApart::of(), KindsOfNotification::these(KindOfNotification::Update))));

    expect($page->sections()[0]->floor)->toBeNull()
        ->and(WhatTheDeviceWouldDraw::by($page)->said())->not->toContain(__('notifications.page.problems_from'));
});

it('draws the floor and leaves it unusable while problems are off, saying why', function (): void {
    $loft = aStackThatMayTell('a', 'The loft');
    $page = theNotificationPage([$loft], AStackThatSaysWhatItTells::with(WhatTheOperatorIsTold::byPreset('quiet', 'Only', SetApart::of(), KindsOfNotification::these(KindOfNotification::Problem))));
    $page->kindTurned($loft->id()->stored(), 'problem', on: false);
    $drawn = WhatTheDeviceWouldDraw::by($page);

    expect($drawn->said())->toContain(__('notifications.page.problems_are_off'))
        ->and($drawn->offersThatWait())->toContain(__('notifications.floor.error'))
        ->and($drawn->offersThatWait())->not->toContain(__('notifications.kind.problem'));
});

it('draws every choice and leaves each unusable while the phone\'s switch is off, saying to turn it on above', function (): void {
    $page = theNotificationPage([aStackThatMayTell('a', 'The loft')], AStackThatSaysWhatItTells::with(WhatTheOperatorIsTold::byPreset('quiet', 'Only', SetApart::of(), KindsOfNotification::these(KindOfNotification::Problem, KindOfNotification::Backup))), notifying: false);
    $drawn = WhatTheDeviceWouldDraw::by($page);

    expect($drawn->said())->toContain(__('notifications.page.turn_on_above'))
        ->and($drawn->offersThatWait())->toContain(__('notifications.kind.backup'))
        ->and($drawn->offersThatWait())->toContain(__('notifications.floor.warning'))
        ->and($drawn->offers())->toContain(__('settings.notifications_on'));
});

it('says why a stack could not be read, and what still applies there, with no switch', function (): void {
    $loft = aStackThatMayTell('a', 'The loft');
    $choosing = theChoicesForEachStack();
    $choosing->switchOff($loft->id(), KindOfNotification::Backup);
    $choosing->tellProblemsFrom($loft->id(), Severity::Critical);
    $page = theNotificationPage([$loft], AStackThatSaysWhatItTells::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)), $choosing);
    $drawn = WhatTheDeviceWouldDraw::by($page);

    expect($page->sections()[0]->kinds)->toBe([])
        ->and($drawn->said())->toContain(Catalogue::words()->for('notifications.page.not_read', ['reason' => Catalogue::words()->for(KindOfObstacle::StackDidNotAnswer->said())]))
        ->and($drawn->said())->toContain(Catalogue::words()->for('notifications.page.kept_off', ['kinds' => Catalogue::words()->for('notifications.kind.backup')]))
        ->and($drawn->said())->toContain(Catalogue::words()->for('notifications.page.kept_floor', ['floor' => Catalogue::words()->for('notifications.floor.critical')]))
        ->and($drawn->offers())->not->toContain(__('notifications.kind.backup'));
});

it('says a stack this phone is signed out of could not be read, and asks it nothing', function (): void {
    $loft = aStackThatMayTell('a', 'The loft');
    $telling = AStackThatSaysWhatItTells::with(WhatTheOperatorIsTold::byPreset('quiet', 'Only', SetApart::of(), KindsOfNotification::these(KindOfNotification::Problem)));
    $drawn = WhatTheDeviceWouldDraw::by(theNotificationPage([$loft], $telling, signedOut: $loft));

    expect($drawn->said())->toContain(Catalogue::words()->for('notifications.page.not_read', ['reason' => Catalogue::words()->for('connection.session_has_ended')]))
        ->and($telling->askings())->toBe(0);
});

it('ignores a choice for a kind, a severity or a stack it did not draw', function (): void {
    $loft = aStackThatMayTell('a', 'The loft');
    $choosing = theChoicesForEachStack();
    $page = theNotificationPage([$loft], AStackThatSaysWhatItTells::with(WhatTheOperatorIsTold::byPreset('quiet', 'Only', SetApart::of(), KindsOfNotification::these(KindOfNotification::Problem))), $choosing);

    $page->kindTurned($loft->id()->stored(), 'gossip', on: false);
    $page->problemsFrom($loft->id()->stored(), 'mild');
    $page->kindTurned(aStackThatMayTell('b', 'The shed')->id()->stored(), 'problem', on: false);

    expect(iterator_to_array($choosing->forTheStack($loft->id())->switchedOff(), preserve_keys: false))->toBe([])
        ->and($choosing->forTheStack($loft->id())->floor())->toBe(Severity::Advisory)
        ->and(iterator_to_array($choosing->forTheStack(aStackThatMayTell('b', 'The shed')->id())->switchedOff(), preserve_keys: false))->toBe([]);
});

it('says so where the phone holds no stack', function (): void {
    expect(WhatTheDeviceWouldDraw::by(theNotificationPage([], AStackThatSaysWhatItTells::eachAs(static fn(): WhatTheAlertsWere => aStackRaising())))->said())
        ->toContain(__('notifications.page.no_stacks'));
});

it('names each switch and chip for its stack, so a screen reader tells two sections apart', function (): void {
    $drawn = WhatTheDeviceWouldDraw::tree(theNotificationPage([aStackThatMayTell('a', 'The loft')], AStackThatSaysWhatItTells::with(WhatTheOperatorIsTold::byPreset('quiet', 'Only', SetApart::of(), KindsOfNotification::these(KindOfNotification::Problem)))));

    expect((string) json_encode($drawn))->toContain(Catalogue::words()->for('notifications.page.switch_for', ['stack' => 'The loft', 'kind' => Catalogue::words()->for('notifications.kind.problem')]))
        ->and((string) json_encode($drawn))->toContain(Catalogue::words()->for('notifications.page.floor_for', ['stack' => 'The loft', 'floor' => Catalogue::words()->for('notifications.floor.critical')]));
});

it('redraws a section it could not read with what is chosen since, and says the floor in force', function (): void {
    $went = HowTheReadingWent::theSessionEnded();
    $was = new HowWhatAStackMayTellReads()->unread('a', 'The loft', $went, WhatMayReachThePhone::everything());
    $again = new HowWhatAStackMayTellReads()->again($was, WhatMayReachThePhone::everything()->withOff(KindOfNotification::Update)->withFloor(Severity::Warning));

    expect($again->keptOff)->toBe(['notifications.kind.update'])
        ->and($again->floorSaid)->toBe('notifications.floor.warning')
        ->and($again->went)->toBe($went);
});
