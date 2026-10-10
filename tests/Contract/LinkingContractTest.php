<?php

declare(strict_types=1);

use Modules\Kernel\Api\AClaimant;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\ALink;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\Capability;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowItReaches;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Linking;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Services;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheClaimants;
use Modules\Kernel\Api\TheLinks;
use Modules\Kernel\Api\Unfilled;
use Modules\Kernel\Api\WhatNothingFills;
use Modules\Kernel\Api\WhatSettledIt;
use Modules\Kernel\Api\WhatTheRefusalNamed;
use Modules\Kernel\Api\WhoPutItThere;
use Modules\Kernel\Api\WhoSettledIt;
use Modules\Kernel\Api\WhyItWasChosen;
use Modules\Sdk\Api\Linkers;
use Modules\Sdk\Api\PinnedClients;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AStackThatSaysWhatAnswersWhat;
use Tests\Support\TheWordCarriedOut;
use Tests\Support\WhatTheContractAccepts;

// The Linking contract, run against the adapter and against the fake.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** The stack whose wiring is read. */
function aStackWhoseWiringIsRead(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('w', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('f', Fingerprint::CHARACTERS)),
    );
}

/**
 * What a stack answers about its wiring, changed where a case says, and
 * without the fields a case leaves out altogether.
 *
 * @param  array<mixed>         $changed
 * @param  list<string>         $without
 * @return array<string, mixed>
 */
function whatAStackSaysItWires(array $changed = [], array $without = []): array
{
    $data = [
        'wired' => [
            ['by' => 'sonarr', 'reaches' => ['how' => 'asked', 'capability' => 'download-client', 'services' => ['qbittorrent'], 'settled' => ['settled' => 'outright'], 'origins' => ['qbittorrent' => ['origin' => 'bundled']]]],
            ['by' => 'prowlarr', 'reaches' => ['how' => 'asked', 'capability' => 'arr', 'services' => ['sonarr', 'radarr'], 'settled' => ['settled' => 'each'], 'origins' => ['sonarr' => ['origin' => 'bundled'], 'radarr' => ['origin' => 'operator']]]],
            ['by' => 'seerr', 'reaches' => ['how' => 'asked', 'capability' => 'media-server', 'services' => [], 'settled' => ['settled' => 'contested', 'claimants' => ['jellyfin', 'plex']], 'origins' => ['jellyfin' => ['origin' => 'bundled'], 'plex' => ['origin' => 'plugin', 'named' => 'plex']]]],
            ['by' => 'radarr', 'reaches' => ['how' => 'asked', 'capability' => 'indexer', 'services' => ['prowlarr'], 'settled' => ['settled' => 'chosen', 'whose' => 'operator', 'over' => ['jackett'], 'why' => 'Jackett is too slow here'], 'origins' => ['prowlarr' => ['origin' => 'bundled'], 'jackett' => ['origin' => 'unknown', 'why' => 'the record would not read']]]],
            ['by' => 'bazarr', 'reaches' => ['how' => 'asked', 'capability' => 'subtitles', 'services' => ['opensubtitles'], 'settled' => ['settled' => 'chosen', 'whose' => 'stack', 'over' => ['subscene']], 'origins' => []]],
            ['by' => 'lidarr', 'reaches' => ['how' => 'asked', 'capability' => 'music-tagger', 'services' => [], 'settled' => ['settled' => 'unfilled'], 'origins' => []]],
            ['by' => 'jellyfin', 'reaches' => ['how' => 'by-name', 'service' => 'tdarr', 'why' => 'Transcoding runs on the other machine']],
        ],
        'unfilled' => [['by' => 'lidarr', 'capability' => 'music-tagger']],
        ...$changed,
    ];

    foreach ($without as $field) {
        unset($data[$field]);
    }

    return ['api_version' => 1, 'kind' => 'wiring', 'data' => $data];
}

/** A claimant, for the fake. */
function aClaimantOf(string $service, WhoPutItThere $from): AClaimant
{
    return AClaimant::of(ServiceId::called($service), $from);
}

