<?php

declare(strict_types=1);

use Lemonfiber\Native\Telling;
use Lemonfiber\Native\WhyNothingWasTold;
use Modules\Connection\Api\NotifyingOnlyWhenTurnedOn;
use Modules\Connection\Api\TurningNotificationsOn;
use Modules\Device\Api\NotifyingWithinACap;
use Modules\Device\Api\PlatformNotifier;
use Modules\Device\Internal\WhatWasToldLately;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AnAlert;
use Modules\Kernel\Api\AnAlertsName;
use Modules\Kernel\Api\Asked;
use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\DeviceAuth;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Notification;
use Modules\Kernel\Api\Notifier;
use Modules\Kernel\Api\Notifying;
use Modules\Kernel\Api\SecondsIn;
use Modules\Kernel\Api\Shown;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\WhatTheCoreDecided;
use Modules\Kernel\Api\WhereATapLeads;
use Modules\Kernel\Api\WhichWayItWent;
use Modules\Kernel\Api\WhyNothingIsShown;
use Native\Mobile\Testing\FakeBridge;
use Tests\Support\Catalogue;
use Tests\Support\Fakes\ADeviceThatKnowsYou;
use Tests\Support\Fakes\ANotificationCentreOnAHandset;
use Tests\Support\Fakes\ANotifierInMemory;
use Tests\Support\Fakes\ASealInMemory;
use Tests\Support\Fakes\ConnectionSettingsInMemory;
use Tests\Support\Fakes\FrozenClock;

// The Notifier contract, run against the adapter and against the fake.
//
// `G2`'s shape. Every other test that needs "the operator was told" will hand
// its subject an `ANotifierInMemory` and never see a notification centre, so a
// fake easier to satisfy than the platform would make the refusal green
// against a centre that always says yes — and the refusal is the one somebody
// who already declined depends on.
//
// What is asserted is only what both must promise. The adapter composes words
// from the catalogue and the fake records an arm, so "the title reads like
// this" belongs to the adapter's own assertions further down — a contract
// asserting it would either fail on the fake or be weakened to pass, and a
// weakened contract is how a fake drifts.

/** A stack this phone remembers as `$remembered` and calls `$called`. */
function aStackToldAbout(string $remembered, string $called): Stack
{
    return Stack::of(StackId::rememberedAs($remembered), StackName::of($called), Address::of('https://192.168.1.42:8443'), Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)));
}

/**
 * Which arm answered, as a word.
 *
 * Named for this file rather than `fold`: the root suites share one namespace,
 * and two functions of the same name are a fatal the moment both load (`G10`).
 */
function whatBecameOfIt(Shown $shown): string
{
    return $shown->either(
        delivered: fn(): Code => Code::of('delivered'),
        withheld: fn(WhyNothingIsShown $why): Code => Code::of($why->name),
    )->shown();
}

function somethingWorthSaying(): Notification
{
    return Notification::fromTheCore(aStackToldAbout('the-loft', 'The loft'), WhatTheCoreDecided::toSay('backup.finished'), WhereATapLeads::to('/stacks/the-loft'));
}

/**
 * The adapter, over a centre scripted into the real bridge.
 *
 * `FakeBridge` is `nativephp/mobile`'s own seam: it intercepts
 * `nativephp_call()` in-process, so this arm of the contract runs the function
 * names from the manifest, the JSON out and the decoding of the answer —
 * the whole path — rather than something built to resemble it.
 *
 * Named for this file: the root suites share one namespace (`G10`).
 */
function overAHandset(ANotificationCentreOnAHandset $centre, ?DeviceAuth $lock = null): PlatformNotifier
{
    FakeBridge::disable();
    FakeBridge::enable()
        ->respondTo('Lemonfiber.Telling.Standing', $centre->standing(...))
        ->respondTo('Lemonfiber.Telling.Ask', $centre->ask(...))
        ->respondTo('Lemonfiber.Telling.Show', $centre->show(...));

    return new PlatformNotifier(new Telling(), Catalogue::words(), $lock ?? ADeviceThatKnowsYou::unlocked());
}

/** The moment the cases kept to a cap begin at. */
const NOTIFYING_AT = 1_790_000_000;

