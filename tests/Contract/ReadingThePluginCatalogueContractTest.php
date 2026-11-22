<?php

declare(strict_types=1);

use Modules\Kernel\Api\ACatalogueEntry;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\ASettingThatReaches;
use Modules\Kernel\Api\ExtendingTheStack;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\ThePluginCatalogue;
use Modules\Kernel\Api\WhatWasFoundOfThePluginCatalogue;
use Modules\Sdk\Api\Extenders;
use Modules\Sdk\Api\PinnedClients;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\PendingRequest;
use Tests\Support\APluginAsItArrives;
use Tests\Support\Fakes\AStackThatExtendsItself;
use Tests\Support\Fakes\SequencedEntropy;
use Tests\Support\WhatTheContractAccepts;

// Reading the stack's plugin catalogue, run against the adapter and against
// the fake: listed, switched off and not reached are each an answer of their
// own, and an answer this app cannot read is what stood in the way, never an
// empty catalogue.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** The stack whose catalogue is read. */
function aStackWithACatalogue(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** The session the catalogue is read on. */
function theSessionTheCatalogueIsReadOn(): Session
{
    return Session::of('a-session-not-a-secret');
}

/**
 * The `plugin-catalogue` envelope carrying this report.
 *
 * @param array<mixed> $report
 * @return array<string, mixed>
 */
function aCatalogueAnswer(array $report): array
{
    return ['api_version' => 1, 'kind' => 'plugin-catalogue', 'data' => $report];
}

/** @return array<string, mixed> the listed report: Bazarr offered, Tdarr installed at an older version, one entry not listed */
function aListedCatalogueReport(): array
{
    return [
        'state' => 'listed',
        'published' => 1_791_579_600,
        'serial' => 12,
        'unreadable' => 1,
        'entries' => [
            ['id' => 'bazarr', 'name' => 'Bazarr', 'description' => 'Fetches subtitles', 'version' => '1.4.0', 'without_it' => 'Nothing comes with subtitles'],
            ['id' => 'tdarr', 'name' => 'Tdarr', 'description' => 'Transcodes what comes in', 'version' => '2.2.0', 'without_it' => 'Files stay as they arrive', 'installed' => ['version' => '2.1.0', 'newer' => true]],
        ],
    ];
}

/** @return array<string, mixed> */
function aSwitchedOffCatalogueReport(string $action = 'config-set'): array
{
    return ['state' => 'off', 'entries' => [], 'setting' => ['key' => 'LEMONFIBER_REACH_CATALOGUE', 'value' => 'on', 'action' => $action]];
}

/** @return array<string, mixed> */
function aFailedCatalogueReport(): array
{
    return ['state' => 'failed', 'entries' => [], 'failure' => ['code' => 'catalogue_unverified', 'summary' => 'The index signature did not verify']];
}

/** The catalogue the fake answers with for the same report. */
function theCatalogueTheReportCarries(string $state): ThePluginCatalogue
{
    return match ($state) {
        'listed' => ThePluginCatalogue::listed(
            Instant::atEpochSeconds(1_791_579_600),
            12,
            1,
            ACatalogueEntry::offered('bazarr', 'Bazarr', 'Fetches subtitles', '1.4.0', 'Nothing comes with subtitles'),
            ACatalogueEntry::installedHere('tdarr', 'Tdarr', 'Transcodes what comes in', '2.2.0', 'Files stay as they arrive', '2.1.0', newer: true),
        ),
        'off' => ThePluginCatalogue::switchedOff(ASettingThatReaches::named('LEMONFIBER_REACH_CATALOGUE', 'on', 'config-set')),
        default => ThePluginCatalogue::unreached('catalogue_unverified', 'The index signature did not verify'),
    };
}

/** Everything a reading of the catalogue says, one line a fact. */
function everythingTheCatalogueSays(WhatWasFoundOfThePluginCatalogue $found): string
{
    return implode("\n", $found->either(
        found: static fn(ThePluginCatalogue $catalogue): ArrayObject => new ArrayObject(everythingOneCatalogueSays($catalogue)),
        met: static fn(Obstacle $why): ArrayObject => new ArrayObject([$why->kind()->value]),
    )->getArrayCopy());
}

/**
 * What one catalogue says, one line a fact.
 *
 * @return array<int, string>
 */
function everythingOneCatalogueSays(ThePluginCatalogue $catalogue): array
{
    return $catalogue->either(
        listed: static fn(Instant $published, int $serial, int $unreadable, array $entries): ArrayObject => new ArrayObject([
            sprintf('listed %d/%d/%d', $published->epochSeconds(), $serial, $unreadable),
            ...array_map(static fn(ACatalogueEntry $entry): string => sprintf('entry %s/%s/%s/%s/%s/%s/%s', $entry->id(), $entry->name(), $entry->description(), $entry->version(), $entry->withoutIt(), $entry->installed(), $entry->isNewer() ? 'newer' : 'not newer'), $entries),
        ]),
        switchedOff: static fn(ASettingThatReaches $setting): ArrayObject => new ArrayObject([sprintf('off %s=%s', $setting->key(), $setting->value())]),
        unreached: static fn(string $code, string $summary): ArrayObject => new ArrayObject([sprintf('failed %s: %s', $code, $summary)]),
    )->getArrayCopy();
}

/**
 * Both ways of reading it, each answering with the catalogue the report carries.
 *
 * @return array<string, Closure(): ExtendingTheStack>
 */
function everyWayOfReadingTheCatalogue(string $state, MockResponse $answered): array
{
    return [
        'the fake' => static fn(): ExtendingTheStack => AStackThatExtendsItself::listing(APluginAsItArrives::theListing())->cataloguing(theCatalogueTheReportCarries($state)),
        'the adapter' => static function () use ($answered): ExtendingTheStack {
            MockClient::destroyGlobal();
            MockClient::global([$answered]);

            return new Extenders(new PinnedClients(), SequencedEntropy::counting());
        },
    ];
}

it('reads each state of the catalogue as an answer of its own, each in the shape the contract declares', function (string $state, array $report, string $said): void {
    expect(WhatTheContractAccepts::complaintsAbout('PluginCatalogueEnvelope', aCatalogueAnswer($report)))->toBe([]);

    foreach (everyWayOfReadingTheCatalogue($state, MockResponse::make((string) json_encode(aCatalogueAnswer($report)))) as $which => $make) {
        expect(everythingTheCatalogueSays($make()->catalogueOf(aStackWithACatalogue(), theSessionTheCatalogueIsReadOn())))->toBe($said, $which);
    }
})->with([
    'listed' => ['listed', aListedCatalogueReport(), implode("\n", [
        'listed 1791579600/12/1',
        'entry bazarr/Bazarr/Fetches subtitles/1.4.0/Nothing comes with subtitles//not newer',
        'entry tdarr/Tdarr/Transcodes what comes in/2.2.0/Files stay as they arrive/2.1.0/newer',
    ])],
    'switched off' => ['off', aSwitchedOffCatalogueReport(), 'off LEMONFIBER_REACH_CATALOGUE=on'],
    'not reached' => ['failed', aFailedCatalogueReport(), 'failed catalogue_unverified: The index signature did not verify'],
]);

it('reads a catalogue listing nothing as listed and empty', function (): void {
    $empty = ['state' => 'listed', 'published' => 1_791_579_600, 'serial' => 12, 'unreadable' => 0, 'entries' => []];
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(aCatalogueAnswer($empty)))]);

    expect(WhatTheContractAccepts::complaintsAbout('PluginCatalogueEnvelope', aCatalogueAnswer($empty)))->toBe([])
        ->and(everythingTheCatalogueSays(new Extenders(new PinnedClients(), SequencedEntropy::counting())->catalogueOf(aStackWithACatalogue(), theSessionTheCatalogueIsReadOn())))->toBe('listed 1791579600/12/0');
});

