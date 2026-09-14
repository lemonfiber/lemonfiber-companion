<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AgreedTo;
use Modules\Kernel\Api\Daemon;
use Modules\Kernel\Api\Daemons;
use Modules\Kernel\Api\Disturbances;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Form;
use Modules\Kernel\Api\Forms;
use Modules\Kernel\Api\HowAServiceRuns;
use Modules\Kernel\Api\HowMuchItMatters;
use Modules\Kernel\Api\HowTheStackIsRunning;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\Supervising;
use Modules\Kernel\Api\WhatItTakesAway;
use Modules\Kernel\Api\WhatLeansOnIt;
use Modules\Kernel\Api\WhatToDoWithIt;
use Modules\Sdk\Api\PinnedClients;
use Modules\Sdk\Api\Supervisors;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AStackThatSupervises;
use Tests\Support\Fakes\SequencedEntropy;
use Tests\Support\WhatTheContractAccepts;

/** What a stack reports its verbs cost, as this suite's stacks report them. */
function whatTheSupervisedVerbsCost(): Disturbances
{
    // The same numbers {@see howLongEachVerbTakesIt()} puts on the wire, and
    // three different ones: a reader that took the first and used it everywhere
    // would be right about a start and wrong about the other two, which is
    // exactly what a contract suite comparing the two implementations is for.
    return Disturbances::of(
        starting: WhatItTakesAway::atMost(45),
        stopping: WhatItTakesAway::atMost(20),
        restarting: WhatItTakesAway::atMost(30),
    );
}

// The Supervising contract, run against the adapter and against the fake.
//
// `G2`'s shape, and `StallingContractTest`'s argument one endpoint along: every
// test of the screen that starts and stops things will hand its subject an
// `AStackThatSupervises` and never open a socket, so a fake easier to satisfy
// than the adapter would enforce `N2-R7` against a stack that always says yes.
//
// The verb half is the one worth the trouble. A reading that disagreed between
// the two would show a wrong listing; a verb that disagreed would be a service
// somebody's household loses while the screen says it was stopped.
//
// What is deliberately not asserted here, as there: which endpoint is called,
// which body it carries, and that the connection was pinned. The fake dials
// nothing, so a contract asking those would either fail on it or be weakened
// until it passed — they are asserted of the adapter in `SupervisorsTest`.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** The stack whose services are asked after. */
function aStackWithServices(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('c', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('d', Fingerprint::CHARACTERS)),
    );
}

/** The session it is asked with. */
function theSessionTheStackIsSupervisedWith(): Session
{
    return Session::of('a-session-not-a-secret');
}

/** What both implementations answer with, where they answer. */
function theSameRunning(): Daemons
{
    return Daemons::of(
        HowTheStackIsRunning::Degraded,
        Forms::these(Form::called('media'), Form::called('downloads')),
        whatTheSupervisedVerbsCost(),
        Daemon::called(
            'Jellyfin',
            ServiceId::called('jellyfin'),
            Form::called('media'),
            HowAServiceRuns::Healthy,
            HowMuchItMatters::Core,
            WhatLeansOnIt::nothing(),
        ),
        Daemon::thatExited(
            'Sonarr',
            ServiceId::called('sonarr'),
            Form::called('downloads'),
            HowAServiceRuns::Failed,
            HowMuchItMatters::Important,
            WhatLeansOnIt::these(ServiceId::called('jellyfin')),
            137,
        ),
    );
}

/**
 * The payload a stack sends where those two are what it runs.
 *
 * Separate from the response so the rule at the foot of this file reads the
 * same array the adapter is given. A fixture checked in one place and sent in
 * another is a fixture that can drift from itself.
 *
 * Four fields here are sent by every stack and read by nothing on this side:
 * `describes` on each service, and `disturbs` and `undeclared` beside them. They
 * are written out anyway, because a fixture holding only what the reader happens
 * to want is a sample of a payload no stack sends — and the next reader to be
 * written against it would be written against a shape that does not exist.
 *
 * @return array<string, mixed>
 */
