<?php

declare(strict_types=1);

use Lemonfiber\Sdk\Contract\Api;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AMode;
use Modules\Kernel\Api\AMove;
use Modules\Kernel\Api\AMoveAgreed;
use Modules\Kernel\Api\APortHeld;
use Modules\Kernel\Api\APortMoved;
use Modules\Kernel\Api\AProjectStanding;
use Modules\Kernel\Api\AServiceAdopted;
use Modules\Kernel\Api\AServiceStanding;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\MovingIn;
use Modules\Kernel\Api\MovingInBy;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\Stance;
use Modules\Kernel\Api\TheAdoption;
use Modules\Kernel\Api\TheImport;
use Modules\Kernel\Api\TheModes;
use Modules\Kernel\Api\ThePortsHeld;
use Modules\Kernel\Api\ThePortsItPublishes;
use Modules\Kernel\Api\ThePortsMoved;
use Modules\Kernel\Api\TheRecords;
use Modules\Kernel\Api\TheReplacement;
use Modules\Kernel\Api\TheStandingBeside;
use Modules\Kernel\Api\TheSurvey;
use Modules\Kernel\Api\Unsupported;
use Modules\Kernel\Api\WhatAdoptingOneWouldDo;
use Modules\Kernel\Api\WhatAdoptingWouldDo;
use Modules\Kernel\Api\WhatBecameOfTheMove;
use Modules\Kernel\Api\WhatIsUnsupported;
use Modules\Kernel\Api\WhatLinkingCosts;
use Modules\Kernel\Api\WhatMayBeDone;
use Modules\Kernel\Api\WhatStandsHere;
use Modules\Kernel\Api\WhatWasNamed;
use Modules\Sdk\Api\PinnedClients;
use Modules\Sdk\Api\Scouts;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AStackWithSomethingAlreadyOnIt;
use Tests\Support\Fakes\SequencedEntropy;
use Tests\Support\WhatTheContractAccepts;

