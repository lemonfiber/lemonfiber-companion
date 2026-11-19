<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\ARunAndExit;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowItsLastRunWent;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\ItsLastRun;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheLastLines;
use Modules\Kernel\Api\TheScheduledServices;
use Modules\Kernel\Api\WhatBecameOfTheWork;
use Modules\Kernel\Api\WhatRunningItNowCameTo;
use Modules\Kernel\Api\WhatTheRefusalNamed;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\TheScheduledRuns;
use Modules\Stacks\Api\AStacksScreen;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\AroundThePhone;
use Tests\Support\Catalogue;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AStackThatSaysWhatRunsOnASchedule;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\WhatTheDeviceWouldDraw;

/** When the screen is drawn, in these tests. */
const WHEN_THE_SCHEDULE_IS_READ = 1_790_000_000;

/** The machine this screen is about. */
function theScheduledStack(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** A run that ended two hours ago after this many seconds, with this exit and these lines. */
function aRunThatEnded(int $took, ?int $exit, string ...$lines): ItsLastRun
{
    $ended = Instant::atEpochSeconds(WHEN_THE_SCHEDULE_IS_READ - 7_200);
    $started = Instant::atEpochSeconds(WHEN_THE_SCHEDULE_IS_READ - 7_200 - $took);

    return $exit === null
        ? ItsLastRun::endedWithoutACode($started, $ended, TheLastLines::these(...$lines))
        : ItsLastRun::exited($started, $ended, $exit, TheLastLines::these(...$lines));
}

/** The screen, signed in unless said. */
function theScheduleScreen(AStackThatSaysWhatRunsOnASchedule $asking, bool $signedIn = true): TheScheduledRuns
{
    $stack = theScheduledStack();
    $keychain = AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    }

    $screen = new TheScheduledRuns(
        $asking,
        $keychain,
        FrozenClock::at(Instant::atEpochSeconds(WHEN_THE_SCHEDULE_IS_READ)),
        AroundThePhone::holding(StacksInMemory::holding($stack)),
        new AppsSettingsThatOpen(),
        AroundThePhone::listening(),
    );
    $screen->setParams(['stack' => $stack->id()->stored()]);

    return $screen;
}

/**
 * A line of the schedule screen, as the catalogue says it.
 *
 * @param array<string, string> $with
 */
function aScheduleLine(string $key, array $with = []): string
{
    return Catalogue::words()->for($key, $with);
}

/**
 * What the screen draws for these services.
 *
 * @return list<string>
 */
function whatTheScheduleScreenSays(ARunAndExit ...$services): array
{
    return WhatTheDeviceWouldDraw::by(theScheduleScreen(AStackThatSaysWhatRunsOnASchedule::with(TheScheduledServices::these(...$services))))->said();
}

it('is served at its own path', function (): void {
    expect(data_get(NativeRouter::resolve(str_replace('{stack}', theScheduledStack()->id()->stored(), AStacksScreen::Scheduled->value)), 'class'))->toBe(TheScheduledRuns::class);
});

it('shows a failed run with its exit code and its last lines', function (): void {
    $said = whatTheScheduleScreenSays(ARunAndExit::lastRan(ServiceId::called('unpackerr'), 'Unpackerr', HowItsLastRunWent::Failed, aRunThatEnded(240, 3, 'reading the queue', 'the key [withheld] was refused'), running: false, fired: true, skipped: 0));

    expect($said)->toContain('Unpackerr')
        ->and($said)->toContain(aScheduleLine('scheduled.health.failed'))
        ->and($said)->toContain(aScheduleLine('scheduled.ran', ['ago' => trans_choice('health.ago.hours', 2)]))
        ->and($said)->toContain(trans_choice('scheduled.took.minutes', 4))
        ->and($said)->toContain(aScheduleLine('scheduled.exit.code', ['code' => '3']))
        ->and($said)->toContain(aScheduleLine('scheduled.log'))
        ->and($said)->toContain('reading the queue')
        ->and($said)->toContain('the key [withheld] was refused')
        ->and($said)->not->toContain(aScheduleLine('scheduled.no_log'))
        ->and($said)->not->toContain(aScheduleLine('scheduled.running'))
        ->and($said)->not->toContain(aScheduleLine('scheduled.not_fired'));
});