/** A notifier kept to a cap, with a memory of its own, over `$platform`. */
function keptToACap(Notifier $platform, FrozenClock $clock): NotifyingWithinACap
{
    return new NotifyingWithinACap($platform, $clock, new WhatWasToldLately());
}

/** A notifier kept to the operator's switch, set as `$notifying`, over `$within`. */
function keptToTheSwitch(Notifier $within, Notifying $notifying): NotifyingOnlyWhenTurnedOn
{
    $kept = ConnectionSettingsInMemory::empty();
    $seal = ASealInMemory::working();
    $clock = FrozenClock::at(Instant::atEpochSeconds(NOTIFYING_AT));
    $switch = new TurningNotificationsOn($seal, $kept, ANotifierInMemory::allowed(), $clock);

    $notifying === Notifying::On ? $switch->turnOn() : $switch->turnOff();

    return new NotifyingOnlyWhenTurnedOn($within, $seal, $kept, $clock);
}

/** One alert of a stack's, named by its recurrence. */
function anAlertOf(string $stack, int $recurrence): Notification
{
    return Notification::ofAnAlert(
        aStackToldAbout($stack, $stack),
        AnAlert::raised(WhatTheCoreDecided::toSay('disk.full'), WhichWayItWent::Onset, AnAlertsName::of(sprintf('disk.full#%d', $recurrence))),
        WhereATapLeads::to(sprintf('/stacks/%s', $stack)),
    );
}

/**
 * Every implementation of the port, in each of the three standings.
 *
 * A plain function rather than a Pest dataset, matching the other contract
 * suites: a dataset whose value is a closure is resolved by Pest and handed back
 * as one argument, so a pair returns as an array where the test wanted two
 * parameters.
 *
 * Three standings rather than two. `NotYet` and `Declined` are the same to a
 * caller asking whether it may show something and opposite to one deciding
 * whether to ask, which is the distinction this whole change is about — a suite
 * that tested "allowed" and "not allowed" could not see it.
 *
 * @return array<string, Closure(): Notifier>
 */
function everyNotifier(Asked $standing): array
{
    return [
        'the fake' => fn(): Notifier => match ($standing) {
            Asked::Granted => ANotifierInMemory::allowed(),
            Asked::Declined => ANotifierInMemory::refused(),
            Asked::NotYet => ANotifierInMemory::unasked(),
        },
        'the adapter' => fn(): Notifier => overAHandset(match ($standing) {
            Asked::Granted => ANotificationCentreOnAHandset::allowed(),
            Asked::Declined => ANotificationCentreOnAHandset::refused(),
            Asked::NotYet => ANotificationCentreOnAHandset::unasked(),
        }),
        'the adapter, kept to a cap' => fn(): Notifier => keptToACap(overAHandset(match ($standing) {
            Asked::Granted => ANotificationCentreOnAHandset::allowed(),
            Asked::Declined => ANotificationCentreOnAHandset::refused(),
            Asked::NotYet => ANotificationCentreOnAHandset::unasked(),
        }), FrozenClock::at(Instant::atEpochSeconds(NOTIFYING_AT))),
        'the adapter, kept to the switch and turned on' => fn(): Notifier => keptToTheSwitch(overAHandset(match ($standing) {
            Asked::Granted => ANotificationCentreOnAHandset::allowed(),
            Asked::Declined => ANotificationCentreOnAHandset::refused(),
            Asked::NotYet => ANotificationCentreOnAHandset::unasked(),
        }), Notifying::On),
    ];
}

it('reads what the operator has already said', function (): void {
    foreach (Asked::cases() as $standing) {
        foreach (everyNotifier($standing) as $which => $make) {
            expect($make()->standing())->toBe($standing, $which);
        }
    }
});

it('asking is separate from reading, so reading never prompts', function (): void {
    // The defect this whole change is about, asserted rather than described.
    // `isPermitted()` asked in order to answer, and `show()` called it — so a
    // notification arriving re-prompted somebody who had already declined.
    //
    // Counted on the adapter, because the count is what is being promised:
    // not asking again is not a question about a return value, it is a question
    // about how many times somebody was interrupted.
    $centre = ANotificationCentreOnAHandset::refused();
    $notifier = overAHandset($centre);

    $notifier->standing();
    $notifier->standing();
    $notifier->show(somethingWorthSaying());
    $notifier->show(somethingWorthSaying());

    expect($centre->prompts())->toBe(0);
});