// The MovingIn contract, run against the adapter and against the fake.
//
// `G2`'s shape, and `WelcomingContractTest`'s argument one endpoint along.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** The stack asked what is already on its machine. */
function aStackToSurvey(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** What both implementations answer with. */
function theSameSurvey(): TheSurvey
{
    return TheSurvey::reported(
        looked: true,
        standing: WhatStandsHere::of(
            AProjectStanding::named(
                'media',
                AServiceStanding::found('sonarr', ThePortsItPublishes::of(8989), running: true, adoptable: true),
                AServiceStanding::found('tautulli', ThePortsItPublishes::of(), running: false, adoptable: false),
            ),
        ),
        conflicts: ThePortsHeld::of(APortHeld::of(8989, 'sonarr', 'media')),
        unsupported: WhatIsUnsupported::these(Unsupported::of('media/tautulli', 'lemonfiber does not run it')),
        beside: ThePortsMoved::of(APortMoved::of('sonarr', 8989, 8990)),
        linking: WhatLinkingCosts::cannotLink('Downloads and the library are on two filesystems', 'Every import is a second copy', 'Keep both under one mount', 'ext4', 'nfs'),
        choices: WhatMayBeDone::offered(TheModes::of(
            AMode::offered('adopt', 'Manages what is here', disturbs: false, preselected: true),
            AMode::offered('replace', 'Stops the old one', disturbs: true, preselected: false),
        ), WhatAdoptingWouldDo::of(WhatAdoptingOneWouldDo::said('sonarr', 'Its database is opened by a newer version', backupFirst: true, refused: false)), WhatIsUnsupported::these(Unsupported::of('Custom scripts', 'They run outside any service'))),
    );
}

/**
 * The payload a stack sends for that.
 *
 * @return array<string, mixed>
 */
function whatAStackSaysIsAlreadyThere(bool $read = true, mixed $tautulliRuns = false): array
{
    return [
        'api_version' => 1,
        'kind' => 'migration',
        'data' => [
            'read' => $read,
            'standing' => [
                ['project' => 'media', 'services' => [
                    ['service' => 'sonarr', 'ports' => [8989], 'running' => true, 'adoptable' => true],
                    ['service' => 'tautulli', 'ports' => [], 'running' => $tautulliRuns, 'adoptable' => false],
                ]],
            ],
            'conflicts' => [['port' => 8989, 'wanted_by' => 'sonarr', 'held_by' => 'media']],
            'unsupported' => [['what' => 'media/tautulli', 'because' => 'lemonfiber does not run it']],
            'carrying' => [[
                'service' => 'sonarr',
                'existing' => '4.0.1',
                'ours' => '4.0.9',
                'verdict' => 'newer',
                'because' => 'Its database is opened by a newer version',
                'backup_first' => true,
                'refused' => false,
            ]],
            'not_carried' => [['what' => 'Custom scripts', 'because' => 'They run outside any service']],
            'modes' => [
                ['mode' => 'adopt', 'what' => 'Manages what is here', 'disturbs' => false, 'preselected' => true],
                ['mode' => 'replace', 'what' => 'Stops the old one', 'disturbs' => true, 'preselected' => false],
            ],
            'beside' => [['service' => 'sonarr', 'from' => 8989, 'to' => 8990]],
            'linking' => [
                'links' => false,
                'because' => 'Downloads and the library are on two filesystems',
                'cost' => 'Every import is a second copy',
                'remedy' => 'Keep both under one mount',
                'filesystems' => ['ext4', 'nfs'],
                'forced' => false,
            ],
        ],
    ];
}

/**
 * Both ways of asking, each set up to produce the same answer.
 *
 * @return array<string, Closure(): MovingIn>
 */
function everyWayOfSurveying(MockResponse $answered, ?Obstacle $why = null): array
{
    return [
        'the fake' => static fn(): MovingIn => $why instanceof Obstacle
            ? AStackWithSomethingAlreadyOnIt::met($why)
            : AStackWithSomethingAlreadyOnIt::with(theSameSurvey()),
        'the adapter' => static function () use ($answered): MovingIn {
            MockClient::destroyGlobal();
            MockClient::global([$answered]);

            return new Scouts(new PinnedClients(), SequencedEntropy::counting());
        },
    ];
}

/** One line carried out of an `either()` arm. */
final readonly class WhatTheSurveyTurnedOutToSay
{
    public function __construct(public string $said) {}
}

/** One of two words, for a flag folded into a line. */
function oneWordFor(bool $is, string $yes, string $no): string
{
    return $is ? $yes : $no;
}

/** Every port a service publishes, as one line. */
function thePortsSurveyed(ThePortsItPublishes $ports): string
{
    $said = [];

    foreach ($ports as $port) {
        $said[] = sprintf('%d', $port);
    }

    return implode(',', $said);
}

/**
 * Every service of every project, a line each.
 *
 * @return list<string>
 */
function whatWasFoundStanding(TheSurvey $survey): array
{
    $lines = [];

    foreach ($survey->standing() as $project) {
        foreach ($project as $service) {
            $lines[] = sprintf('%s/%s|%s|%s|%s', $project->project(), $service->service(), thePortsSurveyed($service->ports()), oneWordFor($service->isRunning(), 'running', 'stopped'), oneWordFor($service->isAdoptable(), 'adoptable', 'not adoptable'));
        }
    }

    return $lines;
}

/**
 * What is in the way, what cannot be taken over, and where a service would move, a line each.
 *
 * @return list<string>
 */
function whatIsInTheWay(TheSurvey $survey): array
{
    $lines = [];

    foreach ($survey->conflicts() as $held) {
        $lines[] = sprintf('conflict %d|%s|%s', $held->port(), $held->wantedBy(), $held->heldBy());
    }

    foreach ($survey->unsupported() as $limit) {
        $lines[] = sprintf('unsupported %s|%s', $limit->what(), $limit->because());
    }

    foreach ($survey->beside() as $moved) {
        $lines[] = sprintf('beside %s|%d|%d', $moved->service(), $moved->from(), $moved->to());
    }

    return $lines;
}

/**
 * Every mode, what adopting each service would come to, and what no mode carries, a line each.
 *
 * @return list<string>
 */
function whatMayBeDoneAboutIt(TheSurvey $survey): array
{
    $lines = [];

    foreach ($survey->choices()->modes() as $mode) {
        $lines[] = sprintf('mode %s|%s|%s|%s', $mode->mode(), $mode->what(), oneWordFor($mode->disturbs(), 'disturbs', 'leaves it'), oneWordFor($mode->isPreselected(), 'chosen', 'not chosen'));
    }

    foreach ($survey->choices()->carrying() as $one) {
        $lines[] = sprintf('carrying %s|%s|%s|%s', $one->service(), $one->because(), oneWordFor($one->wantsACopyFirst(), 'copy first', 'no copy'), oneWordFor($one->isRefused(), 'refused', 'taken'));
    }

    foreach ($survey->choices()->notCarried() as $limit) {
        $lines[] = sprintf('not carried %s|%s', $limit->what(), $limit->because());
    }

    return $lines;
}

/** What a layout that cannot link costs, as one line. */
function whatLinkingWouldCost(TheSurvey $survey): string
{
    return $survey->linking()->either(
        costs: static fn(string $because, string $cost, string $remedy, array $filesystems): WhatTheSurveyTurnedOutToSay => new WhatTheSurveyTurnedOutToSay(sprintf('linking %s|%s|%s|%s', $because, $cost, $remedy, implode(',', $filesystems))),
        links: static fn(): WhatTheSurveyTurnedOutToSay => new WhatTheSurveyTurnedOutToSay('links'),
    )->said;
}

/** Everything a survey says, folded to lines, so two answers can be compared. */
function everythingTheSurveySays(MovingIn $movingIn): string
{
    return $movingIn->surveyedOn(aStackToSurvey(), Session::of('a-session-not-a-secret'))->either(
        found: static fn(TheSurvey $survey): WhatTheSurveyTurnedOutToSay => new WhatTheSurveyTurnedOutToSay(implode("\n", [
            oneWordFor($survey->looked(), 'looked', 'could not look'),
            ...whatWasFoundStanding($survey),
            ...whatIsInTheWay($survey),
            whatLinkingWouldCost($survey),
            ...whatMayBeDoneAboutIt($survey),
        ])),
        met: static fn(Obstacle $why): WhatTheSurveyTurnedOutToSay => new WhatTheSurveyTurnedOutToSay($why->kind()->value),
    )->said;
}

it('comes away with every project and service, what is in the way, and every mode in the stack\'s order', function (): void {
    $answered = MockResponse::make((string) json_encode(whatAStackSaysIsAlreadyThere()));

    foreach (everyWayOfSurveying($answered) as $which => $make) {
        expect(everythingTheSurveySays($make()))->toBe(
            "looked\n"
            . "media/sonarr|8989|running|adoptable\n"
            . "media/tautulli||stopped|not adoptable\n"
            . "conflict 8989|sonarr|media\n"
            . "unsupported media/tautulli|lemonfiber does not run it\n"
            . "beside sonarr|8989|8990\n"
            . "linking Downloads and the library are on two filesystems|Every import is a second copy|Keep both under one mount|ext4,nfs\n"
            . "mode adopt|Manages what is here|leaves it|chosen\n"
            . "mode replace|Stops the old one|disturbs|not chosen\n"
            . "carrying sonarr|Its database is opened by a newer version|copy first|taken\n"
            . 'not carried Custom scripts|They run outside any service',
            $which,
        );
    }
});

it('tells a session that has ended from a stack that is not answering', function (): void {
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::of(KindOfObstacle::CredentialWasRefused)],
        [MockResponse::make('{"error":"gone"}', 500), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
        [MockResponse::make('not json at all'), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
    ];

    foreach ($table as [$answered, $why]) {
        foreach (everyWayOfSurveying($answered, $why) as $which => $make) {
            expect(everythingTheSurveySays($make()))->toBe($why->kind()->value, sprintf('%s / %s', $which, $why->kind()->value));
        }
    }
});

it('a survey this app cannot read is an obstacle, never a machine with less on it', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(whatAStackSaysIsAlreadyThere(tautulliRuns: 'no')))]);

    expect(everythingTheSurveySays(new Scouts(new PinnedClients(), SequencedEntropy::counting())))->toEqual(KindOfObstacle::StackDidNotAnswer->value);
});