/** The wiring that payload stands for, as the fake is handed it. */
function theSameWiring(): TheLinks
{
    $asked = static fn(string $by, string $capability, Services $services, WhatSettledIt $settled, AClaimant ...$claimants): ALink
        => ALink::from(ServiceId::called($by), HowItReaches::asked(Capability::called($capability), $services, $settled, TheClaimants::these(...$claimants)));

    return TheLinks::of(
        WhatNothingFills::these(Unfilled::of(ServiceId::called('lidarr'), Capability::called('music-tagger'))),
        $asked('sonarr', 'download-client', Services::these(ServiceId::called('qbittorrent')), WhatSettledIt::outright(), aClaimantOf('qbittorrent', WhoPutItThere::bundled())),
        $asked('prowlarr', 'arr', Services::these(ServiceId::called('sonarr'), ServiceId::called('radarr')), WhatSettledIt::each(), aClaimantOf('sonarr', WhoPutItThere::bundled()), aClaimantOf('radarr', WhoPutItThere::operator())),
        $asked('seerr', 'media-server', Services::none(), WhatSettledIt::contested(Services::these(ServiceId::called('jellyfin'), ServiceId::called('plex'))), aClaimantOf('jellyfin', WhoPutItThere::bundled()), aClaimantOf('plex', WhoPutItThere::plugin('plex'))),
        $asked('radarr', 'indexer', Services::these(ServiceId::called('prowlarr')), WhatSettledIt::chosen(Services::these(ServiceId::called('jackett')), WhoSettledIt::Operator, WhyItWasChosen::stated('Jackett is too slow here')), aClaimantOf('prowlarr', WhoPutItThere::bundled()), aClaimantOf('jackett', WhoPutItThere::unknown('the record would not read'))),
        $asked('bazarr', 'subtitles', Services::these(ServiceId::called('opensubtitles')), WhatSettledIt::chosen(Services::these(ServiceId::called('subscene')), WhoSettledIt::Stack, WhyItWasChosen::unstated())),
        $asked('lidarr', 'music-tagger', Services::none(), WhatSettledIt::unfilled()),
        ALink::from(ServiceId::called('jellyfin'), HowItReaches::byName(ServiceId::called('tdarr'), 'Transcoding runs on the other machine')),
    );
}

/**
 * Both ways of reading a wiring, each set up to say the same.
 *
 * @return array<string, Closure(): Linking>
 */
function everyWayOfReadingAWiring(MockResponse $answered, TheLinks $same): array
{
    return [
        'the fake' => static fn(): Linking => AStackThatSaysWhatAnswersWhat::with($same),
        'the adapter' => static function () use ($answered): Linking {
            MockClient::destroyGlobal();
            MockClient::global([$answered]);

            return new Linkers(new PinnedClients());
        },
    ];
}

/** Service names, joined. */
function theServicesNamed(Services $services): string
{
    $named = [];

    foreach ($services as $service) {
        $named[] = $service->named();
    }

    return implode('+', $named);
}

/** How an ask was settled, every arm and its payload as one word. */
function howTheAskSettled(WhatSettledIt $settled): string
{
    return $settled->whichever(
        outright: static fn(): TheWordCarriedOut => new TheWordCarriedOut('outright'),
        each: static fn(): TheWordCarriedOut => new TheWordCarriedOut('each'),
        contested: static fn(Services $claimants): TheWordCarriedOut => new TheWordCarriedOut(sprintf('contested by %s', theServicesNamed($claimants))),
        chosen: static fn(Services $over, WhoSettledIt $whose, WhyItWasChosen $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf(
            'chosen by %s over %s, %s',
            $whose->value,
            theServicesNamed($over),
            $why->saying(
                stated: static fn(string $said): TheWordCarriedOut => new TheWordCarriedOut($said),
                unstated: static fn(): TheWordCarriedOut => new TheWordCarriedOut('no reason'),
            )->said,
        )),
        unfilled: static fn(): TheWordCarriedOut => new TheWordCarriedOut('unfilled'),
    )->said;
}

