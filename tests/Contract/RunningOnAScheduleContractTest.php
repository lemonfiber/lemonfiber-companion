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
use Modules\Kernel\Api\RunningOnASchedule;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheLastLines;
use Modules\Kernel\Api\TheScheduledServices;
use Modules\Sdk\Api\PinnedClients;
use Modules\Sdk\Api\Schedulers;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AStackThatSaysWhatRunsOnASchedule;
use Tests\Support\TheWordCarriedOut;
use Tests\Support\WhatTheContractAccepts;

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** When the runs in these tests started. */
const WHEN_THE_SCHEDULED_RUN_STARTED = 1_790_000_000;

/** The stack whose run-and-exit services are asked about. */
function aStackWithAScheduledService(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** What both implementations answer with: one not run yet, one that succeeded while another run goes, one failed with a code and one failed without. */
function theSameSchedule(): TheScheduledServices
{
    $started = Instant::atEpochSeconds(WHEN_THE_SCHEDULED_RUN_STARTED);
    $ended = Instant::atEpochSeconds(WHEN_THE_SCHEDULED_RUN_STARTED + 95);

    return TheScheduledServices::these(
        ARunAndExit::notRunYet(ServiceId::called('recyclarr'), 'Recyclarr', running: false, fired: false, skipped: 0),
        ARunAndExit::lastRan(ServiceId::called('configarr'), 'Configarr', HowItsLastRunWent::Succeeded, ItsLastRun::exited($started, $ended, 0, TheLastLines::these('synced')), running: true, fired: true, skipped: 2),
        ARunAndExit::lastRan(ServiceId::called('unpackerr'), 'Unpackerr', HowItsLastRunWent::Failed, ItsLastRun::exited($started, $ended, 3, TheLastLines::these('reading the queue', 'the key [withheld] was refused')), running: false, fired: true, skipped: 0),
        ARunAndExit::lastRan(ServiceId::called('cleanuparr'), 'Cleanuparr', HowItsLastRunWent::Failed, ItsLastRun::endedWithoutACode($started, $ended, TheLastLines::these()), running: false, fired: true, skipped: 0),
    );
}

/**
 * The payload a stack sends for those services, around a stack running nothing else.
 *
 * @param  list<mixed>|null     $scheduled
 * @return array<string, mixed>
 */
function whatAStackSaysOfItsSchedule(?array $scheduled = null, bool $listsThem = true): array
{
    $run = ['started' => WHEN_THE_SCHEDULED_RUN_STARTED, 'ended' => WHEN_THE_SCHEDULED_RUN_STARTED + 95];

    $data = [
        'condition' => 'active',
        'disturbs' => [
            'restarting' => ['bound' => 'bounded', 'seconds' => 30],
            'starting' => ['bound' => 'bounded', 'seconds' => 45],
            'stopping' => ['bound' => 'bounded', 'seconds' => 20],
            'stopping_after_downloads' => ['bound' => 'open-ended', 'until' => 'downloads'],
            'switching' => ['bound' => 'bounded', 'seconds' => 60],
            'fetching' => ['bound' => 'bounded', 'seconds' => 1800],
        ],
        'active_forms' => [],
        'filtered' => [],
        'forms' => [],
        'services' => [],
        'scheduled' => $scheduled ?? [
            ['id' => 'recyclarr', 'name' => 'Recyclarr', 'health' => 'not-run', 'running' => false, 'fired' => false, 'skipped' => 0],
            ['id' => 'configarr', 'name' => 'Configarr', 'health' => 'succeeded', 'running' => true, 'fired' => true, 'skipped' => 2, 'last' => [...$run, 'exit' => 0, 'log' => ['synced']]],
            ['id' => 'unpackerr', 'name' => 'Unpackerr', 'health' => 'failed', 'running' => false, 'fired' => true, 'skipped' => 0, 'last' => [...$run, 'exit' => 3, 'log' => ['reading the queue', 'the key [withheld] was refused']]],
            ['id' => 'cleanuparr', 'name' => 'Cleanuparr', 'health' => 'failed', 'running' => false, 'fired' => true, 'skipped' => 0, 'last' => [...$run, 'exit' => null, 'log' => []]],
        ],
        'undeclared' => [],
        'waiting' => [],
    ];

    if (! $listsThem) {
        unset($data['scheduled']);
    }

    return ['api_version' => 1, 'kind' => 'status', 'data' => $data];
}

/**
 * Both ways of asking, each set up to produce the same answer.
 *
 * @return array<string, Closure(): RunningOnASchedule>
 */
function everyWayOfAskingWhatRunsOnASchedule(MockResponse $answered, ?Obstacle $why = null): array
{
    return [
        'the fake' => static fn(): RunningOnASchedule => $why instanceof Obstacle
            ? AStackThatSaysWhatRunsOnASchedule::met($why)
            : AStackThatSaysWhatRunsOnASchedule::with(theSameSchedule()),
        'the adapter' => static function () use ($answered): RunningOnASchedule {
            MockClient::destroyGlobal();
            MockClient::global([$answered]);

            return new Schedulers(new PinnedClients());
        },
    ];
}

/** One run, folded to one line. */
function aScheduledRunSaid(ItsLastRun $run): string
{
    $lines = [];

    foreach ($run->log() as $line) {
        $lines[] = $line;
    }

    return sprintf(
        '%d-%d exit %s [%s]',
        $run->started()->epochSeconds(),
        $run->ended()->epochSeconds(),
        $run->exit(
            said: static fn(int $code): TheWordCarriedOut => new TheWordCarriedOut((string) $code),
            unsaid: static fn(): TheWordCarriedOut => new TheWordCarriedOut('none'),
        )->said,
        implode(' / ', $lines),
    );
}

/** Everything one way of asking comes away with, folded to text. */
function everythingTheScheduleSays(RunningOnASchedule $asking): string
{
    return $asking->scheduled(aStackWithAScheduledService(), Session::of('a-session-not-a-secret'))->either(
        said: static function (TheScheduledServices $services): TheWordCarriedOut {
            $said = [];

            foreach ($services as $service) {
                $said[] = sprintf(
                    '%s (%s) %s%s%s skipped %d last %s',
                    $service->id()->named(),
                    $service->name(),
                    $service->health()->value,
                    $service->isRunning() ? ' running' : '',
                    $service->isFired() ? ' fired' : '',
                    $service->skipped(),
                    $service->last(
                        ran: static fn(ItsLastRun $run): TheWordCarriedOut => new TheWordCarriedOut(aScheduledRunSaid($run)),
                        never: static fn(): TheWordCarriedOut => new TheWordCarriedOut('never'),
                    )->said,
                );
            }

            return new TheWordCarriedOut(implode(' | ', $said));
        },
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->value),
    )->said;
}