it('reads a survey that could not look as not having looked', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(whatAStackSaysIsAlreadyThere(read: false)))]);

    expect(everythingTheSurveySays(new Scouts(new PinnedClients(), SequencedEntropy::counting())))->toStartWith("could not look\n");
});

it('asks the migration endpoint, and nothing else', function (): void {
    MockClient::destroyGlobal();
    $mock = MockClient::global([MockResponse::make((string) json_encode(whatAStackSaysIsAlreadyThere()))]);

    everythingTheSurveySays(new Scouts(new PinnedClients(), SequencedEntropy::counting()));

    $sent = $mock->getLastPendingRequest();

    expect($sent?->getUrl())->toEndWith('/api/migration')
        ->and($sent?->query()->all())->toBe([]);
});

it('stands in for a stack with a payload the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('MigrationEnvelope', whatAStackSaysIsAlreadyThere()))
        ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n");
});

/** The stack's answer to asking what adopting would come to, once the work is done. */
function theSameAdoptionStaged(): AMove
{
    return AMove::at(Stance::Pending, TheAdoption::of(
        'media',
        WhatWasNamed::of('back_up', '/srv/sonarr'),
        '',
        AServiceAdopted::said(WhatAdoptingOneWouldDo::said('sonarr', 'Its database is opened by a newer version', backupFirst: true, refused: false), '4.0.1', '4.0.9', 'newer'),
    ));
}

