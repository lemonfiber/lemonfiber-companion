<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AFootprint;
use Modules\Kernel\Api\AServiceLeftOut;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Form;
use Modules\Kernel\Api\Forms;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Rehearsing;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Services;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheServicesLeftOut;
use Modules\Kernel\Api\WhatItWouldNeed;
use Modules\Kernel\Api\WhatStartingItWouldComeTo;
use Modules\Sdk\Api\PinnedClients;
use Modules\Sdk\Api\Rehearsers;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AStackThatRehearses;
use Tests\Support\WhatTheContractAccepts;

// The Rehearsing contract, run against the adapter and against the fake.
//
// `G2`'s shape, and `ExplainingContractTest`'s argument one endpoint along.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

function aStackThatRehearses(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** Starting `dl` on a machine with Usenet and no torrent credentials. */
function theSameRehearsal(): WhatStartingItWouldComeTo
{
    return WhatStartingItWouldComeTo::rehearsed(
        Services::these(ServiceId::called('sabnzbd'), ServiceId::called('sonarr')),
        TheServicesLeftOut::of(AServiceLeftOut::needing(ServiceId::called('qbittorrent'), 'qBittorrent', WhatItWouldNeed::Torrent, Forms::these(Form::called('dl')))),
        AFootprint::estimated(700, Services::these(ServiceId::called('sonarr'))),
    );
}

/**
 * What a stack sends for that rehearsal.
 *
 * @return array<string, mixed>
 */
function whatAStackRehearsingAStartSends(string $name = 'qBittorrent'): array
{
    return [
        'api_version' => 1,
        'kind' => 'preview',
        'data' => [
            'forms' => ['dl'],
            'profiles' => ['usenet', 'arr'],
            'services' => ['sabnzbd', 'sonarr'],
            'dropped' => [['profile' => 'torrent', 'needs' => 'torrent']],
            'filtered' => [['id' => 'qbittorrent', 'name' => $name, 'profile' => 'torrent', 'needs' => 'torrent', 'forms' => ['dl']]],
            'footprint' => ['estimated_mib' => 700, 'unestimated' => ['sonarr']],
        ],
    ];
}

/**
 * Both ways of asking, each set up to produce the same answer.
 *
 * @return array<string, Closure(): Rehearsing>
 */
function everyWayOfRehearsingAStart(MockResponse $answered, ?Obstacle $why = null): array
{
    return [
        'the fake' => static fn(): Rehearsing => $why instanceof Obstacle
            ? AStackThatRehearses::met($why)
            : AStackThatRehearses::with(theSameRehearsal()),
        'the adapter' => static function () use ($answered): Rehearsing {
            MockClient::destroyGlobal();
            MockClient::global([$answered]);

            return new Rehearsers(new PinnedClients());
        },
    ];
}

/** One line carried out of an `either()` arm. */
final readonly class WhatTheRehearsalTurnedOutToSay
{
    public function __construct(public string $said) {}
}

/** Everything the rehearsal says, folded to one line, so two answers can be compared. */
function everythingTheRehearsalSays(Rehearsing $rehearsing): string
{
    return $rehearsing->whatStarting(aStackThatRehearses(), Session::of('a-session-not-a-secret'), Form::called('dl'))->either(
        found: static function (WhatStartingItWouldComeTo $rehearsal): WhatTheRehearsalTurnedOutToSay {
            $leftOut = [];

            foreach ($rehearsal->leftOut() as $service) {
                $leftOut[] = sprintf('%s:%s:%d', $service->name(), $service->needs()->value, $service->askedBy()->count());
            }

            $started = [];

            foreach ($rehearsal->wouldStart() as $service) {
                $started[] = $service->named();
            }

            return new WhatTheRehearsalTurnedOutToSay(sprintf(
                '%s / %s / %d MiB',
                implode(',', $started),
                implode(',', $leftOut),
                $rehearsal->footprint()->mebibytes(),
            ));
        },
        met: static fn(Obstacle $why): WhatTheRehearsalTurnedOutToSay => new WhatTheRehearsalTurnedOutToSay($why->value),
    )->said;
}

it('comes away with what would start, each service left out with what it would need, and the estimate', function (): void {
    $answered = MockResponse::make((string) json_encode(whatAStackRehearsingAStartSends()));

    foreach (everyWayOfRehearsingAStart($answered) as $which => $make) {
        expect(everythingTheRehearsalSays($make()))->toBe('sabnzbd,sonarr / qBittorrent:torrent:1 / 700 MiB', $which);
    }
});

it('tells a session that has ended from a stack that is not answering', function (): void {
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::CredentialWasRefused],
        [MockResponse::make('{"error":"gone"}', 500), Obstacle::StackDidNotAnswer],
        [MockResponse::make('not json at all'), Obstacle::StackDidNotAnswer],
    ];

    foreach ($table as [$answered, $why]) {
        foreach (everyWayOfRehearsingAStart($answered, $why) as $which => $make) {
            expect(everythingTheRehearsalSays($make()))->toBe($why->value, sprintf('%s / %s', $which, $why->value));
        }
    }
});

it('a rehearsal this app cannot read is an obstacle, never a start half-described', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(whatAStackRehearsingAStartSends(name: ' ')))]);

    expect(everythingTheRehearsalSays(new Rehearsers(new PinnedClients())))->toBe(Obstacle::StackDidNotAnswer->value);
});

it('asks the forms read naming the form, which is how a start is rehearsed rather than the forms listed', function (): void {
    MockClient::destroyGlobal();
    $mock = MockClient::global([MockResponse::make((string) json_encode(whatAStackRehearsingAStartSends()))]);

    everythingTheRehearsalSays(new Rehearsers(new PinnedClients()));

    $sent = $mock->getLastPendingRequest();

    expect($sent?->getUrl())->toContain('/api/forms')
        ->and($sent?->query()->all())->toBe(['form' => 'dl']);
});

it('the fake records the form it was asked to rehearse', function (): void {
    $rehearsing = AStackThatRehearses::with(theSameRehearsal());
    everythingTheRehearsalSays($rehearsing);

    expect($rehearsing->asked())->toHaveCount(1)
        ->and($rehearsing->asked()[0]->named())->toBe('dl');
});

it('stands in for a stack with a payload the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('PreviewEnvelope', whatAStackRehearsingAStartSends()))
        ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n");
});
