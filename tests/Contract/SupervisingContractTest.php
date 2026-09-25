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
// than the adapter would enforce *start and stop, per service and per whole
// form* against a stack that always says yes.
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
        Forms::these(Form::called('library'), Form::called('full')),
        whatTheSupervisedVerbsCost(),
        Daemon::called(
            'Jellyfin',
            ServiceId::called('jellyfin'),
            HowAServiceRuns::Healthy,
            HowMuchItMatters::Core,
            WhatLeansOnIt::nothing(),
        ),
        Daemon::thatExited(
            'Sonarr',
            ServiceId::called('sonarr'),
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
            'active_forms' => ['library', 'hunt'],
            'filtered' => [[
                'id' => 'qbittorrent',
                'name' => 'qBittorrent',
                'profile' => 'torrent',
                'needs' => 'torrent',
                'forms' => ['hunt'],
            ]],
            // The forms this reading was asked about. `/api/status` asks about
            // none, so a stack sends none here however many it declares — which
            // is the trap: read as the stack's forms, it draws no form at all.
            'forms' => [],
            'services' => [
                [
                    'id' => 'jellyfin',
                    'name' => 'Jellyfin',
                    'describes' => 'The library everybody watches from',
                    // A profile, and not a form: `media` is what `library`
                    // and `full` include, and no verb takes it.
                    'profile' => 'media',
                    // Two forms ask for it, and it runs once for both.
                    'forms' => ['library', 'hunt'],
                    'state' => 'healthy',
                    'criticality' => 'core',
                    'depends_on' => [],
                ],
                [
                    'id' => 'sonarr',
                    'name' => 'Sonarr',
                    'describes' => 'Fetches the series somebody is following',
                    'profile' => 'tv',
                    'forms' => ['hunt'],
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

/**
 * The payload a stack sends when asked which forms it declares.
 *
 * Two of the forms the bundled stack declares, spelled as it spells them, and
 * neither of them a profile any service above carries — which is what the
 * listing is asked to keep apart.
 *
 * @return array<string, mixed>
 */
function whatAStackDeclaringFormsSends(): array
{
    return [
        'api_version' => 1,
        'kind' => 'forms',
        'data' => ['forms' => [
            [
                'id' => 'library',
                'name' => 'Library',
                'description' => 'Serve what exists. Requires no third-party accounts.',
                'composable' => true,
            ],
            ['id' => 'full', 'name' => 'Full', 'description' => 'The lot.', 'composable' => true],
        ]],
    ];
}

/**
 * What the far end answers where those two are what it runs.
 *
 * Two answers, in the order the adapter asks: what is running, and then the
 * forms the stack declares.
 *
 * @return list<MockResponse>
 */
function aRunningAnswer(): array
{
    return [
        MockResponse::make((string) json_encode(whatAStackRunningSends())),
        MockResponse::make((string) json_encode(whatAStackDeclaringFormsSends())),
    ];
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
 * @param list<MockResponse> $answered what the far end answers, in the order it is asked
 *
 * @return array<string, Closure(): Supervising>
 */
function everyWayOfSupervising(array $answered, ?Obstacle $why = null): array
{
    return [
        'the fake' => static fn(): Supervising => $why instanceof Obstacle
            ? AStackThatSupervises::met($why)
            : AStackThatSupervises::with(theSameRunning()),
        'the adapter' => static function () use ($answered): Supervising {
            MockClient::destroyGlobal();
            MockClient::global($answered);

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

/**
 * The forms a listing came to, as one line to compare.
 *
 * Every row on the wire names its profile, so a profile read as a form would
 * show up here: a control that asks a stack for something it has never heard
 * of. The forms read off the wrong field would show up as none.
 */
function theFormsIn(Supervising $supervising): string
{
    return $supervising->running(aStackWithServices(), theSessionTheStackIsSupervisedWith())->either(
        these: static function (Daemons $daemons): WhatSupervisingTurnedOutToSay {
            $forms = [];

            foreach ($daemons->forms() as $form) {
                $forms[] = $form->named();
            }

            return new WhatSupervisingTurnedOutToSay(sprintf('forms: %s', implode(', ', $forms)));
        },
        met: static fn(Obstacle $why): WhatSupervisingTurnedOutToSay
            => new WhatSupervisingTurnedOutToSay($why->value),
    )->said;
}

it('N2-R7 — the forms are the ones the stack declares, and no profile is one of them', function (): void {
    // The status envelope says it was asked about no form and each service
    // names its profile; the stack's list of forms names `library` and `full`.
    // Only the last is a form a verb can be asked for by.
    foreach (everyWayOfSupervising(aRunningAnswer()) as $which => $make) {
        expect(theFormsIn($make()))->toBe('forms: library, full', $which);
    }
});

it('N18-R9 — forms that could not be read are a stack that did not answer, not one with none', function (): void {
    // The listing arrived and the forms did not. Reading that as a stack that
    // declares no forms would take every form control off the screen and say
    // nothing about why; a stack that did not answer is the honest sentence.
    $running = MockResponse::make((string) json_encode(whatAStackRunningSends()));

    $table = [
        'refused' => MockResponse::make('{"error":"gone"}', 500),
        'a form with no id' => MockResponse::make((string) json_encode([
            'api_version' => 1,
            'kind' => 'forms',
            'data' => ['forms' => [['name' => 'Library', 'description' => 'Serve what exists.', 'composable' => true]]],
        ])),
    ];

    foreach ($table as $case => $forms) {
        foreach (everyWayOfSupervising([$running, $forms], Obstacle::StackDidNotAnswer) as $which => $make) {
            expect(theFormsIn($make()))->toBe(Obstacle::StackDidNotAnswer->value, sprintf('%s: %s', $case, $which));
        }
    }
});

it('N2-R7 — takes a verb about a service and comes away with a name to ask about', function (): void {
    $agreed = AgreedTo::theService(WhatToDoWithIt::Stop, ServiceId::called('sonarr'));

    foreach (everyWayOfSupervising([aStartedAnswer()]) as $which => $make) {
        expect(whatCameOfSaying($make(), $agreed))->toBe(AStackThatSupervises::THE_JOB, $which);
    }
});

it('N2-R7 — takes the same verb about a whole form', function (): void {
    // The other half of the granularity owed — one service, and a whole form. A
    // port that took only
    // one of them would have a screen assembling the other out of services it
    // read a moment ago, which is a listing going stale between the reading and
    // the verb.
    $agreed = AgreedTo::theForm(WhatToDoWithIt::Restart, Form::called('library'));

    foreach (everyWayOfSupervising([aStartedAnswer()]) as $which => $make) {
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
        foreach (everyWayOfSupervising([$answered], $why) as $which => $make) {
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
        foreach (everyWayOfSupervising([$answered], $why) as $which => $make) {
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
                'condition' => 'active',
                'disturbs' => howLongEachVerbTakesIt(),
                'active_forms' => [],
                'filtered' => [],
                'forms' => [],
                'services' => [[
                    'id' => 'jellyfin',
                    'name' => 'Jellyfin',
                    'describes' => 'The library everybody watches from',
                    'profile' => 'media',
                    'forms' => [],
                    'criticality' => 'core',
                    'depends_on' => [],
                ]],
                'undeclared' => [],
            ],
        ]),
    );

    $declared = MockResponse::make((string) json_encode(whatAStackDeclaringFormsSends()));

    foreach (everyWayOfSupervising([$answered, $declared], Obstacle::StackDidNotAnswer) as $which => $make) {
        expect(everythingRunningIn($make()))->toBe(Obstacle::StackDidNotAnswer->value, $which);
    }
});

it('an acknowledgement with no name in it is a stack that did not answer', function (): void {
    // The state where an action was delivered and named nothing, reached through
    // the port. It must not be sent again, and there is no handle to ask after
    // it by —
    // which reaches the screen as a stack that did not answer rather than as a
    // raise on a tap.
    $agreed = AgreedTo::theService(WhatToDoWithIt::Start, ServiceId::called('sonarr'));

    $answered = MockResponse::make(
        (string) json_encode(['api_version' => 1, 'kind' => 'job', 'data' => ['action' => 'up']]),
    );

    foreach (everyWayOfSupervising([$answered], Obstacle::StackDidNotAnswer) as $which => $make) {
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
        'the forms' => ['FormsEnvelope', whatAStackDeclaringFormsSends()],
        'the handle' => ['JobEnvelope', whatAStackTakingAVerbSends()],
    ];

    foreach ($payloads as $which => [$envelope, $payload]) {
        expect(WhatTheContractAccepts::complaintsAbout($envelope, $payload))->toBe(
            [],
            sprintf("The payload this suite stands in for a stack with is not one a stack would send: %s.\n", $which),
        );
    }
});