it('says a failed run ended without an exit code where the stack gives none', function (): void {
    $said = whatTheScheduleScreenSays(ARunAndExit::lastRan(ServiceId::called('cleanuparr'), 'Cleanuparr', HowItsLastRunWent::Failed, aRunThatEnded(30, null), running: false, fired: true, skipped: 0));

    expect($said)->toContain(aScheduleLine('scheduled.exit.none'))
        ->and($said)->toContain(trans_choice('scheduled.took.minutes', 0))
        ->and($said)->toContain(aScheduleLine('scheduled.log'))
        ->and($said)->toContain(aScheduleLine('scheduled.no_log'));
});

it('shows a run that succeeded without its code or its lines, and a run going now', function (): void {
    $said = whatTheScheduleScreenSays(ARunAndExit::lastRan(ServiceId::called('configarr'), 'Configarr', HowItsLastRunWent::Succeeded, aRunThatEnded(7_200, 0, 'synced'), running: true, fired: true, skipped: 2));

    expect($said)->toContain(aScheduleLine('scheduled.health.succeeded'))
        ->and($said)->toContain(trans_choice('scheduled.took.hours', 2))
        ->and($said)->toContain(aScheduleLine('scheduled.running'))
        ->and($said)->toContain(trans_choice('scheduled.skipped', 2))
        ->and($said)->not->toContain(aScheduleLine('scheduled.exit.code', ['code' => '0']))
        ->and($said)->not->toContain(aScheduleLine('scheduled.log'))
        ->and($said)->not->toContain('synced');
});

it('shows a service not run yet as neither a success nor a failure, and says where its times are not fired', function (): void {
    $said = whatTheScheduleScreenSays(ARunAndExit::notRunYet(ServiceId::called('recyclarr'), 'Recyclarr', running: false, fired: false, skipped: 0));

    expect($said)->toContain(aScheduleLine('scheduled.health.not-run'))
        ->and($said)->toContain(aScheduleLine('scheduled.not_fired'))
        ->and($said)->not->toContain(aScheduleLine('scheduled.health.succeeded'))
        ->and($said)->not->toContain(aScheduleLine('scheduled.health.failed'))
        ->and(array_filter($said, static fn(string $line): bool => str_starts_with($line, aScheduleLine('scheduled.ran', ['ago' => '']))))->toBe([])
        ->and(array_filter($said, static fn(string $line): bool => str_contains($line, trans_choice('scheduled.skipped', 1))))->toBe([]);
});

it('says so where nothing on the stack runs on a schedule', function (): void {
    expect(whatTheScheduleScreenSays())->toContain(aScheduleLine('scheduled.nothing'))
        ->and(whatTheScheduleScreenSays())->toContain(aScheduleLine('scheduled.between_runs'));
});

it('asks for a session rather than the schedule where this device holds none', function (): void {
    $asking = AStackThatSaysWhatRunsOnASchedule::with(TheScheduledServices::these());
    $screen = theScheduleScreen($asking, signedIn: false);

    expect($screen->answer()->went->isSignedIn)->toBeFalse()
        ->and($asking->askings())->toBe(0);
});

it('says what stood in the way where the stack could not be asked', function (): void {
    $screen = theScheduleScreen(AStackThatSaysWhatRunsOnASchedule::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)));

    expect($screen->answer()->went->cameBack())->toBeFalse()
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(aScheduleLine(KindOfObstacle::StackDidNotAnswer->said()));
});

it('asks once a frame, and again when asked again', function (): void {
    $asking = AStackThatSaysWhatRunsOnASchedule::with(TheScheduledServices::these());
    $screen = theScheduleScreen($asking);
    WhatTheDeviceWouldDraw::by($screen);
    $screen->answer();

    expect($asking->askings())->toBe(1);

    $screen->again();
    WhatTheDeviceWouldDraw::by($screen);

    expect($asking->askings())->toBe(2);
});

