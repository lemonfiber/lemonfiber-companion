<?php

declare(strict_types=1);

use Modules\Kernel\Api\ADownloadItWouldInterrupt;
use Modules\Kernel\Api\AskingWhatAStopWouldInterrupt;
use Modules\Kernel\Api\Form;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\TheDownloadsComingDown;
use Modules\Kernel\Api\WhatAStopReaches;
use Modules\Sdk\Api\Forewarners;
use Modules\Sdk\Api\PinnedClients;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AStackThatSaysWhatIsComingDown;
use Tests\Support\TheHealthScreenOfTheLoft;
use Tests\Support\TheWordCarriedOut;
use Tests\Support\WhatTheContractAccepts;

// The AskingWhatAStopWouldInterrupt contract, run against the adapter and against the fake.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/**
 * The payload a stack sends for a film over Usenet at forty and a show by torrent at ninety-four, or the downloads given.
 *
 * @param  list<mixed>|null     $downloads
 * @return array<string, mixed>
 */
function whatAStackSaysIsComingDown(?array $downloads = null): array
{
    return ['api_version' => 1, 'kind' => 'in-flight', 'data' => ['downloads' => $downloads ?? [
        ['protocol' => 'usenet', 'name' => 'A film', 'progress' => 40],
        ['protocol' => 'torrent', 'name' => 'A show', 'progress' => 94],
    ]]];
}

/**
 * Both ways of asking, each set up to produce the same answer.
 *
 * @return array<string, Closure(): AskingWhatAStopWouldInterrupt>
 */
function everyWayOfAskingWhatIsComingDown(MockResponse $answered, ?Obstacle $why = null): array
{
    return [
        'the fake' => static fn(): AskingWhatAStopWouldInterrupt => $why instanceof Obstacle
            ? AStackThatSaysWhatIsComingDown::met($why)
            : AStackThatSaysWhatIsComingDown::twoDownloads(),
        'the adapter' => static function () use ($answered): AskingWhatAStopWouldInterrupt {
            MockClient::destroyGlobal();
            MockClient::global([$answered]);

            return new Forewarners(new PinnedClients());
        },
    ];
}

/** Everything one way of asking comes away with, folded to text. */
function everythingTheStackSaysIsComingDown(AskingWhatAStopWouldInterrupt $asking, ?WhatAStopReaches $reach = null): string
{
    return $asking->inside(TheHealthScreenOfTheLoft::theStackBeingLookedAt(), Session::of('a-session-not-a-secret'), $reach ?? WhatAStopReaches::theServices(ServiceId::called('sabnzbd')))->either(
        said: static fn(TheDownloadsComingDown $coming): TheWordCarriedOut => new TheWordCarriedOut(implode('; ', array_map(
            static fn(ADownloadItWouldInterrupt $download): string => sprintf('%s %d%% by %s', $download->name, $download->progress, $download->by->value),
            iterator_to_array($coming, preserve_keys: false),
        ))),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->value),
    )->said;
}

it('comes away with each download still coming down, how far along it is and which kind of client holds it', function (): void {
    $answered = MockResponse::make((string) json_encode(whatAStackSaysIsComingDown()));
    $said = [];

    foreach (everyWayOfAskingWhatIsComingDown($answered) as $which => $make) {
        $said[$which] = everythingTheStackSaysIsComingDown($make());
    }

    expect($said['the adapter'])->toBe($said['the fake'])
        ->and($said['the fake'])->toBe('A film 40% by usenet; A show 94% by torrent');
});

it('asks about exactly the forms and the services the stop names', function (WhatAStopReaches $reach, array $query): void {
    MockClient::destroyGlobal();
    $mock = MockClient::global([MockResponse::make((string) json_encode(whatAStackSaysIsComingDown([])))]);

    expect(everythingTheStackSaysIsComingDown(new Forewarners(new PinnedClients()), $reach))->toBe('');

    $sent = $mock->getLastPendingRequest();

    expect($sent?->getUrl())->toEndWith('/api/in-flight')
        ->and(array_filter($sent?->query()->all() ?? [], static fn(mixed $asked): bool => $asked !== []))->toBe($query);
})->with([
    'a form' => [WhatAStopReaches::theForm(Form::called('downloads')), ['forms' => ['downloads']]],
    'two services' => [WhatAStopReaches::theServices(ServiceId::called('sabnzbd'), ServiceId::called('qbittorrent')), ['services' => ['sabnzbd', 'qbittorrent']]],
]);

it('reads a way of downloading it has no word for as a download client', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(whatAStackSaysIsComingDown([['protocol' => 'carrier-pigeon', 'name' => 'A film', 'progress' => 0]])))]);

    expect(everythingTheStackSaysIsComingDown(new Forewarners(new PinnedClients())))->toBe('A film 0% by another-way');
});

it('tells a session that has ended from a stack that is not answering', function (): void {
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::of(KindOfObstacle::CredentialWasRefused)],
        [MockResponse::make('{"error":"gone"}', 500), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
        [MockResponse::make('not json at all'), Obstacle::of(KindOfObstacle::AnswerCouldNotBeRead)],
    ];

    foreach ($table as [$answered, $why]) {
        foreach (everyWayOfAskingWhatIsComingDown($answered, $why) as $which => $make) {
            expect(everythingTheStackSaysIsComingDown($make()))->toBe($why->kind()->value, sprintf('%s / %s', $which, $why->kind()->value));
        }
    }
});

// `in-flight` is stood in for and not judged: each case is a payload the contract does not describe, which no stack sends.
it('refuses what it cannot read rather than naming a download it does not describe', function (mixed $downloads): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'in-flight', 'data' => ['downloads' => $downloads]]))]);

    expect(everythingTheStackSaysIsComingDown(new Forewarners(new PinnedClients())))->toBe(KindOfObstacle::AnswerCouldNotBeRead->value);
})->with([
    'no list' => [null],
    'a table' => [['a' => 1]],
    'a download that is not a table' => [['A film']],
    'no name' => [[['protocol' => 'usenet', 'progress' => 4]]],
    'a blank name' => [[['protocol' => 'usenet', 'name' => ' ', 'progress' => 4]]],
    'no progress' => [[['protocol' => 'usenet', 'name' => 'A film']]],
    'progress past all of it' => [[['protocol' => 'usenet', 'name' => 'A film', 'progress' => 101]]],
    'no protocol' => [[['name' => 'A film', 'progress' => 4]]],
]);

it('stands in for a stack with a payload the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('InFlightEnvelope', whatAStackSaysIsComingDown()))->toBe([]);
});