/**
 * The payload a stack sends for that.
 *
 * @return array<string, mixed>
 */
function whatAStackSaysAdoptingWouldComeTo(string $stance = 'pending'): array
{
    return [
        'api_version' => 1,
        'kind' => 'adoption',
        'data' => [
            'project' => 'media',
            'stance' => $stance,
            'upgrades' => [[
                'service' => 'sonarr',
                'existing' => '4.0.1',
                'ours' => '4.0.9',
                'verdict' => 'newer',
                'because' => 'Its database is opened by a newer version',
                'backup_first' => true,
                'refused' => false,
            ]],
            'back_up' => ['/srv/sonarr'],
        ],
    ];
}

/** The handle a stack answers each act with. */
function theHandleAMoveIsAnsweredWith(): MockResponse
{
    return MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'job', 'data' => ['job' => 'j-1', 'action' => 'migrate-adopt']]), 202);
}

/**
 * Both ways of moving in, each set up to answer the handle and then where the move stands.
 *
 * @return array<string, Closure(): MovingIn>
 */
function everyWayOfMovingIn(MockResponse ...$answered): array
{
    return [
        'the fake' => static fn(): MovingIn => AStackWithSomethingAlreadyOnIt::with(theSameSurvey())->moving(
            WhatBecameOfTheMove::underway(Job::named('j-1')),
            WhatBecameOfTheMove::answered(theSameAdoptionStaged()),
        ),
        'the adapter' => static function () use ($answered): MovingIn {
            MockClient::destroyGlobal();
            MockClient::global($answered);

            return new Scouts(new PinnedClients(), SequencedEntropy::counting());
        },
    ];
}

/** One line carried out of an `either()` arm. */
final readonly class WhatMovingInTurnedOutToSay
{
    public function __construct(public string $said) {}
}

/** What adopting carries, folded to one line. */
function whatAdoptingCarried(TheAdoption $adoption): WhatMovingInTurnedOutToSay
{
    $said = [$adoption->project(), implode(',', iterator_to_array($adoption->backUp(), preserve_keys: false)), $adoption->backedUp()];

    foreach ($adoption as $upgrade) {
        $said[] = sprintf('%s %s>%s %s %s', $upgrade->what()->service(), $upgrade->existing(), $upgrade->ours(), $upgrade->verdict(), oneWordFor($upgrade->what()->wantsACopyFirst(), 'copy first', 'no copy'));
    }

    return new WhatMovingInTurnedOutToSay(implode('|', $said));
}

/** Everything an answer to moving in says, folded to one line, so two answers can be compared. */
function everythingMovingInSays(WhatBecameOfTheMove $became): string
{
    return $became->either(
        underway: static fn(Job $job): WhatMovingInTurnedOutToSay => new WhatMovingInTurnedOutToSay(sprintf('underway %s', $job->shown())),
        answered: static fn(AMove $move): WhatMovingInTurnedOutToSay => new WhatMovingInTurnedOutToSay(sprintf(
            '%s|%s|%s',
            $move->by()->value,
            $move->stance()->value,
            $move->either(
                adopting: whatAdoptingCarried(...),
                importing: static fn(): WhatMovingInTurnedOutToSay => new WhatMovingInTurnedOutToSay('importing'),
                standingBeside: static fn(): WhatMovingInTurnedOutToSay => new WhatMovingInTurnedOutToSay('beside'),
                replacing: static fn(): WhatMovingInTurnedOutToSay => new WhatMovingInTurnedOutToSay('replacing'),
            )->said,
        )),
        ended: static fn(): WhatMovingInTurnedOutToSay => new WhatMovingInTurnedOutToSay('ended'),
        refused: static fn(string $because): WhatMovingInTurnedOutToSay => new WhatMovingInTurnedOutToSay(sprintf('refused %s', $because)),
        met: static fn(Obstacle $why): WhatMovingInTurnedOutToSay => new WhatMovingInTurnedOutToSay($why->kind()->value),
    )->said;
}

