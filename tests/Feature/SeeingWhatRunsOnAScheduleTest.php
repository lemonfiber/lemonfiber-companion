<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\ARunAndExit;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowItsLastRunWent;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\ItsLastRun;
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