function whatAStackRunningSends(): array
{
    return [
        'api_version' => 1,
        'kind' => 'status',
        'data' => [
            'condition' => 'degraded',
            'disturbs' => howLongEachVerbTakesIt(),
            'forms' => ['media', 'downloads'],
            'services' => [
                [
                    'id' => 'jellyfin',
                    'name' => 'Jellyfin',
                    'describes' => 'The library everybody watches from',
                    'profile' => 'media',
                    'state' => 'healthy',
                    'criticality' => 'core',
                    'depends_on' => [],
                ],
                [
                    'id' => 'sonarr',
                    'name' => 'Sonarr',
                    'describes' => 'Fetches the series somebody is following',
                    'profile' => 'downloads',
                    'state' => 'failed',
                    'criticality' => 'important',
                    'depends_on' => ['jellyfin'],
                    'exit' => 137,
                ],
            ],
            // A container the machine is running that this stack's own
            // configuration does not declare. Every stack sends the field, so
            // the fixture carries it — and it is one rather than none because
            // an empty list would not tell a reader written later that the
            // rows have a shape of their own.
            'undeclared' => [[
                'id' => 'a-container-somebody-started',
                'describes' => 'Something running beside the stack',
                'state' => 'running',
            ]],
        ],
    ];
}

/**
 * How long each verb takes the stack away for, as a stack states it.
 *
 * Every one of the five, because the contract requires all five and a payload
 * short of one is a payload no stack sends. Two shapes between them, so a
 * reader written against this cannot be written against only the simpler.
 *
 * @return array<string, mixed>
 */
function howLongEachVerbTakesIt(): array
{
    return [
        'restarting' => ['bound' => 'bounded', 'seconds' => 30],
        'starting' => ['bound' => 'bounded', 'seconds' => 45],
        'stopping' => ['bound' => 'bounded', 'seconds' => 20],
        'stopping_after_downloads' => ['bound' => 'open-ended', 'until' => 'downloads'],
        'switching' => ['bound' => 'bounded', 'seconds' => 60],
    ];
}

/**
 * The payload a stack sends where it took a verb on.
 *
 * @return array<string, mixed>
 */
function whatAStackTakingAVerbSends(): array
{
    return [
        'api_version' => 1,
        'kind' => 'job',
        'data' => ['action' => 'down', 'job' => AStackThatSupervises::THE_JOB],
    ];
}

/** What the far end answers where those two are what it runs. */
function aRunningAnswer(): MockResponse
{
    return MockResponse::make((string) json_encode(whatAStackRunningSends()));
}

/** What the far end answers where it took a verb on. */
function aStartedAnswer(): MockResponse
{
    return MockResponse::make((string) json_encode(whatAStackTakingAVerbSends()));
}

/**
 * Both ways of asking, each set up to produce the same answer.
 *
 * A plain function rather than a Pest dataset, matching the other contract
 * suites: a dataset whose value is a closure is resolved by Pest and handed
 * back as one argument.
 *
 * @return array<string, Closure(): Supervising>
 */
function everyWayOfSupervising(MockResponse $answered, ?Obstacle $why = null): array
{
    return [
        'the fake' => static fn(): Supervising => $why instanceof Obstacle
            ? AStackThatSupervises::met($why)
            : AStackThatSupervises::with(theSameRunning()),
        'the adapter' => static function () use ($answered): Supervising {
            MockClient::destroyGlobal();
            MockClient::global([$answered]);

            return new Supervisors(new PinnedClients(), SequencedEntropy::counting());
        },
    ];
}

/** One word carried out of an `either()` arm. */
final readonly class WhatSupervisingTurnedOutToSay
{
    public function __construct(public string $said) {}
}

/** Every service, folded to a word each, so an order can be compared. */
function everythingRunningIn(Supervising $supervising): string
{
    return $supervising->running(aStackWithServices(), theSessionTheStackIsSupervisedWith())->either(
        these: static function (Daemons $daemons): WhatSupervisingTurnedOutToSay {
            $rows = [];

            foreach ($daemons as $daemon) {
                $rows[] = sprintf(
                    '%s/%s/%s',
                    $daemon->id()->named(),
                    $daemon->runs()->value,
                    $daemon->matters()->value,
                );
            }

            return new WhatSupervisingTurnedOutToSay(implode(' | ', $rows));
        },
        met: static fn(Obstacle $why): WhatSupervisingTurnedOutToSay
            => new WhatSupervisingTurnedOutToSay($why->value),
    )->said;
}