it('asks the catalogue at its endpoint, with nothing sent and no key', function (): void {
    $sent = [];
    MockClient::destroyGlobal();
    MockClient::global([
        '*' => static function (PendingRequest $asked) use (&$sent): MockResponse {
            $sent[] = [$asked->getRequest()->resolveEndpoint(), $asked->body()?->all(), $asked->headers()->get('Idempotency-Key') !== null];

            return MockResponse::make((string) json_encode(aCatalogueAnswer(aFailedCatalogueReport())));
        },
    ]);

    new Extenders(new PinnedClients(), SequencedEntropy::counting())->catalogueOf(aStackWithACatalogue(), theSessionTheCatalogueIsReadOn());

    expect($sent)->toBe([['/api/plugins/catalogue', null, false]]);
});

it('reads an answer it cannot take as what stood in the way, never as an empty catalogue', function (array $report): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(aCatalogueAnswer($report)))]);

    expect(everythingTheCatalogueSays(new Extenders(new PinnedClients(), SequencedEntropy::counting())->catalogueOf(aStackWithACatalogue(), theSessionTheCatalogueIsReadOn())))
        ->toBe(KindOfObstacle::AnswerCouldNotBeRead->value);
})->with([
    'a state it has no case for' => [['state' => 'paused', 'entries' => []]],
    'listed with no date' => [array_diff_key(aListedCatalogueReport(), ['published' => true])],
    'listed with no serial' => [array_diff_key(aListedCatalogueReport(), ['serial' => true])],
    'listed with no count of what it could not list' => [array_diff_key(aListedCatalogueReport(), ['unreadable' => true])],
    'listed with no entries' => [array_diff_key(aListedCatalogueReport(), ['entries' => true])],
    'an entry with a blank name' => [['state' => 'listed', 'published' => 1, 'serial' => 1, 'unreadable' => 0, 'entries' => [['id' => 'bazarr', 'name' => ' ', 'description' => 'd', 'version' => '1', 'without_it' => 'w']]]],
    'an entry that is not a table' => [['state' => 'listed', 'published' => 1, 'serial' => 1, 'unreadable' => 0, 'entries' => ['bazarr']]],
    'an installed version with no word on newer' => [['state' => 'listed', 'published' => 1, 'serial' => 1, 'unreadable' => 0, 'entries' => [['id' => 'b', 'name' => 'B', 'description' => 'd', 'version' => '1', 'without_it' => 'w', 'installed' => ['version' => '1']]]]],
    'an installed version that is not a table' => [['state' => 'listed', 'published' => 1, 'serial' => 1, 'unreadable' => 0, 'entries' => [['id' => 'b', 'name' => 'B', 'description' => 'd', 'version' => '1', 'without_it' => 'w', 'installed' => '1']]]],
    'off with no setting' => [['state' => 'off', 'entries' => []]],
    'off with a setting written by an act this app does not know' => [aSwitchedOffCatalogueReport('settings-set')],
    'off with a setting with no value' => [['state' => 'off', 'entries' => [], 'setting' => ['key' => 'LEMONFIBER_OFFLINE', 'action' => 'config-set']]],
    'failed with no failure' => [['state' => 'failed', 'entries' => []]],
    'failed with no summary' => [['state' => 'failed', 'entries' => [], 'failure' => ['code' => 'catalogue_unverified']]],
]);

