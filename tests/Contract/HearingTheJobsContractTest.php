<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HearingTheJobs;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\WhatWasHeardFromAJob;
use Modules\Sdk\Api\JobLines;
use Modules\Sdk\Api\PinnedClients;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\AStreamStillOpen;
use Tests\Support\Fakes\AJobThatSays;
use Tests\Support\TheWordCarriedOut;
use Tests\Support\WhatTheContractAccepts;
use Tests\Support\WhereEachOpenResumed;

// The HearingTheJobs contract, run against the adapter and against the fake.
//
// Every screen test that follows a job hands its subject an `AJobThatSays`
// and never opens a stream, so a fake that answered in a way the adapter never
// does would let a screen pass against a stack that cannot exist.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** The job a screen follows, as the stack named it. */
const THE_JOB_FOLLOWED = 'copy-the-stack';

/** A stack a job runs on, reachable and pinned so the adapter will build a client. */
function aStackAJobRunsOn(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('j', Nonce::SHORTEST))),
        StackName::of('The attic'),
        Address::of('https://192.168.1.43:8443'),
        Fingerprint::of(str_repeat('c', Fingerprint::CHARACTERS)),
    );
}

/** One event on the stream, as the core frames it. */
function anEventTheJobSays(string $kind, string $data): string
{
    return sprintf("id: run-1\nevent: %s\ndata: %s\n\n", $kind, $data);
}

/** A start line, said by the job followed unless another or none is named. */
function aStartLineOfTheJob(mixed $said, ?string $saidBy = THE_JOB_FOLLOWED): string
{
    return anEventTheJobSays('start', json_encode(['api_version' => 1, 'kind' => 'start', 'data' => $said, 'job' => $saidBy], JSON_THROW_ON_ERROR));
}

/** A pull line, said by the job followed unless another or none is named. */
function aPullLineOfTheJob(mixed $said, ?string $saidBy = THE_JOB_FOLLOWED): string
{
    return anEventTheJobSays('pull', json_encode(['api_version' => 1, 'kind' => 'pull', 'data' => $said, 'job' => $saidBy], JSON_THROW_ON_ERROR));
}

/** A step line, said by the job followed. */
function aStepLineOfTheJob(mixed $said): string
{
    return anEventTheJobSays('step', json_encode(['api_version' => 1, 'kind' => 'step', 'data' => $said, 'job' => THE_JOB_FOLLOWED], JSON_THROW_ON_ERROR));
}

/**
 * A step a job says, as the stack sends it.
 *
 * @return array<string, string>
 */
function aStepAJobSays(string $said): array
{
    return ['step' => 'downloading', 'said' => $said, 'detail' => ''];
}

/**
 * Both ways of hearing the jobs, each set up to hear the same thing.
 *
 * @param  list<MockResponse>  $streams  what the far end answers each time the adapter opens the stream
 * @param  list<WhatWasHeardFromAJob>  $script  what the fake is scripted to answer, wake by wake
 * @return array<string, Closure(): HearingTheJobs>
 */
function everyWayOfHearingTheJobs(array $streams, array $script): array
{
    return [
        'the fake' => static fn(): HearingTheJobs => AJobThatSays::saying(...$script),
        'the adapter' => static function () use ($streams): HearingTheJobs {
            MockClient::destroyGlobal();
            MockClient::global($streams);

            return new JobLines(new PinnedClients());
        },
    ];
}

/** What one answer said, as one line, whichever arm it took. */
function whatWasHeardFromAJobAsAWord(WhatWasHeardFromAJob $heard): string
{
    return $heard->either(
        said: static fn(string ...$lines): TheWordCarriedOut => new TheWordCarriedOut(sprintf('said %s', implode(' | ', $lines))),
        nothingNew: static fn(): TheWordCarriedOut => new TheWordCarriedOut('nothing new'),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->value),
    )->said;
}

/**
 * What one way of hearing a job answers across as many wakes as asked.
 *
 * @return list<string>
 */
function whatWakesHearOfAJob(HearingTheJobs $hearing, int $wakes): array
{
    $heard = [];

    for ($wake = 0; $wake < $wakes; $wake++) {
        $heard[] = whatWasHeardFromAJobAsAWord($hearing->whatTheySaid(aStackAJobRunsOn(), Session::of('a-session-not-a-secret'), Job::named(THE_JOB_FOLLOWED))->from(Job::named(THE_JOB_FOLLOWED)));
    }

    return $heard;
}

it('hears every line the job said, start, pull and step alike, in the order they arrived, and then nothing new', function (): void {
    foreach (everyWayOfHearingTheJobs(
        [
            MockResponse::make(sprintf(
                '%s%s%s',
                aStartLineOfTheJob('Waiting for the database'),
                aPullLineOfTheJob('Pulling sonarr'),
                aStepLineOfTheJob(aStepAJobSays('Downloading Sintel')),
            )),
            MockResponse::make(''),
        ],
        [WhatWasHeardFromAJob::said('Waiting for the database', 'Pulling sonarr', 'Downloading Sintel')],
    ) as $which => $make) {
        expect(whatWakesHearOfAJob($make(), 2))->toBe(['said Waiting for the database | Pulling sonarr | Downloading Sintel', 'nothing new'], $which);
    }
});

it('takes no line another job said, or one no job said', function (): void {
    foreach (everyWayOfHearingTheJobs(
        [MockResponse::make(sprintf(
            '%s%s',
            aStartLineOfTheJob('Waiting for the database', 'start-another'),
            aPullLineOfTheJob('Pulling sonarr', null),
        ))],
        [],
    ) as $which => $make) {
        expect(whatWakesHearOfAJob($make(), 1))->toBe(['nothing new'], $which);
    }
});

