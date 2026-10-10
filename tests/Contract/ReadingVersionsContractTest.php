<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AGroupOfChanges;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowTheNotesStand;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ReadingVersions;
use Modules\Kernel\Api\Release;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\WhatAReleaseDelivers;
use Modules\Kernel\Api\WhatRunsHere;
use Modules\Sdk\Api\Chroniclers;
use Modules\Sdk\Api\PinnedClients;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AStackThatNamesItsVersions;
use Tests\Support\TheWordCarriedOut;
use Tests\Support\WhatTheContractAccepts;

// The ReadingVersions contract, run against the adapter and against the fake.
//
// `SelfCheckingContractTest`'s argument one endpoint along.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** The stack asked which versions it runs. */
function aStackThatKnowsItsVersions(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** What both implementations answer with. */
function theSameVersions(): WhatRunsHere
{
    return WhatRunsHere::reported(
        '0.16.0',
        '0.9.0',
        'Docker Compose version v2.29.1',
        HowTheNotesStand::Current,
        Release::called('0.16.0', noticeable: true, withdrawn: false, delivers: WhatAReleaseDelivers::said('Plugins')),
        AGroupOfChanges::titled('New', 'The panel shows the forwarded port', 'Plugins can be installed'),
        AGroupOfChanges::titled('Fixed', 'A stuck download is said once'),
    );
}

/**
 * The payload a stack sends for that.
 *
 * @return array<string, mixed>
 */
function whatAStackSaysItRuns(string $notes = 'current'): array
{
    return [
        'api_version' => 1,
        'kind' => 'version',
        'data' => [
            'binary' => '0.16.0',
            'supported_schema' => [1, 2],
            'stack' => '0.9.0',
            'compose' => 'Docker Compose version v2.29.1',
            'changelog' => [
                'state' => $notes,
                'running' => [
                    'version' => '0.16.0',
                    'tag' => 'v0.16.0',
                    'delivers' => 'Plugins',
                    'user_facing' => true,
                    'groups' => [
                        ['title' => 'New', 'entries' => [
                            ['summary' => 'The panel shows the forwarded port', 'requirements' => ['N2-R4']],
                            ['summary' => 'Plugins can be installed', 'requirements' => []],
                        ]],
                        ['title' => 'Fixed', 'entries' => [['summary' => 'A stuck download is said once', 'requirements' => []]]],
                    ],
                ],
                'releases' => [['version' => '0.16.0', 'user_facing' => true]],
                'requirements' => [],
            ],
        ],
    ];
}

/**
 * Both ways of asking, each set up to produce the same answer.
 *
 * @return array<string, Closure(): ReadingVersions>
 */
function everyWayOfAskingWhatRuns(MockResponse $answered, ?Obstacle $why = null): array
{
    return [
        'the fake' => static fn(): ReadingVersions => $why instanceof Obstacle
            ? AStackThatNamesItsVersions::met($why)
            : AStackThatNamesItsVersions::with(theSameVersions()),
        'the adapter' => static function () use ($answered): ReadingVersions {
            MockClient::destroyGlobal();
            MockClient::global([$answered]);

            return new Chroniclers(new PinnedClients());
        },
    ];
}

/** Everything the reading says, folded to one line, so two answers can be compared. */
function everythingTheVersionsSay(ReadingVersions $reading): string
{
    return $reading->versionsOn(aStackThatKnowsItsVersions(), Session::of('a-session-not-a-secret'))->either(
        found: static fn(WhatRunsHere $runs): TheWordCarriedOut => new TheWordCarriedOut(sprintf(
            '%s|%s|%s|%s|%s',
            $runs->lemonfiber(),
            $runs->stack(),
            $runs->engine(),
            $runs->notes()->value,
            $runs->running(
                named: static fn(Release $release, array $changes): TheWordCarriedOut => new TheWordCarriedOut(sprintf(
                    '%s %s %s: %s',
                    $release->version(),
                    $release->theHouseholdWouldNotice() ? 'noticed' : 'unnoticed',
                    $release->wasWithdrawn() ? 'withdrawn' : 'standing',
                    implode('; ', array_map(
                        static fn(AGroupOfChanges $group): string => sprintf('%s=%s', $group->title(), implode(',', iterator_to_array($group, preserve_keys: false))),
                        $changes,
                    )),
                )),
                notNamed: static fn(): TheWordCarriedOut => new TheWordCarriedOut('-'),
            )->said,
        )),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->value),
    )->said;
}

it('comes away with the three versions, whether the notes describe them, and the running release\'s notes', function (): void {
    $answered = MockResponse::make((string) json_encode(whatAStackSaysItRuns()));

    foreach (everyWayOfAskingWhatRuns($answered) as $which => $make) {
        expect(everythingTheVersionsSay($make()))->toBe(
            '0.16.0|0.9.0|Docker Compose version v2.29.1|current|0.16.0 noticed standing: New=The panel shows the forwarded port,Plugins can be installed; Fixed=A stuck download is said once',
            $which,
        );
    }
});

it('tells a session that has ended from a stack that is not answering', function (): void {
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::of(KindOfObstacle::CredentialWasRefused)],
        [MockResponse::make('{"error":"gone"}', 500), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
        [MockResponse::make('not json at all'), Obstacle::of(KindOfObstacle::AnswerCouldNotBeRead)],
    ];

    foreach ($table as [$answered, $why]) {
        foreach (everyWayOfAskingWhatRuns($answered, $why) as $which => $make) {
            expect(everythingTheVersionsSay($make()))->toBe($why->kind()->value, sprintf('%s / %s', $which, $why->kind()->value));
        }
    }
});

it('asks the version endpoint, with nothing in the question', function (): void {
    MockClient::destroyGlobal();
    $mock = MockClient::global([MockResponse::make((string) json_encode(whatAStackSaysItRuns()))]);

    everythingTheVersionsSay(new Chroniclers(new PinnedClients()));

    $sent = $mock->getLastPendingRequest();

    expect($sent?->getUrl())->toContain('/api/version')
        ->and($sent?->query()->all())->toBe([]);
});

it('stands in for a stack with a payload the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('VersionEnvelope', whatAStackSaysItRuns()))
        ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n");
});