it('says a stack that refuses the read, or cannot be reached, is what stood in the way', function (MockResponse $answered, KindOfObstacle $why): void {
    MockClient::destroyGlobal();
    MockClient::global([$answered]);

    expect(everythingTheCatalogueSays(new Extenders(new PinnedClients(), SequencedEntropy::counting())->catalogueOf(aStackWithACatalogue(), theSessionTheCatalogueIsReadOn())))
        ->toBe($why->value);
})->with([
    'a session that has ended' => [fn(): MockResponse => MockResponse::make('{"error":"no"}', 401), KindOfObstacle::CredentialWasRefused],
    'a stack that did not answer' => [fn(): MockResponse => MockResponse::make('{"error":"gone"}', 500), KindOfObstacle::StackDidNotAnswer],
    'an answer that is not an envelope' => [fn(): MockResponse => MockResponse::make('not json at all'), KindOfObstacle::AnswerCouldNotBeRead],
]);

it('says a stack whose fake offers no catalogue is not one to read it from', function (): void {
    expect(everythingTheCatalogueSays(AStackThatExtendsItself::listing(APluginAsItArrives::theListing())->catalogueOf(aStackWithACatalogue(), theSessionTheCatalogueIsReadOn())))
        ->toBe(KindOfObstacle::NotOnThisStack->value)
        ->and(everythingTheCatalogueSays(AStackThatExtendsItself::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))->catalogueOf(aStackWithACatalogue(), theSessionTheCatalogueIsReadOn())))
        ->toBe(KindOfObstacle::StackDidNotAnswer->value);
});