it('is reached from the operator\'s menu', function (): void {
    expect(theScheduleScreen(AStackThatSaysWhatRunsOnASchedule::with(TheScheduledServices::these()))->goes()->to(AStacksScreen::Scheduled))
        ->toBe(sprintf('/stacks/%s/scheduled', theScheduledStack()->id()->stored()));
});

/** The two services the stack lists: one that last succeeded and one that last failed. */
function twoScheduledServices(): TheScheduledServices
{
    return TheScheduledServices::these(
        ARunAndExit::lastRan(ServiceId::called('configarr'), 'Configarr', HowItsLastRunWent::Succeeded, aRunThatEnded(60, 0, 'synced'), running: false, fired: true, skipped: 0),
        ARunAndExit::lastRan(ServiceId::called('unpackerr'), 'Unpackerr', HowItsLastRunWent::Failed, aRunThatEnded(240, 3, 'the key [withheld] was refused'), running: false, fired: true, skipped: 0),
    );
}

/**
 * A stack listing those services that takes a run at once on and says `$became` of it.
 *
 * @param WhatBecameOfTheWork<WhatRunningItNowCameTo> $became
 */
function aStackThatRunsOneNow(WhatBecameOfTheWork $became): AStackThatSaysWhatRunsOnASchedule
{
    return AStackThatSaysWhatRunsOnASchedule::with(twoScheduledServices())->running($became);
}

/** The schedule screen, asked to run Unpackerr, given the yes, and looking once while it runs. */
function unpackerrRunNow(AStackThatSaysWhatRunsOnASchedule $asking): TheScheduledRuns
{
    $screen = theScheduleScreen($asking);
    $screen->askToRun('unpackerr');
    $screen->runItNow();
    $screen->whileItRunsNow();

    return $screen;
}

/** A run of Unpackerr that just ended after a minute, with this exit and these lines. */
function aRunJustNow(?int $exit, string ...$lines): ItsLastRun
{
    $started = Instant::atEpochSeconds(WHEN_THE_SCHEDULE_IS_READ - 60);
    $ended = Instant::atEpochSeconds(WHEN_THE_SCHEDULE_IS_READ);

    return $exit === null
        ? ItsLastRun::endedWithoutACode($started, $ended, TheLastLines::these(...$lines))
        : ItsLastRun::exited($started, $ended, $exit, TheLastLines::these(...$lines));
}

it('offers each service a run now, named for it, sending nothing before the yes', function (): void {
    $asking = aStackThatRunsOneNow(WhatBecameOfTheWork::noOutcome());
    $screen = theScheduleScreen($asking);
    $offers = WhatTheDeviceWouldDraw::by($screen)->offers();
    $tree = (string) json_encode(WhatTheDeviceWouldDraw::tree($screen));

    expect(array_count_values($offers)[aScheduleLine('scheduled.now.run')] ?? 0)->toBe(2)
        ->and($tree)->toContain(aScheduleLine('scheduled.now.run_named', ['service' => 'Configarr']))
        ->and($tree)->toContain(aScheduleLine('scheduled.now.run_named', ['service' => 'Unpackerr']))
        ->and($asking->runsAsked())->toBe([]);
});

it('asks one yes naming the service, and sends nothing until it is given', function (): void {
    $asking = aStackThatRunsOneNow(WhatBecameOfTheWork::noOutcome());
    $screen = theScheduleScreen($asking);
    $screen->askToRun('unpackerr');
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($drawn->said())->toContain(aScheduleLine('scheduled.now.about_to', ['service' => 'Unpackerr']))
        ->and($drawn->said())->toContain(aScheduleLine('scheduled.now.whatever_is_due'))
        ->and($drawn->offers())->toContain(aScheduleLine('scheduled.now.run'))
        ->and($drawn->offers())->toContain(aScheduleLine('scheduled.now.leave_it'))
        ->and($drawn->said())->not->toContain('Configarr')
        ->and($asking->runsAsked())->toBe([]);
});