/** Where every claimant came from, as one word. */
function whereTheClaimantsCameFrom(TheClaimants $claimants): string
{
    $said = [];

    foreach ($claimants as $claimant) {
        $said[] = sprintf('%s=%s', $claimant->service()->named(), $claimant->from()->whichever(
            bundled: static fn(): TheWordCarriedOut => new TheWordCarriedOut('bundled'),
            operator: static fn(): TheWordCarriedOut => new TheWordCarriedOut('operator'),
            plugin: static fn(string $named): TheWordCarriedOut => new TheWordCarriedOut(sprintf('plugin:%s', $named)),
            unknown: static fn(string $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('unknown:%s', $why)),
            overridden: static fn(string $named): TheWordCarriedOut => new TheWordCarriedOut(sprintf('overridden:%s', $named)),
            orphaned: static fn(string $named): TheWordCarriedOut => new TheWordCarriedOut(sprintf('orphaned:%s', $named)),
        )->said);
    }

    return implode(',', $said);
}

/** One link, as one line. */
function oneLinkSaid(ALink $link): string
{
    return sprintf('%s: %s', $link->by()->named(), $link->reaches()->whichever(
        asked: static fn(Capability $capability, Services $services, WhatSettledIt $settled, TheClaimants $claimants): TheWordCarriedOut => new TheWordCarriedOut(sprintf(
            'asks %s, reaches %s, %s [%s]',
            $capability->named(),
            theServicesNamed($services),
            howTheAskSettled($settled),
            whereTheClaimantsCameFrom($claimants),
        )),
        byName: static fn(ServiceId $service, string $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('by name to %s, %s', $service->named(), $why)),
    )->said);
}

/** What reading a wiring came to, every part folded to one line. */
function whatTheStacksWiringComesTo(Linking $linking): string
{
    return $linking->linkedOn(aStackWhoseWiringIsRead(), Session::of('a-session-not-a-secret'))->either(
        links: static function (TheLinks $read): TheWordCarriedOut {
            $said = [];

            foreach ($read as $link) {
                $said[] = oneLinkSaid($link);
            }

            foreach ($read->unfilled() as $unfilled) {
                $said[] = sprintf('unfilled: %s asks %s', $unfilled->asking()->named(), $unfilled->capability()->named());
            }

            return new TheWordCarriedOut(implode(' | ', $said));
        },
        refused: static fn(ARefusalInItsWords $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('refused: %s | %s | %s', $why->summary(), $why->meaning(), $why->named()->forTheOperator())),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->name),
    )->said;
}

/**
 * The stack refusing a wiring it cannot read, as the error envelope it answers with.
 *
 * @return array<string, mixed>
 */
function aWiringTheStackCannotRead(): array
{
    return ['api_version' => 1, 'kind' => 'error', 'data' => [
        'code' => 'PLUGIN-4',
        'severity' => 'error',
        'state' => 'guided',
        'summary' => 'The record of what is installed cannot be read',
        'meaning' => 'What fills each capability depends on what is installed, so nothing can be said about it until the record reads.',
        'remedies' => [['action' => 'Restore the record from a backup']],
        'detail' => '/var/lib/lemonfiber/plugins.json: expected value at line 1 column 1',
    ]];
}

it('reads every link with how it was settled and where each claimant came from, and every capability nothing fills', function (): void {
    $answered = MockResponse::make((string) json_encode(whatAStackSaysItWires()));

    foreach (everyWayOfReadingAWiring($answered, theSameWiring()) as $which => $build) {
        expect(whatTheStacksWiringComesTo($build()))->toBe(implode(' | ', [
            'sonarr: asks download-client, reaches qbittorrent, outright [qbittorrent=bundled]',
            'prowlarr: asks arr, reaches sonarr+radarr, each [sonarr=bundled,radarr=operator]',
            'seerr: asks media-server, reaches , contested by jellyfin+plex [jellyfin=bundled,plex=plugin:plex]',
            'radarr: asks indexer, reaches prowlarr, chosen by operator over jackett, Jackett is too slow here [prowlarr=bundled,jackett=unknown:the record would not read]',
            'bazarr: asks subtitles, reaches opensubtitles, chosen by stack over subscene, no reason []',
            'lidarr: asks music-tagger, reaches , unfilled []',
            'jellyfin: by name to tdarr, Transcoding runs on the other machine',
            'unfilled: lidarr asks music-tagger',
        ]), $which);
    }
});