it('comes away with each run-and-exit service, its health, its last run, and whether one is going or fired', function (): void {
    $answered = MockResponse::make((string) json_encode(whatAStackSaysOfItsSchedule()));
    $said = [];

    foreach (everyWayOfAskingWhatRunsOnASchedule($answered) as $which => $make) {
        $said[$which] = everythingTheScheduleSays($make());
    }

    $run = sprintf('%d-%d', WHEN_THE_SCHEDULED_RUN_STARTED, WHEN_THE_SCHEDULED_RUN_STARTED + 95);

    expect($said['the adapter'])->toBe($said['the fake'])
        ->and($said['the fake'])->toBe(sprintf(
            'recyclarr (Recyclarr) not-run skipped 0 last never | configarr (Configarr) succeeded running fired skipped 2 last %s exit 0 [synced] | unpackerr (Unpackerr) failed fired skipped 0 last %s exit 3 [reading the queue / the key [withheld] was refused] | cleanuparr (Cleanuparr) failed fired skipped 0 last %s exit none []',
            $run,
            $run,
            $run,
        ));
});

it('comes away with nothing where nothing runs on a schedule', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(whatAStackSaysOfItsSchedule([])))]);

    expect(everythingTheScheduleSays(new Schedulers(new PinnedClients())))->toBe('');
});