it('sends nothing where the yes is left, or was never asked for', function (): void {
    $asking = aStackThatRunsOneNow(WhatBecameOfTheWork::noOutcome());
    $screen = theScheduleScreen($asking);
    $screen->runItNow();
    $screen->askToRun('unpackerr');
    $screen->leaveTheRun();
    $screen->runItNow();

    expect($asking->runsAsked())->toBe([])
        ->and($screen->toRun)->toBeNull()
        ->and($screen->runNamed)->toBe('')
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(aScheduleLine('scheduled.title'));
});

it('asks nothing about a service the reading does not list', function (): void {
    $asking = aStackThatRunsOneNow(WhatBecameOfTheWork::noOutcome());
    $screen = theScheduleScreen($asking);
    $screen->askToRun('recyclarr');
    $screen->runItNow();

    expect($screen->toRun)->toBeNull()
        ->and($asking->runsAsked())->toBe([]);
});

it('sends the run for that service alone, and shows it running without changing the service before the stack answers', function (): void {
    $asking = AStackThatSaysWhatRunsOnASchedule::with(twoScheduledServices())->running(WhatBecameOfTheWork::underway(Job::named(AStackThatSaysWhatRunsOnASchedule::THE_RUN)));
    $screen = unpackerrRunNow($asking);
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($asking->runsAsked())->toBe(['unpackerr'])
        ->and($screen->awaitsAnOutcome())->toBeTrue()
        ->and($drawn->said())->toContain(aScheduleLine('scheduled.now.running', ['service' => 'Unpackerr']))
        ->and($drawn->said())->toContain(aScheduleLine('scheduled.health.failed'))
        ->and($drawn->said())->not->toContain(aScheduleLine('scheduled.now.ran', ['service' => 'Unpackerr']))
        ->and($drawn->offers())->not->toContain(aScheduleLine('scheduled.now.run_named', ['service' => 'Configarr']))
        ->and($asking->askings())->toBe(1);
});

it('follows the run by its handle, and shows a failed one with its exit code and its last lines, reading the schedule again', function (): void {
    $asking = aStackThatRunsOneNow(WhatBecameOfTheWork::answered(WhatRunningItNowCameTo::ran(aRunJustNow(4, 'reading the queue', 'gave up'))));
    $screen = unpackerrRunNow($asking);
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($asking->followed())->toBe([AStackThatSaysWhatRunsOnASchedule::THE_RUN])
        ->and($screen->awaitsAnOutcome())->toBeFalse()
        ->and($screen->runFollowed)->toBeNull()
        ->and($drawn->said())->toContain(aScheduleLine('scheduled.now.ran', ['service' => 'Unpackerr']))
        ->and($drawn->said())->toContain(aScheduleLine('scheduled.exit.code', ['code' => '4']))
        ->and($drawn->said())->toContain('reading the queue')
        ->and($drawn->said())->toContain('gave up')
        ->and($drawn->said())->toContain(trans_choice('scheduled.took.minutes', 1))
        ->and($screen->ranNow?->tone)->toBe('trouble')
        ->and($asking->askings())->toBe(2);
});

it('shows a run that exited zero as succeeded, without its code or its lines', function (): void {
    $screen = unpackerrRunNow(aStackThatRunsOneNow(WhatBecameOfTheWork::answered(WhatRunningItNowCameTo::ran(aRunJustNow(0, 'all queued')))));
    $said = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($said)->toContain(aScheduleLine('scheduled.now.ran', ['service' => 'Unpackerr']))
        ->and($said)->toContain(aScheduleLine('scheduled.health.succeeded'))
        ->and($said)->not->toContain(aScheduleLine('scheduled.exit.code', ['code' => '0']))
        ->and($said)->not->toContain('all queued')
        ->and($screen->ranNow?->tone)->toBe('fine')
        ->and($screen->ranNow?->held)->toBe('');
});

it('shows a run that ended without a code as failed', function (): void {
    $screen = unpackerrRunNow(aStackThatRunsOneNow(WhatBecameOfTheWork::answered(WhatRunningItNowCameTo::ran(aRunJustNow(null)))));

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(aScheduleLine('scheduled.exit.none'))
        ->and($screen->ranNow?->healthSaid)->toBe('scheduled.health.failed');
});