it('a declined permission is not asked for again', function (): void {
    foreach (everyNotifier(Asked::Declined) as $which => $make) {
        $notifier = $make();

        expect($notifier->ask())->toBe(Asked::Declined, $which)
            ->and($notifier->standing())->toBe(Asked::Declined, $which);
    }
});

it('the prompt is raised once, and not again once answered', function (): void {
    $centre = ANotificationCentreOnAHandset::unasked();
    $notifier = overAHandset($centre);

    expect($notifier->ask())->toBe(Asked::Granted)
        ->and($centre->prompts())->toBe(1);

    // The second ask finds a standing answer and returns it without asking.
    expect($notifier->ask())->toBe(Asked::Granted)
        ->and($centre->prompts())->toBe(1);
});

it('asks where nothing has been asked yet', function (): void {
    foreach (everyNotifier(Asked::NotYet) as $which => $make) {
        expect($make()->ask())->toBe(Asked::Granted, $which);
    }
});

it('shows nothing where the operator has not allowed it', function (): void {
    foreach ([Asked::Declined, Asked::NotYet] as $standing) {
        foreach (everyNotifier($standing) as $which => $make) {
            // Withheld rather than thrown, and the reason is one a screen can
            // act on: `mightBeWorthAsking()` separates somebody who has not been
            // asked from somebody who said no.
            expect(whatBecameOfIt($make()->show(somethingWorthSaying())))
                ->toBe(WhyNothingIsShown::NotificationsAreNotPermitted->name, $which);
        }
    }
});

it('shows something where the operator has allowed it', function (): void {
    foreach (everyNotifier(Asked::Granted) as $which => $make) {
        expect(whatBecameOfIt($make()->show(somethingWorthSaying())))->toBe('delivered', $which);
    }
});

it('shows the guarded form without complaint', function (): void {
    // Both arms have to survive both implementations. The locked form is the one
    // a fake is most likely to get wrong, because nothing about it looks
    // different from the outside.
    foreach (everyNotifier(Asked::Granted) as $which => $make) {
        expect(whatBecameOfIt($make()->show(somethingWorthSaying()->whileLocked())))
            ->toBe('delivered', $which);
    }
});

// --- what only the adapter promises -------------------------------------
//
// Below the contract, because these are about composing words from the
// catalogue and the fake composes none. Asserting them above would force the
// fake to grow a translator to stay honest, which is the opposite of why it
// exists.

it('the locked wording names no stack', function (): void {
    $centre = ANotificationCentreOnAHandset::allowed();

    overAHandset($centre)->show(somethingWorthSaying()->whileLocked());

    $shown = $centre->shown();

    expect($shown)->toHaveCount(1)
        ->and($shown[0]['title'])->not->toContain('The loft')
        ->and($shown[0]['body'])->not->toContain('The loft')
        ->and($shown[0]['title'])->not->toContain('the-loft')
        ->and($shown[0]['body'])->not->toContain('the-loft')
        ->and($shown[0]['body'])->not->toContain('backup.finished');
});

it('names no stack while the lock stands, whatever the caller handed it', function (): void {
    // The notifier asks the device itself, so a caller that forgot to ask for
    // the guarded form cannot put a stack's words on a locked phone.
    $centre = ANotificationCentreOnAHandset::allowed();

    overAHandset($centre, ADeviceThatKnowsYou::refusing())->show(somethingWorthSaying());

    $shown = $centre->shown();

    expect($shown)->toHaveCount(1)
        ->and($shown[0]['title'])->not->toContain('The loft')
        ->and($shown[0]['body'])->not->toContain('The loft')
        ->and($shown[0]['title'])->not->toContain('the-loft')
        ->and($shown[0]['body'])->not->toContain('the-loft')
        ->and($shown[0]['body'])->not->toContain('backup.finished');
});