/** What the listing says it all amounts to, whichever arm it took. */
function whatTheStackAmountsTo(Supervising $supervising): string
{
    return $supervising->running(aStackWithServices(), theSessionTheStackIsSupervisedWith())->either(
        these: static fn(Daemons $daemons): WhatSupervisingTurnedOutToSay
            => new WhatSupervisingTurnedOutToSay($daemons->running()->value),
        met: static fn(Obstacle $why): WhatSupervisingTurnedOutToSay
            => new WhatSupervisingTurnedOutToSay($why->value),
    )->said;
}

/** What came back from saying a verb, as a word. */
function whatCameOfSaying(Supervising $supervising, AgreedTo $agreed): string
{
    return $supervising->told(aStackWithServices(), theSessionTheStackIsSupervisedWith(), $agreed)->either(
        started: static fn(Job $job): WhatSupervisingTurnedOutToSay
            => new WhatSupervisingTurnedOutToSay($job->shown()),
        met: static fn(Obstacle $why): WhatSupervisingTurnedOutToSay
            => new WhatSupervisingTurnedOutToSay($why->value),
    )->said;
}

it('N2-R7 — comes away with every service, how it is running, and how much it matters', function (): void {
    // All three together, in the stack's order. Which order an operator should
    // read them in is a screen's decision, made where there is a screen.
    foreach (everyWayOfSupervising(aRunningAnswer()) as $which => $make) {
        expect(everythingRunningIn($make()))->toBe(
            'jellyfin/healthy/core | sonarr/failed/important',
            $which,
        );
    }
});

it('carries the stack\'s own judgement rather than one worked out from the rows', function (): void {
    // `degraded` over two services of which one is healthy is exactly the sum a
    // phone would get wrong. Both implementations carry the word the stack
    // said, so neither can be used to build a screen that recomputes it.
    foreach (everyWayOfSupervising(aRunningAnswer()) as $which => $make) {
        expect(whatTheStackAmountsTo($make()))->toBe(HowTheStackIsRunning::Degraded->value, $which);
    }
});

it('N2-R7 — takes a verb about a service and comes away with a name to ask about', function (): void {
    $agreed = AgreedTo::theService(WhatToDoWithIt::Stop, ServiceId::called('sonarr'));

    foreach (everyWayOfSupervising(aStartedAnswer()) as $which => $make) {
        expect(whatCameOfSaying($make(), $agreed))->toBe(AStackThatSupervises::THE_JOB, $which);
    }
});

it('N2-R7 — takes the same verb about a whole form', function (): void {
    // The other half of the granularity `N2-R7` names. A port that took only
    // one of them would have a screen assembling the other out of services it
    // read a moment ago, which is a listing going stale between the reading and
    // the verb.
    $agreed = AgreedTo::theForm(WhatToDoWithIt::Restart, Form::called('downloads'));

    foreach (everyWayOfSupervising(aStartedAnswer()) as $which => $make) {
        expect(whatCameOfSaying($make(), $agreed))->toBe(AStackThatSupervises::THE_JOB, $which);
    }
});

it('N1-R10 — tells a session that has ended from a stack that is not answering', function (): void {
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::CredentialWasRefused],
        [MockResponse::make('{"error":"gone"}', 500), Obstacle::StackDidNotAnswer],
        [MockResponse::make('not json at all'), Obstacle::StackDidNotAnswer],
    ];

    foreach ($table as [$answered, $why]) {
        foreach (everyWayOfSupervising($answered, $why) as $which => $make) {
            expect(everythingRunningIn($make()))->toBe($why->value, $which);
        }
    }
});

it('N1-R10 — says the same about a verb it could not deliver', function (): void {
    // The half that matters more. A verb that failed and reported nothing would
    // leave an operator tapping stop on a service that goes on running, and a
    // verb that failed and reported a refused session sends them to sign in
    // rather than to the machine.
    $agreed = AgreedTo::theService(WhatToDoWithIt::Stop, ServiceId::called('sonarr'));

    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::CredentialWasRefused],
        [MockResponse::make('{"error":"gone"}', 500), Obstacle::StackDidNotAnswer],
    ];

    foreach ($table as [$answered, $why]) {
        foreach (everyWayOfSupervising($answered, $why) as $which => $make) {
            expect(whatCameOfSaying($make(), $agreed))->toBe($why->value, $which);
        }
    }
});