/** A pending answer for each way of moving in, which is what a yes is built from. */
function aMoveStagedBy(MovingInBy $by): AMove
{
    return AMove::at(Stance::Pending, match ($by) {
        MovingInBy::Adopting => TheAdoption::of('', WhatWasNamed::of('back_up'), ''),
        MovingInBy::Importing => TheImport::of('', TheRecords::of(), TheRecords::of(), WhatIsUnsupported::none()),
        MovingInBy::StandingBeside => TheStandingBeside::of(ThePortsMoved::of(), ''),
        MovingInBy::Replacing => TheReplacement::of('', WhatWasNamed::of('would_stop'), WhatWasNamed::of('stopped'), WhatWasNamed::of('still_running')),
    });
}

it('asks what a way of moving in would come to, and follows the work to where it stands', function (): void {
    $done = MockResponse::make((string) json_encode(whatAStackSaysAdoptingWouldComeTo()));

    foreach (everyWayOfMovingIn(theHandleAMoveIsAnsweredWith(), $done) as $which => $make) {
        $movingIn = $make();
        $started = $movingIn->wouldMoveIn(aStackToSurvey(), Session::of('a-session-not-a-secret'), MovingInBy::Adopting);
        $finished = $movingIn->whatBecameOf(aStackToSurvey(), Session::of('a-session-not-a-secret'), Job::named('j-1'));

        expect(everythingMovingInSays($started))->toBe('underway j-1', $which)
            ->and(everythingMovingInSays($finished))->toBe('adopt|pending|media|/srv/sonarr||sonarr 4.0.1>4.0.9 newer copy first', $which);
    }
});

it('moves in as agreed, answered with the work to follow', function (): void {
    foreach (everyWayOfMovingIn(theHandleAMoveIsAnsweredWith()) as $which => $make) {
        expect(everythingMovingInSays($make()->moveIn(aStackToSurvey(), Session::of('a-session-not-a-secret'), AMoveAgreed::after(theSameAdoptionStaged()))))
            ->toBe('underway j-1', $which);
    }
});

it('asks the question without a yes or a key, and the yes with both, by lemonfiber\'s name for each act', function (MovingInBy $by): void {
    MockClient::destroyGlobal();
    $mock = MockClient::global([theHandleAMoveIsAnsweredWith(), theHandleAMoveIsAnsweredWith()]);
    $scouts = new Scouts(new PinnedClients(), SequencedEntropy::counting());

    $scouts->wouldMoveIn(aStackToSurvey(), Session::of('a-session-not-a-secret'), $by);
    $asked = $mock->getLastPendingRequest();

    expect($asked?->getUrl())->toEndWith(sprintf('/api/actions/%s', $by->asked()))
        ->and($asked?->body()?->all())->toBe(['confirm' => false])
        ->and($asked?->headers()->get(Api::IDEMPOTENCY_HEADER))->toBeNull();

    $scouts->moveIn(aStackToSurvey(), Session::of('a-session-not-a-secret'), AMoveAgreed::after(aMoveStagedBy($by)));
    $agreed = $mock->getLastPendingRequest();

    expect($agreed?->getUrl())->toEndWith(sprintf('/api/actions/%s', $by->asked()))
        ->and($agreed?->body()?->all())->toBe(['confirm' => true])
        ->and($agreed?->headers()->get(Api::IDEMPOTENCY_HEADER))->not->toBeNull();
})->with(MovingInBy::cases());