it('names the stack when the device is not locked', function (): void {
    $centre = ANotificationCentreOnAHandset::allowed();

    overAHandset($centre)->show(somethingWorthSaying());

    $shown = $centre->shown();

    expect($shown)->toHaveCount(1)
        ->and($shown[0]['title'])->toContain('The loft')
        ->and($shown[0]['title'])->not->toContain('the-loft')
        ->and($shown[0]['body'])->toContain('backup.finished');
});

it('puts nothing on a notification but the catalogue\'s words, the stack it is about and the core\'s code', function (): void {
    // Exactly the catalogue's lines, filled with those two and nothing else, so
    // a credential, a member's name or a requested title has nowhere to appear:
    // a notification carrying any of them would read differently from these.
    $centre = ANotificationCentreOnAHandset::allowed();
    $words = Catalogue::words();

    overAHandset($centre)->show(somethingWorthSaying());
    overAHandset($centre)->show(somethingWorthSaying()->whileLocked());

    expect(array_map(static fn(array $shown): array => [$shown['title'], $shown['body']], $centre->shown()))->toBe([
        [$words->for('notifications.plain.onset_title', ['stack' => 'The loft']), $words->for('notifications.plain.onset_body', ['code' => 'backup.finished'])],
        [$words->for('notifications.guarded.onset_title'), $words->for('notifications.guarded.onset_body')],
    ]);
});

it('tells a device that would not show it from one that was not allowed to', function (): void {
    // The distinction a boolean could not carry, and the reason this whole
    // capability is ours. A channel the operator switched off and a platform
    // that declined outright reach a screen as two different sentences, and the
    // plugin this replaced reported both — and a missing permission — as the
    // same `false`.
    foreach ([
        [WhyNothingWasTold::NotPermitted, WhyNothingIsShown::NotificationsAreNotPermitted],
        [WhyNothingWasTold::NoSuchChannel, WhyNothingIsShown::NotificationsAreNotPermitted],
        [WhyNothingWasTold::TheTimeHasPassed, WhyNothingIsShown::TheDeviceWouldNotShowIt],
        [WhyNothingWasTold::NoSuchRepeat, WhyNothingIsShown::TheDeviceWouldNotShowIt],
        [WhyNothingWasTold::TheDeviceRefused, WhyNothingIsShown::TheDeviceWouldNotShowIt],
    ] as [$said, $means]) {
        $notifier = overAHandset(ANotificationCentreOnAHandset::allowedButRefusing($said));

        expect(whatBecameOfIt($notifier->show(somethingWorthSaying())))->toBe($means->name, $said->value);
    }
});

it('keys a repeat of the same code onto the same notification', function (): void {
    // Both platforms replace a notification whose id they have seen. Two alerts
    // about the same thing should update one entry rather than stack two, and
    // an id built from anything varying — a timestamp, a counter — would
    // quietly give the operator a pile.
    $centre = ANotificationCentreOnAHandset::allowed();
    $notifier = overAHandset($centre);

    $notifier->show(somethingWorthSaying());
    $notifier->show(somethingWorthSaying());

    $shown = $centre->shown();

    expect($shown)->toHaveCount(2)
        ->and($shown[0]['id'])->toBe($shown[1]['id']);
});

it('files an alert under its name, so its resolution replaces its onset in the resolution\'s words', function (): void {
    $centre = ANotificationCentreOnAHandset::allowed();
    $notifier = overAHandset($centre);
    $words = Catalogue::words();
    $about = aStackToldAbout('the-loft', 'The loft');

    $notifier->show(Notification::ofAnAlert($about, AnAlert::raised(WhatTheCoreDecided::toSay('disk.full'), WhichWayItWent::Onset, AnAlertsName::of('disk.full#3')), WhereATapLeads::to('/stacks/the-loft')));
    $notifier->show(Notification::ofAnAlert($about, AnAlert::raised(WhatTheCoreDecided::toSay('disk.full'), WhichWayItWent::Resolved, AnAlertsName::of('disk.full#3')), WhereATapLeads::to('/stacks/the-loft')));
    $notifier->show(Notification::ofAnAlert($about, AnAlert::raised(WhatTheCoreDecided::toSay('disk.full'), WhichWayItWent::Onset, AnAlertsName::of('disk.full#4')), WhereATapLeads::to('/stacks/the-loft')));

    $shown = $centre->shown();

    expect($shown[0]['id'])->toBe($shown[1]['id'])
        ->and($shown[2]['id'])->not->toBe($shown[0]['id'])
        ->and([$shown[1]['title'], $shown[1]['body']])->toBe([
            $words->for('notifications.plain.resolved_title', ['stack' => 'The loft']),
            $words->for('notifications.plain.resolved_body', ['code' => 'disk.full']),
        ]);
});

