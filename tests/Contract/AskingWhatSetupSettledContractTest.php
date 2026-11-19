<?php

declare(strict_types=1);

use Modules\Kernel\Api\AskingWhatSetupSettled;
use Modules\Kernel\Api\HowFarSetupGot;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\TheDataRoot;
use Modules\Kernel\Api\TheServiceUser;
use Modules\Kernel\Api\TheWaysOfDownloading;
use Modules\Kernel\Api\WhatSetupSettled;
use Modules\Kernel\Api\WhereTheServiceUserComesFrom;
use Modules\Sdk\Api\PinnedClients;
use Modules\Sdk\Api\Setups;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AStackThatSaysWhatSetupSettled;
use Tests\Support\TheHealthScreenOfTheLoft;
use Tests\Support\TheWordCarriedOut;
use Tests\Support\WhatTheContractAccepts;

// The AskingWhatSetupSettled contract, run against the adapter and against the fake.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** What both implementations answer with: set up part-way, the library on an NFS export, torrents alone, one of PUID and PGID set. */
function theSameSettlement(): WhatSetupSettled
{
    return WhatSetupSettled::said(
        HowFarSetupGot::PartWay,
        TheDataRoot::at('nas:/export/media'),
        TheWaysOfDownloading::of(usenet: false, torrent: true),
        TheServiceUser::runsAs(1234, 1000, WhereTheServiceUserComesFrom::PartSet),
    );
}

/**
 * The payload a stack sends for that settlement, with the fields a test changes and those it leaves out.
 *
 * @param  array<mixed>         $changed
 * @return array<string, mixed>
 */
function whatAStackSaysSetupSettled(array $changed = [], string ...$leftOut): array
{
    $data = [
        'setup' => 'part-way',
        'data_root' => 'nas:/export/media',
        'protocols' => ['usenet' => false, 'torrent' => true],
        'service_user' => ['uid' => 1234, 'gid' => 1000, 'from' => 'part-set'],
        ...$changed,
    ];

    foreach ($leftOut as $field) {
        unset($data[$field]);
    }

    return ['api_version' => 1, 'kind' => 'settled', 'data' => $data];
}

/**
 * Both ways of asking, each set up to produce the same answer.
 *
 * @return array<string, Closure(): AskingWhatSetupSettled>
 */
function everyWayOfAskingWhatSetupSettled(MockResponse $answered, ?Obstacle $why = null): array
{
    return [
        'the fake' => static fn(): AskingWhatSetupSettled => $why instanceof Obstacle
            ? AStackThatSaysWhatSetupSettled::met($why)
            : AStackThatSaysWhatSetupSettled::with(theSameSettlement()),
        'the adapter' => static function () use ($answered): AskingWhatSetupSettled {
            MockClient::destroyGlobal();
            MockClient::global([$answered]);

            return new Setups(new PinnedClients());
        },
    ];
}

/** Everything one way of asking comes away with, folded to text. */
function everythingTheStackSaysSetupSettled(AskingWhatSetupSettled $asking): string
{
    return $asking->settled(TheHealthScreenOfTheLoft::theStackBeingLookedAt(), Session::of('a-session-not-a-secret'))->either(
        said: static fn(WhatSetupSettled $settled): TheWordCarriedOut => new TheWordCarriedOut(sprintf(
            '%s; root %s "%s"; usenet %s, torrent %s; runs as %s from %s',
            $settled->setup()->value,
            $settled->dataRoot()->isChosen() ? 'chosen' : 'not chosen',
            $settled->dataRoot()->path(),
            var_export($settled->downloading()->usenet(), return: true),
            var_export($settled->downloading()->torrent(), return: true),
            $settled->serviceUser()->asTyped(),
            $settled->serviceUser()->from()->value,
        )),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->value),
    )->said;
}

it('comes away with how far setup got, where the library is kept, how it downloads and who the services run as', function (): void {
    $answered = MockResponse::make((string) json_encode(whatAStackSaysSetupSettled()));
    $said = [];

    foreach (everyWayOfAskingWhatSetupSettled($answered) as $which => $make) {
        $said[$which] = everythingTheStackSaysSetupSettled($make());
    }

    expect($said['the adapter'])->toBe($said['the fake'])
        ->and($said['the fake'])->toBe('part-way; root chosen "nas:/export/media"; usenet false, torrent true; runs as 1234:1000 from part-set');
});

it('reads a data root left out or null as not chosen', function (): void {
    $payloads = [
        'null' => whatAStackSaysSetupSettled(['setup' => 'none', 'data_root' => null]),
        'left out' => whatAStackSaysSetupSettled(['setup' => 'none'], 'data_root'),
    ];

    foreach ($payloads as $which => $payload) {
        MockClient::destroyGlobal();
        MockClient::global([MockResponse::make((string) json_encode($payload))]);

        expect(everythingTheStackSaysSetupSettled(new Setups(new PinnedClients())))
            ->toBe('none; root not chosen ""; usenet false, torrent true; runs as 1234:1000 from part-set', $which);
    }
});

it('tells a session that has ended from a stack that is not answering', function (): void {
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::of(KindOfObstacle::CredentialWasRefused)],
        [MockResponse::make('{"error":"gone"}', 500), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
        [MockResponse::make('not json at all'), Obstacle::of(KindOfObstacle::AnswerCouldNotBeRead)],
    ];

    foreach ($table as [$answered, $why]) {
        foreach (everyWayOfAskingWhatSetupSettled($answered, $why) as $which => $make) {
            expect(everythingTheStackSaysSetupSettled($make()))->toBe($why->kind()->value, sprintf('%s / %s', $which, $why->kind()->value));
        }
    }
});

// `settled` is stood in for and not judged: each case is a payload the contract does not describe, which no stack sends.
it('refuses what it cannot read, rather than drawing a setup that is not finished as finished', function (array $changed): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(whatAStackSaysSetupSettled($changed)))]);

    expect(everythingTheStackSaysSetupSettled(new Setups(new PinnedClients())))->toBe(KindOfObstacle::AnswerCouldNotBeRead->value);
})->with([
    'a setup it has no word for' => [['setup' => 'nearly']],
    'no setup' => [['setup' => null]],
    'a data root that is not text' => [['data_root' => 7]],
    'no protocols' => [['protocols' => null]],
    'no usenet' => [['protocols' => ['torrent' => true]]],
    'a torrent that is not a yes or a no' => [['protocols' => ['usenet' => true, 'torrent' => 'yes']]],
    'no service user' => [['service_user' => 'anna']],
    'no uid' => [['service_user' => ['gid' => 1000, 'from' => 'set']]],
    'a gid below nothing' => [['service_user' => ['uid' => 1000, 'gid' => -1, 'from' => 'set']]],
    'a source it has no word for' => [['service_user' => ['uid' => 1000, 'gid' => 1000, 'from' => 'guessed']]],
    'no source' => [['service_user' => ['uid' => 1000, 'gid' => 1000]]],
]);

it('stands in for a stack with a payload the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('SettledEnvelope', whatAStackSaysSetupSettled()))
        ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n")
        ->and(WhatTheContractAccepts::complaintsAbout('SettledEnvelope', whatAStackSaysSetupSettled(['setup' => 'none', 'data_root' => null])))
        ->toBe([], "The payload with no data root this suite stands in for a stack with is not one a stack would send.\n");
});