it('tells a session that has ended from a stack that is not answering', function (): void {
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::of(KindOfObstacle::CredentialWasRefused)],
        [MockResponse::make('{"error":"gone"}', 500), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
        [MockResponse::make('not json at all'), Obstacle::of(KindOfObstacle::AnswerCouldNotBeRead)],
    ];

    foreach ($table as [$answered, $why]) {
        foreach (everyWayOfAskingWhatRunsOnASchedule($answered, $why) as $which => $make) {
            expect(everythingTheScheduleSays($make()))->toBe($why->kind()->value, sprintf('%s / %s', $which, $why->kind()->value));
        }
    }
});

it('refuses what it cannot read, rather than drawing a failed run as one that went well', function (array $scheduled): void {
    /** @var list<mixed> $scheduled */
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(whatAStackSaysOfItsSchedule($scheduled)))]);

    expect(everythingTheScheduleSays(new Schedulers(new PinnedClients())))->toBe(KindOfObstacle::AnswerCouldNotBeRead->value);
})->with([
    'a health it has no case for' => [[['id' => 'a', 'name' => 'A', 'health' => 'sulking', 'running' => false, 'fired' => true, 'skipped' => 0]]],
    'no health' => [[['id' => 'a', 'name' => 'A', 'running' => false, 'fired' => true, 'skipped' => 0]]],
    'no name' => [[['id' => 'a', 'health' => 'not-run', 'running' => false, 'fired' => true, 'skipped' => 0]]],
    'a blank id' => [[['id' => ' ', 'name' => 'A', 'health' => 'not-run', 'running' => false, 'fired' => true, 'skipped' => 0]]],
    'no word on whether it is running' => [[['id' => 'a', 'name' => 'A', 'health' => 'not-run', 'fired' => true, 'skipped' => 0]]],
    'no word on whether it is fired' => [[['id' => 'a', 'name' => 'A', 'health' => 'not-run', 'running' => false, 'skipped' => 0]]],
    'skipped below nothing' => [[['id' => 'a', 'name' => 'A', 'health' => 'not-run', 'running' => false, 'fired' => true, 'skipped' => -1]]],
    'a failure with no last run' => [[['id' => 'a', 'name' => 'A', 'health' => 'failed', 'running' => false, 'fired' => true, 'skipped' => 0]]],
    'a last run with no start' => [[['id' => 'a', 'name' => 'A', 'health' => 'failed', 'running' => false, 'fired' => true, 'skipped' => 0, 'last' => ['ended' => 2, 'log' => []]]]],
    'a last run that ended before it started' => [[['id' => 'a', 'name' => 'A', 'health' => 'failed', 'running' => false, 'fired' => true, 'skipped' => 0, 'last' => ['started' => 5, 'ended' => 4, 'log' => []]]]],
    'a start before the epoch' => [[['id' => 'a', 'name' => 'A', 'health' => 'failed', 'running' => false, 'fired' => true, 'skipped' => 0, 'last' => ['started' => -1, 'ended' => 4, 'log' => []]]]],
    'an exit that is not a number' => [[['id' => 'a', 'name' => 'A', 'health' => 'failed', 'running' => false, 'fired' => true, 'skipped' => 0, 'last' => ['started' => 1, 'ended' => 4, 'exit' => 'three', 'log' => []]]]],
    'no log' => [[['id' => 'a', 'name' => 'A', 'health' => 'failed', 'running' => false, 'fired' => true, 'skipped' => 0, 'last' => ['started' => 1, 'ended' => 4]]]],
    'a log line that is not text' => [[['id' => 'a', 'name' => 'A', 'health' => 'failed', 'running' => false, 'fired' => true, 'skipped' => 0, 'last' => ['started' => 1, 'ended' => 4, 'log' => [7]]]]],
    'a last run that is not an object' => [[['id' => 'a', 'name' => 'A', 'health' => 'failed', 'running' => false, 'fired' => true, 'skipped' => 0, 'last' => 'yesterday']]],
    'a service that is not an object' => [['configarr']],
]);

it('refuses a status with no run-and-exit services listed', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(whatAStackSaysOfItsSchedule(listsThem: false)))]);

    expect(everythingTheScheduleSays(new Schedulers(new PinnedClients())))->toBe(KindOfObstacle::AnswerCouldNotBeRead->value);
});

it('stands in for a stack with a payload the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('StatusEnvelope', whatAStackSaysOfItsSchedule()))
        ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n");
});