it('files an alert the core gave no name under its kind, and keeps the resolution\'s words while locked', function (): void {
    $centre = ANotificationCentreOnAHandset::allowed();
    $words = Catalogue::words();
    $resolved = Notification::ofAnAlert(aStackToldAbout('the-loft', 'The loft'), AnAlert::unnamed(WhatTheCoreDecided::toSay('disk.full'), WhichWayItWent::Resolved), WhereATapLeads::to('/stacks/the-loft'));

    overAHandset($centre)->show($resolved->whileLocked());
    overAHandset($centre)->show(Notification::fromTheCore(aStackToldAbout('the-loft', 'The loft'), WhatTheCoreDecided::toSay('disk.full'), WhereATapLeads::to('/stacks/the-loft')));

    $shown = $centre->shown();

    expect($shown[0]['id'])->toBe($shown[1]['id'])
        ->and([$shown[0]['title'], $shown[0]['body']])->toBe([$words->for('notifications.guarded.resolved_title'), $words->for('notifications.guarded.resolved_body')]);
});

it('files the same alert name from two stacks apart, so one stack\'s resolution never clears another\'s onset', function (): void {
    $centre = ANotificationCentreOnAHandset::allowed();
    $notifier = overAHandset($centre);
    $loft = aStackToldAbout('the-loft', 'The loft');
    $shed = aStackToldAbout('the-shed', 'The shed');

    $notifier->show(Notification::ofAnAlert($loft, AnAlert::raised(WhatTheCoreDecided::toSay('disk.full'), WhichWayItWent::Onset, AnAlertsName::of('disk.full#3')), WhereATapLeads::to('/stacks/the-loft')));
    $notifier->show(Notification::ofAnAlert($shed, AnAlert::raised(WhatTheCoreDecided::toSay('disk.full'), WhichWayItWent::Resolved, AnAlertsName::of('disk.full#3')), WhereATapLeads::to('/stacks/the-loft')));
    $notifier->show(Notification::ofAnAlert($shed, AnAlert::unnamed(WhatTheCoreDecided::toSay('disk.full'), WhichWayItWent::Onset), WhereATapLeads::to('/stacks/the-loft'))->whileLocked());
    $notifier->show(Notification::fromTheCore($loft, WhatTheCoreDecided::toSay('disk.full'), WhereATapLeads::to('/stacks/the-loft')));

    $ids = array_column($centre->shown(), 'id');

    expect($ids[1])->not->toBe($ids[0])
        ->and($ids[2])->not->toBe($ids[3]);
});

it('files a notification under a key that names neither its stack nor its alert', function (): void {
    $centre = ANotificationCentreOnAHandset::allowed();

    overAHandset($centre)->show(Notification::ofAnAlert(aStackToldAbout('the-loft', 'The loft'), AnAlert::raised(WhatTheCoreDecided::toSay('disk.full'), WhichWayItWent::Onset, AnAlertsName::of('disk.full#3')), WhereATapLeads::to('/stacks/the-loft')));

    $id = $centre->shown()[0]['id'];

    expect($id)->not->toContain('the-loft')
        ->and($id)->not->toContain('The loft')
        ->and($id)->not->toContain('disk.full');
});