it('reads a choice whose reason is null as one nobody gave a reason for', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(whatAStackSaysItWires(['wired' => [
        ['by' => 'bazarr', 'reaches' => ['how' => 'asked', 'capability' => 'subtitles', 'services' => ['opensubtitles'], 'settled' => ['settled' => 'chosen', 'whose' => 'stack', 'over' => [], 'why' => null], 'origins' => []]],
    ], 'unfilled' => []])))]);

    expect(whatTheStacksWiringComesTo(new Linkers(new PinnedClients())))->toBe('bazarr: asks subtitles, reaches opensubtitles, chosen by stack over , no reason []');
});

it('reads a stack that asks nothing of its services as an answer, not an obstacle', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(whatAStackSaysItWires(['wired' => [], 'unfilled' => []])))]);

    expect(whatTheStacksWiringComesTo(new Linkers(new PinnedClients())))->toBe('');
});

it('a wiring this app cannot read is an answer it could not read, never a shorter one', function (array $changed): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(whatAStackSaysItWires($changed)))]);

    expect(whatTheStacksWiringComesTo(new Linkers(new PinnedClients())))->toEqual(KindOfObstacle::AnswerCouldNotBeRead->name);
})->with([
    'no links' => [['wired' => null]],
    'a link that is not a row' => [['wired' => ['sonarr']]],
    'a link reaching nothing readable' => [['wired' => [['by' => 'sonarr', 'reaches' => 'qbittorrent']]]],
    'a link from nobody' => [['wired' => [['by' => ' ', 'reaches' => ['how' => 'by-name', 'service' => 'tdarr', 'why' => 'Elsewhere']]]]],
    'a link reached in a way this app has no word for' => [['wired' => [['by' => 'sonarr', 'reaches' => ['how' => 'guessed', 'service' => 'tdarr', 'why' => 'Elsewhere']]]]],
    'a by-name link with no reason' => [['wired' => [['by' => 'jellyfin', 'reaches' => ['how' => 'by-name', 'service' => 'tdarr', 'why' => ' ']]]]],
    'an ask with no capability' => [['wired' => [['by' => 'sonarr', 'reaches' => ['how' => 'asked', 'services' => [], 'settled' => ['settled' => 'unfilled'], 'origins' => []]]]]],
    'an ask reaching a service with no name' => [['wired' => [['by' => 'sonarr', 'reaches' => ['how' => 'asked', 'capability' => 'download-client', 'services' => [' '], 'settled' => ['settled' => 'outright'], 'origins' => []]]]]],
    'an ask settled in a way this app has no word for' => [['wired' => [['by' => 'sonarr', 'reaches' => ['how' => 'asked', 'capability' => 'download-client', 'services' => [], 'settled' => ['settled' => 'first-installed'], 'origins' => []]]]]],
    'an ask settled by nothing readable' => [['wired' => [['by' => 'sonarr', 'reaches' => ['how' => 'asked', 'capability' => 'download-client', 'services' => [], 'settled' => 'outright', 'origins' => []]]]]],
    'a contest naming no claimants' => [['wired' => [['by' => 'seerr', 'reaches' => ['how' => 'asked', 'capability' => 'media-server', 'services' => [], 'settled' => ['settled' => 'contested'], 'origins' => []]]]]],
    'a choice made by somebody this app has no word for' => [['wired' => [['by' => 'radarr', 'reaches' => ['how' => 'asked', 'capability' => 'indexer', 'services' => [], 'settled' => ['settled' => 'chosen', 'whose' => 'plugin', 'over' => []], 'origins' => []]]]]],
    'a choice with a blank reason' => [['wired' => [['by' => 'radarr', 'reaches' => ['how' => 'asked', 'capability' => 'indexer', 'services' => [], 'settled' => ['settled' => 'chosen', 'whose' => 'operator', 'over' => [], 'why' => ' '], 'origins' => []]]]]],
    'origins that are not a table' => [['wired' => [['by' => 'sonarr', 'reaches' => ['how' => 'asked', 'capability' => 'download-client', 'services' => [], 'settled' => ['settled' => 'unfilled'], 'origins' => 'bundled']]]]],
    'a claimant whose origin is not a table' => [['wired' => [['by' => 'sonarr', 'reaches' => ['how' => 'asked', 'capability' => 'download-client', 'services' => [], 'settled' => ['settled' => 'unfilled'], 'origins' => ['qbittorrent' => 'bundled']]]]]],
    'a claimant from somewhere this app has no word for' => [['wired' => [['by' => 'sonarr', 'reaches' => ['how' => 'asked', 'capability' => 'download-client', 'services' => [], 'settled' => ['settled' => 'unfilled'], 'origins' => ['qbittorrent' => ['origin' => 'elsewhere']]]]]]],
    'an unfilled ask that is not a row' => [['unfilled' => ['lidarr']]],
    'an unfilled ask naming no capability' => [['unfilled' => [['by' => 'lidarr']]]],
]);