it('says where nothing ran, with the reason the stack gave', function (): void {
    $why = 'a run of it is still going, which accounts for this minute when it ends';
    $screen = unpackerrRunNow(aStackThatRunsOneNow(WhatBecameOfTheWork::answered(WhatRunningItNowCameTo::held($why))));
    $said = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($said)->toContain(aScheduleLine('scheduled.now.held', ['service' => 'Unpackerr']))
        ->and($said)->toContain($why)
        ->and($said)->not->toContain(aScheduleLine('scheduled.now.ran', ['service' => 'Unpackerr']))
        ->and($screen->ranNow?->tone)->toBe('attention')
        ->and($screen->ranNow?->healthSaid)->toBe('');

    $screen->leaveTheRun();

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->not->toContain($why)
        ->and($screen->ranNow)->toBeNull();
});

it('says what the stack refused, where the run was no outcome, and lets the answer go', function (): void {
    $refused = unpackerrRunNow(aStackThatRunsOneNow(WhatBecameOfTheWork::refused(ARefusalInItsWords::said('unpackerr is no run-and-exit service', '', WhatTheRefusalNamed::nothing(), 'List what is installed'))));
    $ended = unpackerrRunNow(aStackThatRunsOneNow(WhatBecameOfTheWork::noOutcome()));

    expect(WhatTheDeviceWouldDraw::by($refused)->said())->toContain(aScheduleLine('scheduled.now.refused', ['service' => 'Unpackerr']))
        ->and(WhatTheDeviceWouldDraw::by($refused)->said())->toContain('unpackerr is no run-and-exit service')
        ->and($refused->runFollowed)->toBeNull()
        ->and(WhatTheDeviceWouldDraw::by($ended)->said())->toContain(aScheduleLine('scheduled.now.no_outcome', ['service' => 'Unpackerr']));

    $ended->leaveTheRun();

    expect(WhatTheDeviceWouldDraw::by($ended)->said())->not->toContain(aScheduleLine('scheduled.now.no_outcome', ['service' => 'Unpackerr']));
});

it('says what stood in the way where the run could not be sent', function (): void {
    $asking = AStackThatSaysWhatRunsOnASchedule::with(twoScheduledServices())->answeringTheRun(WhatBecameOfTheWork::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)));
    $screen = unpackerrRunNow($asking);

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(aScheduleLine(KindOfObstacle::StackDidNotAnswer->said()))
        ->and($screen->awaitsAnOutcome())->toBeFalse();
});

it('looks again while the run goes, and lets the reading stand once it has ended', function (): void {
    $asking = AStackThatSaysWhatRunsOnASchedule::with(twoScheduledServices())->running(WhatBecameOfTheWork::underway(Job::named(AStackThatSaysWhatRunsOnASchedule::THE_RUN)));
    $screen = unpackerrRunNow($asking);
    $screen->whileItRunsNow();
    $screen->whileItRunsNow();

    expect($asking->followed())->toBe([AStackThatSaysWhatRunsOnASchedule::THE_RUN, AStackThatSaysWhatRunsOnASchedule::THE_RUN])
        ->and($screen->awaitsAnOutcome())->toBeTrue()
        ->and($screen->runFollowed)->toBe(AStackThatSaysWhatRunsOnASchedule::THE_RUN);

    $screen->askToRun('configarr');
    $screen->leaveTheRun();

    expect($screen->toRun)->toBeNull()
        ->and($screen->runNamed)->toBe('Unpackerr')
        ->and($screen->runFollowed)->toBe(AStackThatSaysWhatRunsOnASchedule::THE_RUN);
});

it('lets go of a run it can no longer follow, saying what stood in the way', function (): void {
    $asking = AStackThatSaysWhatRunsOnASchedule::with(twoScheduledServices())->running(WhatBecameOfTheWork::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)));
    $screen = unpackerrRunNow($asking);

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(aScheduleLine(KindOfObstacle::StackDidNotAnswer->said()))
        ->and($screen->runFollowed)->toBeNull()
        ->and($asking->followed())->toBe([AStackThatSaysWhatRunsOnASchedule::THE_RUN]);
});