it('cannot hear a line that is blank or not text, and says so as an answer it could not read', function (): void {
    foreach ([
        aStartLineOfTheJob('   '),
        aPullLineOfTheJob(['pulling' => 'sonarr']),
        aStepLineOfTheJob(['said' => 'Downloading Sintel']),
        anEventTheJobSays('pull', 'not json at all'),
        aStartLineOfTheJob('Waiting for the database', '  '),
    ] as $said) {
        foreach (everyWayOfHearingTheJobs(
            [MockResponse::make($said)],
            [WhatWasHeardFromAJob::met(Obstacle::of(KindOfObstacle::AnswerCouldNotBeRead))],
        ) as $which => $make) {
            expect(whatWakesHearOfAJob($make(), 1))->toBe([KindOfObstacle::AnswerCouldNotBeRead->value], $which);
        }
    }
});

it('tells a session the stack refused from a stack that could not be heard, while a job runs', function (): void {
    foreach ([
        [MockResponse::make('{"error":"no"}', 401), Obstacle::of(KindOfObstacle::CredentialWasRefused)],
        [MockResponse::make('{"error":"gone"}', 500), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
    ] as [$answered, $why]) {
        foreach (everyWayOfHearingTheJobs([$answered], [WhatWasHeardFromAJob::met($why)]) as $which => $make) {
            expect(whatWakesHearOfAJob($make(), 1))->toBe([$why->kind()->value], $which);
        }
    }
});

it('opens a job\'s stream once while it is held, and again after it ended, resuming where it left off', function (): void {
    MockClient::destroyGlobal();
    $stack = MockClient::global([
        MockResponse::make(AStreamStillOpen::after(aStartLineOfTheJob('Waiting for the database'))),
        MockResponse::make(aPullLineOfTheJob('Pulling sonarr')),
    ]);

    expect(whatWakesHearOfAJob(new JobLines(new PinnedClients()), 3))
        ->toBe(['said Waiting for the database', 'nothing new', 'said Pulling sonarr'])
        ->and(WhereEachOpenResumed::in($stack))->toBe([null, 'run-1']);
});

it('opens a job\'s stream after where it left off once let go of keeping the place, and from the start once let go of', function (): void {
    MockClient::destroyGlobal();
    $stack = MockClient::global([
        MockResponse::make(AStreamStillOpen::after(aStartLineOfTheJob('Waiting for the database'))),
        MockResponse::make(''),
        MockResponse::make(''),
    ]);
    $hearing = new JobLines(new PinnedClients());
    whatWakesHearOfAJob($hearing, 1);
    $hearing->letGoKeepingThePlace();
    whatWakesHearOfAJob($hearing, 1);
    $hearing->letGo();
    whatWakesHearOfAJob($hearing, 1);

    expect(WhereEachOpenResumed::in($stack))->toBe([null, 'run-1', null]);
});

it('hears nothing new once let go of', function (): void {
    foreach (everyWayOfHearingTheJobs([], []) as $which => $make) {
        expect(whatWasHeardFromAJobAsAWord($make()->letGo()->from(Job::named(THE_JOB_FOLLOWED))))->toBe('nothing new', $which);
    }
});

/** A job a screen follows beside the first, as the stack named it. */
const THE_OTHER_JOB_FOLLOWED = 'set-the-library-once-landed';

it('hears every job followed in the one reading, each its own lines, and nothing of a job not asked about', function (): void {
    foreach ([
        'the fake' => static fn(): HearingTheJobs => AJobThatSays::eachSaying([
            THE_JOB_FOLLOWED => [WhatWasHeardFromAJob::said('Pulling sonarr')],
            THE_OTHER_JOB_FOLLOWED => [WhatWasHeardFromAJob::said('Waiting on two downloads')],
        ]),
        'the adapter' => static function (): HearingTheJobs {
            MockClient::destroyGlobal();
            MockClient::global([MockResponse::make(sprintf(
                '%s%s%s',
                aPullLineOfTheJob('Pulling sonarr'),
                aStartLineOfTheJob('Waiting on two downloads', THE_OTHER_JOB_FOLLOWED),
                aStartLineOfTheJob('Not followed here', 'another-job'),
            ))]);

            return new JobLines(new PinnedClients());
        },
    ] as $which => $make) {
        $heard = $make()->whatTheySaid(aStackAJobRunsOn(), Session::of('a-session-not-a-secret'), Job::named(THE_JOB_FOLLOWED), Job::named(THE_OTHER_JOB_FOLLOWED));

        expect([
            whatWasHeardFromAJobAsAWord($heard->from(Job::named(THE_JOB_FOLLOWED))),
            whatWasHeardFromAJobAsAWord($heard->from(Job::named(THE_OTHER_JOB_FOLLOWED))),
            whatWasHeardFromAJobAsAWord($heard->from(Job::named('another-job'))),
        ])->toBe(['said Pulling sonarr', 'said Waiting on two downloads', 'nothing new'], $which);
    }
});

it('stands in for a stack with lines a job says that the contract would accept', function (): void {
    foreach ([
        'StartEnvelope' => ['api_version' => 1, 'kind' => 'start', 'data' => 'Waiting for the database', 'job' => THE_JOB_FOLLOWED],
        'PullEnvelope' => ['api_version' => 1, 'kind' => 'pull', 'data' => 'Pulling sonarr', 'job' => THE_JOB_FOLLOWED],
        'StepEnvelope' => ['api_version' => 1, 'kind' => 'step', 'data' => aStepAJobSays('Downloading Sintel'), 'job' => THE_JOB_FOLLOWED],
    ] as $envelope => $payload) {
        expect(WhatTheContractAccepts::complaintsAbout($envelope, $payload))
            ->toBe([], sprintf("The %s this suite stands in for a stack with is not one a stack would send.\n", $envelope));
    }
});