it('a wiring leaving out either list is an answer it could not read', function (string $field): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(whatAStackSaysItWires(without: [$field])))]);

    expect(whatTheStacksWiringComesTo(new Linkers(new PinnedClients())))->toEqual(KindOfObstacle::AnswerCouldNotBeRead->name);
})->with(['wired', 'unfilled']);

it('tells a refused session from a stack that is not answering', function (): void {
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::of(KindOfObstacle::CredentialWasRefused)],
        [MockResponse::make('{"error":"gone"}', 500), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
        [MockResponse::make('not json at all'), Obstacle::of(KindOfObstacle::AnswerCouldNotBeRead)],
    ];

    foreach ($table as [$answered, $why]) {
        MockClient::destroyGlobal();
        MockClient::global([$answered]);
        expect(whatTheStacksWiringComesTo(new Linkers(new PinnedClients())))->toBe($why->kind()->name)
            ->and(whatTheStacksWiringComesTo(AStackThatSaysWhatAnswersWhat::met($why)))->toBe($why->kind()->name);
    }
});

it('a wiring the stack cannot read is its refusal, in its words, with what it named', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(aWiringTheStackCannotRead()), 500)]);
    $summary = 'The record of what is installed cannot be read';
    $meaning = 'What fills each capability depends on what is installed, so nothing can be said about it until the record reads.';
    $detail = '/var/lib/lemonfiber/plugins.json: expected value at line 1 column 1';
    $said = sprintf('refused: %s | %s | %s', $summary, $meaning, $detail);

    expect(whatTheStacksWiringComesTo(new Linkers(new PinnedClients())))->toBe($said)
        ->and(whatTheStacksWiringComesTo(AStackThatSaysWhatAnswersWhat::refusing(ARefusalInItsWords::said($summary, $meaning, WhatTheRefusalNamed::as($detail)))))->toBe($said);
});

it('asks the wiring endpoint', function (): void {
    MockClient::destroyGlobal();
    $mock = MockClient::global([MockResponse::make('not json at all')]);

    whatTheStacksWiringComesTo(new Linkers(new PinnedClients()));

    expect($mock->getLastPendingRequest()?->getUrl())->toEndWith('/api/wiring');
});

it('the fake remembers the stack it was asked about', function (): void {
    $linking = AStackThatSaysWhatAnswersWhat::with(theSameWiring());
    whatTheStacksWiringComesTo($linking);

    expect($linking->askings())->toBe(1)
        ->and($linking->askedAbout()?->id()->stored())->toBe(aStackWhoseWiringIsRead()->id()->stored());
});

it('stands in for a stack with a payload the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('WiringEnvelope', whatAStackSaysItWires()))
        ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n")
        ->and(WhatTheContractAccepts::complaintsAbout('ErrorEnvelope', aWiringTheStackCannotRead()))->toBe([]);
});