it('keeps following work the stack is still carrying out', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([theHandleAMoveIsAnsweredWith()]);

    expect(everythingMovingInSays(new Scouts(new PinnedClients(), SequencedEntropy::counting())->whatBecameOf(aStackToSurvey(), Session::of('a-session-not-a-secret'), Job::named('j-1'))))
        ->toBe('underway j-1');
});

it('tells a session that has ended from a stack that is not answering, when moving in', function (): void {
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::of(KindOfObstacle::CredentialWasRefused)],
        [MockResponse::make('Not yours to ask', 403, ['Content-Type' => 'text/plain']), Obstacle::of(KindOfObstacle::NotForThisAccount)],
        [MockResponse::make('The machine failed', 500, ['Content-Type' => 'text/plain']), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
        [MockResponse::make('  ', 404, ['Content-Type' => 'text/plain']), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
        [MockResponse::make('not json at all', 202), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
    ];

    foreach ($table as [$answered, $why]) {
        $ways = [
            'the fake' => static fn(): MovingIn => AStackWithSomethingAlreadyOnIt::met($why),
            'the adapter' => everyWayOfMovingIn($answered)['the adapter'],
        ];

        foreach ($ways as $which => $make) {
            expect(everythingMovingInSays($make()->wouldMoveIn(aStackToSurvey(), Session::of('a-session-not-a-secret'), MovingInBy::Replacing)))
                ->toBe($why->kind()->value, sprintf('%s / %s', $which, $why->kind()->value));
        }
    }
});

it('hands on a refusal in the stack\'s own words', function (int $status): void {
    $refused = MockResponse::make('There is no single setup here', $status, ['Content-Type' => 'text/plain']);
    $ways = [
        'the fake' => static fn(): MovingIn => AStackWithSomethingAlreadyOnIt::with(theSameSurvey())->moving(WhatBecameOfTheMove::refused('There is no single setup here')),
        'the adapter' => everyWayOfMovingIn($refused)['the adapter'],
    ];

    foreach ($ways as $which => $make) {
        expect(everythingMovingInSays($make()->moveIn(aStackToSurvey(), Session::of('a-session-not-a-secret'), AMoveAgreed::after(theSameAdoptionStaged()))))
            ->toBe('refused There is no single setup here', $which);
    }
})->with([400, 404, 499]);

it('hands on a refusal met while following the work, too', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make('That name was never handed out', 400, ['Content-Type' => 'text/plain'])]);

    expect(everythingMovingInSays(new Scouts(new PinnedClients(), SequencedEntropy::counting())->whatBecameOf(aStackToSurvey(), Session::of('a-session-not-a-secret'), Job::named('j-1'))))
        ->toBe('refused That name was never handed out');
});

it('says the stack has no outcome for work it no longer knows', function (): void {
    $ways = [
        'the fake' => static fn(): MovingIn => AStackWithSomethingAlreadyOnIt::with(theSameSurvey()),
        'the adapter' => everyWayOfMovingIn(MockResponse::make('{"error":"no such job"}', 404))['the adapter'],
    ];

    foreach ($ways as $which => $make) {
        expect(everythingMovingInSays($make()->whatBecameOf(aStackToSurvey(), Session::of('a-session-not-a-secret'), Job::named('j-1'))))->toBe('ended', $which);
    }
});

it('a move this app cannot read is an obstacle, never a move that did less', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(whatAStackSaysAdoptingWouldComeTo(stance: 'mostly')))]);

    expect(everythingMovingInSays(new Scouts(new PinnedClients(), SequencedEntropy::counting())->whatBecameOf(aStackToSurvey(), Session::of('a-session-not-a-secret'), Job::named('j-1'))))
        ->toEqual(KindOfObstacle::StackDidNotAnswer->value);
});

it('a handle this app cannot read is an obstacle, never a move under way', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'job', 'data' => ['job' => ' ', 'action' => 'migrate-adopt']]), 202)]);

    expect(everythingMovingInSays(new Scouts(new PinnedClients(), SequencedEntropy::counting())->wouldMoveIn(aStackToSurvey(), Session::of('a-session-not-a-secret'), MovingInBy::Adopting)))
        ->toEqual(KindOfObstacle::StackDidNotAnswer->value);
});

it('stands in for a stack with an answer to adopting the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('AdoptionEnvelope', whatAStackSaysAdoptingWouldComeTo()))
        ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n");
});