it('an answer this app cannot read is a stack that did not answer', function (): void {
    // A service missing its state is the shape that matters: the adapter
    // refuses it, and the refusal has to reach the screen as an obstacle rather
    // than as a raise. A listing one row short is a service nobody can turn off
    // from the phone, with nothing on the screen to say so.
    //
    // One field away from what a stack sends, and no further: everything else
    // here is what the contract asks for, so the refusal below can only be the
    // missing `state`. A fixture short of five fields would pass this case on a
    // reader that refused it for any of them.
    $answered = MockResponse::make(
        (string) json_encode([
            'api_version' => 1,
            'kind' => 'status',
            'data' => [
                'disturbs' => [
                    'starting' => ['bound' => 'bounded', 'seconds' => 180],
                    'stopping' => ['bound' => 'bounded', 'seconds' => 10],
                    'restarting' => ['bound' => 'bounded', 'seconds' => 180],
                    'stopping_after_downloads' => ['bound' => 'open-ended', 'until' => 'downloads'],
                    'switching' => ['bound' => 'bounded', 'seconds' => 180],
                ],
                'condition' => 'active',
                'disturbs' => howLongEachVerbTakesIt(),
                'forms' => ['media'],
                'services' => [[
                    'id' => 'jellyfin',
                    'name' => 'Jellyfin',
                    'describes' => 'The library everybody watches from',
                    'profile' => 'media',
                    'criticality' => 'core',
                    'depends_on' => [],
                ]],
                'undeclared' => [],
            ],
        ]),
    );

    foreach (everyWayOfSupervising($answered, Obstacle::StackDidNotAnswer) as $which => $make) {
        expect(everythingRunningIn($make()))->toBe(Obstacle::StackDidNotAnswer->value, $which);
    }
});

it('an acknowledgement with no name in it is a stack that did not answer', function (): void {
    // `N1-R41`'s state, reached through the port. The action was delivered, so
    // it must not be sent again, and there is no handle to ask after it by —
    // which reaches the screen as a stack that did not answer rather than as a
    // raise on a tap.
    $agreed = AgreedTo::theService(WhatToDoWithIt::Start, ServiceId::called('sonarr'));

    $answered = MockResponse::make(
        (string) json_encode(['api_version' => 1, 'kind' => 'job', 'data' => ['action' => 'up']]),
    );

    foreach (everyWayOfSupervising($answered, Obstacle::StackDidNotAnswer) as $which => $make) {
        expect(whatCameOfSaying($make(), $agreed))->toBe(Obstacle::StackDidNotAnswer->value, $which);
    }
});

it('N1-R11 — is asked about the stack it was handed, with that stack\'s session', function (): void {
    // The half a screen cannot assert about itself. A screen holding two stacks
    // and stopping a service on the wrong machine is this requirement broken
    // exactly where it costs the most.
    $supervising = AStackThatSupervises::with(theSameRunning());

    $supervising->running(aStackWithServices(), theSessionTheStackIsSupervisedWith());

    expect($supervising->askedAbout()?->id()->stored())->toBe(aStackWithServices()->id()->stored());
    expect($supervising->wasGivenASession())->toBeTrue();
});

it('N2-R8 — nothing reaches the stack until a verb is agreed to', function (): void {
    // The thing worth proving about a confirmation, and only the port can say
    // it: reading a listing must not carry one out. A screen that drew a row
    // and stopped it on the way past would be caught here and nowhere else.
    $supervising = AStackThatSupervises::with(theSameRunning());

    $supervising->running(aStackWithServices(), theSessionTheStackIsSupervisedWith());

    expect($supervising->whatItWasToldToDo())->toBe([]);

    $supervising->told(
        aStackWithServices(),
        theSessionTheStackIsSupervisedWith(),
        AgreedTo::theService(WhatToDoWithIt::Stop, ServiceId::called('sonarr')),
    );

    expect($supervising->whatItWasToldToDo())->toHaveCount(1);
});

it('stands in for a stack with payloads the contract would accept', function (): void {
    $payloads = [
        'the listing' => ['StatusEnvelope', whatAStackRunningSends()],
        'the handle' => ['JobEnvelope', whatAStackTakingAVerbSends()],
    ];

    foreach ($payloads as $which => [$envelope, $payload]) {
        expect(WhatTheContractAccepts::complaintsAbout($envelope, $payload))->toBe(
            [],
            sprintf("The payload this suite stands in for a stack with is not one a stack would send: %s.\n", $which),
        );
    }
});