it('shows a stack\'s first notifications within a minute as they come, and every one after as one that replaces itself', function (): void {
    $centre = ANotificationCentreOnAHandset::allowed();
    $notifier = keptToACap(overAHandset($centre), FrozenClock::at(Instant::atEpochSeconds(NOTIFYING_AT)));

    for ($recurrence = 1; $recurrence <= NotifyingWithinACap::AT_MOST + 3; $recurrence++) {
        $notifier->show(anAlertOf('the-loft', $recurrence));
    }

    $ids = array_column($centre->shown(), 'id');
    $beyond = array_slice($ids, NotifyingWithinACap::AT_MOST);

    expect(array_unique(array_slice($ids, 0, NotifyingWithinACap::AT_MOST)))->toHaveCount(NotifyingWithinACap::AT_MOST)
        ->and(array_unique($beyond))->toBe([$beyond[0]])
        ->and(array_intersect($beyond, array_slice($ids, 0, NotifyingWithinACap::AT_MOST)))->toBe([])
        ->and(array_column($centre->shown(), 'title')[NotifyingWithinACap::AT_MOST])->toBe(Catalogue::words()->for('notifications.plain.more_title', ['stack' => 'the-loft']))
        ->and(array_column($centre->shown(), 'opens')[NotifyingWithinACap::AT_MOST])->toBe('lemonfiber://stacks/the-loft');
});

it('says a stack has more to tell without naming it while the lock stands', function (): void {
    $centre = ANotificationCentreOnAHandset::allowed();
    $notifier = keptToACap(overAHandset($centre, ADeviceThatKnowsYou::refusing()), FrozenClock::at(Instant::atEpochSeconds(NOTIFYING_AT)));

    for ($recurrence = 1; $recurrence <= NotifyingWithinACap::AT_MOST + 1; $recurrence++) {
        $notifier->show(anAlertOf('the-loft', $recurrence));
    }

    $summary = $centre->shown()[NotifyingWithinACap::AT_MOST];

    expect([$summary['title'], $summary['body']])->toBe([Catalogue::words()->for('notifications.guarded.more_title'), Catalogue::words()->for('notifications.guarded.more_body')])
        ->and($summary['title'])->not->toContain('the-loft');
});

it('counts each stack apart, so one stack\'s flood never touches another\'s notifications', function (): void {
    $centre = ANotificationCentreOnAHandset::allowed();
    $notifier = keptToACap(overAHandset($centre), FrozenClock::at(Instant::atEpochSeconds(NOTIFYING_AT)));

    for ($recurrence = 1; $recurrence <= NotifyingWithinACap::AT_MOST + 2; $recurrence++) {
        $notifier->show(anAlertOf('the-loft', $recurrence));
    }

    $notifier->show(anAlertOf('the-shed', 1));

    $ids = array_column($centre->shown(), 'id');
    $titles = array_column($centre->shown(), 'title');
    $shed = array_pop($ids);

    expect($shed)->not->toBeIn($ids)
        ->and(array_pop($titles))->toBe(Catalogue::words()->for('notifications.plain.onset_title', ['stack' => 'the-shed']));
});

it('shows a stack\'s notifications as they come again once a minute has passed', function (): void {
    $centre = ANotificationCentreOnAHandset::allowed();
    $clock = FrozenClock::at(Instant::atEpochSeconds(NOTIFYING_AT));
    $notifier = keptToACap(overAHandset($centre), $clock);

    for ($recurrence = 1; $recurrence <= NotifyingWithinACap::AT_MOST + 1; $recurrence++) {
        $notifier->show(anAlertOf('the-loft', $recurrence));
    }

    $clock->moveTo(Instant::atEpochSeconds(NOTIFYING_AT + SecondsIn::AMinute->value));
    $notifier->show(anAlertOf('the-loft', 99));

    $titles = array_column($centre->shown(), 'title');

    expect(array_pop($titles))->toBe(Catalogue::words()->for('notifications.plain.onset_title', ['stack' => 'the-loft']));
});

it('shows nothing while the operator has notifications turned off, and says that is why', function (): void {
    $centre = ANotificationCentreOnAHandset::allowed();

    expect(whatBecameOfIt(keptToTheSwitch(overAHandset($centre), Notifying::Off)->show(somethingWorthSaying())))->toBe(WhyNothingIsShown::TheOperatorTurnedThemOff->name)
        ->and($centre->shown())->toBe([]);
});
