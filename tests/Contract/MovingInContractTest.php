<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AMode;
use Modules\Kernel\Api\APortHeld;
use Modules\Kernel\Api\APortMoved;
use Modules\Kernel\Api\AProjectStanding;
use Modules\Kernel\Api\AServiceStanding;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\MovingIn;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheModes;
use Modules\Kernel\Api\ThePortsHeld;
use Modules\Kernel\Api\ThePortsItPublishes;
use Modules\Kernel\Api\ThePortsMoved;
use Modules\Kernel\Api\TheSurvey;
use Modules\Kernel\Api\Unsupported;
use Modules\Kernel\Api\WhatAdoptingOneWouldDo;
use Modules\Kernel\Api\WhatAdoptingWouldDo;
use Modules\Kernel\Api\WhatIsUnsupported;
use Modules\Kernel\Api\WhatLinkingCosts;
use Modules\Kernel\Api\WhatMayBeDone;
use Modules\Kernel\Api\WhatStandsHere;
use Modules\Sdk\Api\PinnedClients;
use Modules\Sdk\Api\Scouts;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AStackWithSomethingAlreadyOnIt;
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

            return new Scouts(new PinnedClients());
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
        met: static fn(Obstacle $why): WhatTheSurveyTurnedOutToSay => new WhatTheSurveyTurnedOutToSay($why->value),
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
        [MockResponse::make('{"error":"no"}', 401), Obstacle::CredentialWasRefused],
        [MockResponse::make('{"error":"gone"}', 500), Obstacle::StackDidNotAnswer],
        [MockResponse::make('not json at all'), Obstacle::StackDidNotAnswer],
    ];

    foreach ($table as [$answered, $why]) {
        foreach (everyWayOfSurveying($answered, $why) as $which => $make) {
            expect(everythingTheSurveySays($make()))->toBe($why->value, sprintf('%s / %s', $which, $why->value));
        }
    }
});

it('a survey this app cannot read is an obstacle, never a machine with less on it', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(whatAStackSaysIsAlreadyThere(tautulliRuns: 'no')))]);

    expect(everythingTheSurveySays(new Scouts(new PinnedClients())))->toBe(Obstacle::StackDidNotAnswer->value);
});

it('reads a survey that could not look as not having looked', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(whatAStackSaysIsAlreadyThere(read: false)))]);

    expect(everythingTheSurveySays(new Scouts(new PinnedClients())))->toStartWith("could not look\n");
});

it('asks the migration endpoint, and nothing else', function (): void {
    MockClient::destroyGlobal();
    $mock = MockClient::global([MockResponse::make((string) json_encode(whatAStackSaysIsAlreadyThere()))]);

    everythingTheSurveySays(new Scouts(new PinnedClients()));

    $sent = $mock->getLastPendingRequest();

    expect($sent?->getUrl())->toEndWith('/api/migration')
        ->and($sent?->query()->all())->toBe([]);
});

it('stands in for a stack with a payload the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('MigrationEnvelope', whatAStackSaysIsAlreadyThere()))
        ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n");
});
