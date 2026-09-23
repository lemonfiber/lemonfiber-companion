<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Provenance;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\WhereItComesFrom;
use Modules\Kernel\Api\WhereTheServicesComeFrom;
use Modules\Sdk\Api\Archivists;
use Modules\Sdk\Api\PinnedClients;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AStackThatNamesItsOrigins;
use Tests\Support\WhatTheContractAccepts;

// The Provenance contract, run against the adapter and against the fake.
//
// `G2`'s shape, and `HistoryContractTest`'s argument one endpoint along: every
// test of the provenance screen hands its subject an `AStackThatNamesItsOrigins`
// and never opens a socket, so a fake easier to satisfy than the adapter would
// enforce *a stack declaring nothing is not one that could not be read* against
// a stack that always answers.
//
// What is deliberately not asserted, as there: which endpoint is called, and
// that the connection was pinned. The fake dials nothing.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** The stack whose origins are asked for. */
function aStackThatSaysWhereItsServicesComeFrom(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** What both implementations answer with. */
function theSameOrigins(): WhereTheServicesComeFrom
{
    return WhereTheServicesComeFrom::declaring(
        WhereItComesFrom::declared(ServiceId::called('sonarr'), 'Sonarr', 'lscr.io/linuxserver/sonarr', '4.0.15', 'https://github.com/Sonarr/Sonarr', 'GPL-3.0-only'),
        WhereItComesFrom::declared(ServiceId::called('jellyfin'), 'Jellyfin', 'jellyfin/jellyfin', '10.10.7', 'https://github.com/jellyfin/jellyfin', 'GPL-2.0-only'),
    );
}

/**
 * The payload a stack sends for those origins.
 *
 * The first licence is a parameter rather than something a case reaches in
 * and overwrites, for {@see whatAStackKeepingARecordSends()}'s reason.
 *
 * @return array<string, mixed>
 */
function whatAStackNamingItsOriginsSends(string $licenceOfTheFirst = 'GPL-3.0-only'): array
{
    return [
        'api_version' => 1,
        'kind' => 'provenance',
        'data' => [
            'services' => [
                [
                    'id' => 'sonarr',
                    'name' => 'Sonarr',
                    'image' => 'lscr.io/linuxserver/sonarr',
                    'pinned' => '4.0.15',
                    'upstream' => 'https://github.com/Sonarr/Sonarr',
                    'license' => $licenceOfTheFirst,
                ],
                [
                    'id' => 'jellyfin',
                    'name' => 'Jellyfin',
                    'image' => 'jellyfin/jellyfin',
                    'pinned' => '10.10.7',
                    'upstream' => 'https://github.com/jellyfin/jellyfin',
                    'license' => 'GPL-2.0-only',
                ],
            ],
        ],
    ];
}

/**
 * The payload a stack declaring nothing sends.
 *
 * @return array<string, mixed>
 */
function whatAStackDeclaringNothingSends(): array
{
    return ['api_version' => 1, 'kind' => 'provenance', 'data' => ['services' => []]];
}

/**
 * Both ways of asking, each set up to produce the same answer.
 *
 * @return array<string, Closure(): Provenance>
 */
function everyWayOfAskingWhereItComesFrom(MockResponse $answered, ?Obstacle $why = null, ?WhereTheServicesComeFrom $origins = null): array
{
    return [
        'the fake' => static fn(): Provenance => $why instanceof Obstacle
            ? AStackThatNamesItsOrigins::met($why)
            : AStackThatNamesItsOrigins::with($origins ?? theSameOrigins()),
        'the adapter' => static function () use ($answered): Provenance {
            MockClient::destroyGlobal();
            MockClient::global([$answered]);

            return new Archivists(new PinnedClients());
        },
    ];
}

/** One line carried out of an `either()` arm. */
final readonly class WhatTheOriginsTurnedOutToSay
{
    public function __construct(public string $said) {}
}

/** Everything the origins say, folded to one line, so two answers can be compared. */
function everythingTheOriginsSay(Provenance $provenance): string
{
    return $provenance->declaredOn(aStackThatSaysWhereItsServicesComeFrom(), Session::of('a-session-not-a-secret'))->either(
        origins: static function (WhereTheServicesComeFrom $origins): WhatTheOriginsTurnedOutToSay {
            $rows = [];

            foreach ($origins as $origin) {
                $rows[] = sprintf(
                    '%s/%s/%s/%s/%s/%s',
                    $origin->service()->named(),
                    $origin->name(),
                    $origin->image(),
                    $origin->pinned(),
                    $origin->upstream(),
                    $origin->licence(),
                );
            }

            return new WhatTheOriginsTurnedOutToSay(sprintf('origins: %s', implode(' | ', $rows)));
        },
        met: static fn(Obstacle $why): WhatTheOriginsTurnedOutToSay => new WhatTheOriginsTurnedOutToSay($why->value),
    )->said;
}

it('N11-R6, N11-R7 — comes away with every service\'s image, pin, upstream and licence, in the stack\'s order', function (): void {
    $answered = MockResponse::make((string) json_encode(whatAStackNamingItsOriginsSends()));

    foreach (everyWayOfAskingWhereItComesFrom($answered) as $which => $make) {
        expect(everythingTheOriginsSay($make()))->toBe(
            'origins: sonarr/Sonarr/lscr.io/linuxserver/sonarr/4.0.15/https://github.com/Sonarr/Sonarr/GPL-3.0-only'
            . ' | jellyfin/Jellyfin/jellyfin/jellyfin/10.10.7/https://github.com/jellyfin/jellyfin/GPL-2.0-only',
            $which,
        );
    }
});

it('a stack declaring nothing answers with no origins, not an obstacle', function (): void {
    $answered = MockResponse::make((string) json_encode(whatAStackDeclaringNothingSends()));

    foreach (everyWayOfAskingWhereItComesFrom($answered, origins: WhereTheServicesComeFrom::declaring()) as $which => $make) {
        expect(everythingTheOriginsSay($make()))->toBe('origins: ', $which);
    }
});

it('tells a session that has ended from a stack that is not answering', function (): void {
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::CredentialWasRefused],
        [MockResponse::make('{"error":"gone"}', 500), Obstacle::StackDidNotAnswer],
        [MockResponse::make('not json at all'), Obstacle::StackDidNotAnswer],
    ];

    foreach ($table as [$answered, $why]) {
        foreach (everyWayOfAskingWhereItComesFrom($answered, $why) as $which => $make) {
            expect(everythingTheOriginsSay($make()))->toBe($why->value, sprintf('%s / %s', $which, $why->value));
        }
    }
});

it('N11-R7 — origins this app cannot read are an obstacle, not a shorter list', function (): void {
    // A service dropped for a blank licence is a service the screen says this
    // stack does not run.
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(whatAStackNamingItsOriginsSends('  ')))]);

    expect(everythingTheOriginsSay(new Archivists(new PinnedClients())))->toBe(Obstacle::StackDidNotAnswer->value);
});

it('stands in for a stack with a payload the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('ProvenanceEnvelope', whatAStackNamingItsOriginsSends()))
        ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n")
        ->and(WhatTheContractAccepts::complaintsAbout('ProvenanceEnvelope', whatAStackDeclaringNothingSends()))
        ->toBe([], "The empty payload this suite stands in for a stack with is not one a stack would send.\n");
});
