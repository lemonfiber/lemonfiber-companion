<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\AServiceDropped;
use Modules\Kernel\Api\Cataloguing;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowMuchItMatters;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheCatalogue;
use Modules\Kernel\Api\WhatAServiceIsFor;
use Modules\Kernel\Api\WhatTheRefusalNamed;
use Modules\Kernel\Api\WhatTheServicesAreFor;
use Modules\Kernel\Api\WhatWasDropped;
use Modules\Sdk\Api\Cataloguers;
use Modules\Sdk\Api\PinnedClients;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AStackThatCatalogues;
use Tests\Support\WhatTheContractAccepts;

// The Cataloguing contract, run against the adapter and against the fake.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** The stack whose catalogue is read. */
function aStackWhoseCatalogueIsRead(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('e', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('f', Fingerprint::CHARACTERS)),
    );
}

/**
 * What a stack answers about its catalogue, changed where a case says, and
 * without the fields a case leaves out altogether.
 *
 * @param  array<mixed>         $changed
 * @param  list<string>         $without
 * @return array<string, mixed>
 */
function whatAStackSaysOfItsCatalogue(array $changed = [], array $without = []): array
{
    $data = [
        'services' => [
            ['id' => 'sonarr', 'name' => 'Sonarr', 'describes' => 'Finds and fetches television', 'without_it' => 'New episodes stop arriving', 'criticality' => 'important'],
            ['id' => 'bazarr', 'name' => 'Bazarr', 'describes' => 'Finds subtitles', 'without_it' => 'Nothing has subtitles', 'criticality' => 'optional'],
        ],
        'removed' => [
            ['id' => 'ombi', 'removed_in' => '0.8.0', 'reason' => 'Requests moved into the household app', 'replaced_by' => 'jellyseerr'],
            ['id' => 'lidarr', 'removed_in' => '0.9.0', 'reason' => 'Music is handled elsewhere', 'replaced_by' => null],
        ],
        ...$changed,
    ];

    foreach ($without as $field) {
        unset($data[$field]);
    }

    return ['api_version' => 1, 'kind' => 'catalogue', 'data' => $data];
}

/** The catalogue that payload stands for, as the fake is handed it. */
function theSameCatalogue(): TheCatalogue
{
    return TheCatalogue::of(
        WhatTheServicesAreFor::these(
            WhatAServiceIsFor::declared(ServiceId::called('sonarr'), 'Sonarr', 'Finds and fetches television', 'New episodes stop arriving', HowMuchItMatters::Important),
            WhatAServiceIsFor::declared(ServiceId::called('bazarr'), 'Bazarr', 'Finds subtitles', 'Nothing has subtitles', HowMuchItMatters::Optional),
        ),
        WhatWasDropped::these(
            AServiceDropped::replaced(ServiceId::called('ombi'), '0.8.0', 'Requests moved into the household app', 'jellyseerr'),
            AServiceDropped::went(ServiceId::called('lidarr'), '0.9.0', 'Music is handled elsewhere'),
        ),
    );
}

/**
 * Both ways of reading a catalogue, each set up to say the same.
 *
 * @return array<string, Closure(): Cataloguing>
 */
function everyWayOfReadingACatalogue(MockResponse $answered, TheCatalogue $same): array
{
    return [
        'the fake' => static fn(): Cataloguing => AStackThatCatalogues::with($same),
        'the adapter' => static function () use ($answered): Cataloguing {
            MockClient::destroyGlobal();
            MockClient::global([$answered]);

            return new Cataloguers(new PinnedClients());
        },
    ];
}

/** One line carried out of an `either()` arm. */
final readonly class WhatTheCatalogueWasSaid
{
    public function __construct(public string $said) {}
}

/** What reading a catalogue came to, every part folded to one line. */
function whatTheStacksCatalogueComesTo(Cataloguing $catalogue): string
{
    return $catalogue->describedOn(aStackWhoseCatalogueIsRead(), Session::of('a-session-not-a-secret'))->either(
        catalogue: static function (TheCatalogue $read): WhatTheCatalogueWasSaid {
            $said = [];

            foreach ($read->services() as $service) {
                $said[] = sprintf('%s (%s): %s; without it %s; %s', $service->name(), $service->service()->named(), $service->describes(), $service->withoutIt(), $service->matters()->value);
            }

            foreach ($read->dropped() as $dropped) {
                $said[] = sprintf(
                    'dropped %s in %s: %s, %s',
                    $dropped->service()->named(),
                    $dropped->removedIn(),
                    $dropped->reason(),
                    $dropped->replacement(
                        by: static fn(string $by): WhatTheCatalogueWasSaid => new WhatTheCatalogueWasSaid(sprintf('replaced by %s', $by)),
                        nothing: static fn(): WhatTheCatalogueWasSaid => new WhatTheCatalogueWasSaid('not replaced'),
                    )->said,
                );
            }

            return new WhatTheCatalogueWasSaid(implode(' | ', $said));
        },
        refused: static fn(ARefusalInItsWords $why): WhatTheCatalogueWasSaid => new WhatTheCatalogueWasSaid(sprintf(
            'refused: %s | %s | %s',
            $why->summary(),
            $why->meaning(),
            $why->named()->forTheOperator(),
        )),
        met: static fn(Obstacle $why): WhatTheCatalogueWasSaid => new WhatTheCatalogueWasSaid($why->kind()->name),
    )->said;
}

/**
 * Every problem the stack answers the catalogue with where it cannot read its own manifest.
 *
 * Keyed by what each is; each holds its code, severity, state, summary,
 * meaning, remedy and detail, as the stack words them.
 *
 * @return array<string, array{string, string, string, string, string, string, string}>
 */
function everyManifestTheStackCannotRead(): array
{
    return [
        'no stack there' => ['STACK-1', 'error', 'guided', 'No stack was found at /srv/stack', 'A stack directory holds a stack.toml beside its compose files. Without one there is nothing describing what would be started.', 'Point at a directory containing stack.toml', 'No such file or directory (os error 2)'],
        'written for another version' => ['STACK-2', 'error', 'guided', 'This stack was written for a different version of lemonfiber', 'Stacks and lemonfiber are versioned separately so each can move on its own. This pairing does not line up, and guessing at the difference would fail later in a way that looks unrelated.', 'Update lemonfiber, or point at a stack this version reads', 'the stack asks for lemonfiber 2, and this is 1'],
        'not intact' => ['STACK-3', 'critical', 'unknown', 'This build of lemonfiber is not intact', 'The stack that ships inside the binary is missing, which the build is supposed to make impossible.', 'Send a diagnostic bundle so this can be investigated', ''],
        'things that cannot work' => ['STACK-6', 'error', 'guided', 'This stack describes 2 things that cannot work', 'The file is well-formed, so this is not a typo — it says things about itself that contradict each other, and starting it would fail somewhere unrelated.', 'Fix the faults listed below, all of which were found in one pass', "sonarr needs qbittorrent, which is not declared
radarr listens on 7878, which sonarr already holds"],
        'not written in the format' => ['STACK-7', 'error', 'guided', 'This stack file could not be read', 'A stack.toml is written in a strict format, and this one breaks it — so nothing in the file has been read at all. The detail below is where the reader stopped, and that line is where the answer is.', 'Fix the file at the line named below', 'expected `=`, found newline at line 3 column 9'],
        'names this build does not know' => ['STACK-8', 'error', 'guided', 'This stack declares 2 names this build does not know', 'The file is well-formed and says things about itself in words this version has no meaning for — usually a stack from a newer lemonfiber, or a fork that has added something of its own. Starting it would quietly leave out whatever was named.', 'Update lemonfiber, or change the names listed below to ones it knows', "form.tiny
service.whisparr"],
    ];
}

/**
 * One of those problems, as the error envelope the stack answers with.
 *
 * @return array<string, mixed>
 */
function aManifestTheStackCannotRead(string $which): array
{
    [$code, $severity, $state, $summary, $meaning, $remedy, $detail] = everyManifestTheStackCannotRead()[$which];
    $problem = ['code' => $code, 'severity' => $severity, 'state' => $state, 'summary' => $summary, 'meaning' => $meaning, 'remedies' => [['action' => $remedy]]];

    return ['api_version' => 1, 'kind' => 'error', 'data' => $detail === '' ? $problem : [...$problem, 'detail' => $detail]];
}

it('reads what each service is for and what the house goes without, and what became of each it dropped', function (): void {
    $answered = MockResponse::make((string) json_encode(whatAStackSaysOfItsCatalogue()));

    foreach (everyWayOfReadingACatalogue($answered, theSameCatalogue()) as $which => $build) {
        expect(whatTheStacksCatalogueComesTo($build()))->toBe(implode(' | ', [
            'Sonarr (sonarr): Finds and fetches television; without it New episodes stop arriving; important',
            'Bazarr (bazarr): Finds subtitles; without it Nothing has subtitles; optional',
            'dropped ombi in 0.8.0: Requests moved into the household app, replaced by jellyseerr',
            'dropped lidarr in 0.9.0: Music is handled elsewhere, not replaced',
        ]), $which);
    }
});

it('reads a dropped service with nothing named in its place as not replaced, whether null or left out', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(whatAStackSaysOfItsCatalogue([
        'removed' => [['id' => 'lidarr', 'removed_in' => '0.9.0', 'reason' => 'Music is handled elsewhere']],
    ])))]);

    expect(whatTheStacksCatalogueComesTo(new Cataloguers(new PinnedClients())))->toEndWith('dropped lidarr in 0.9.0: Music is handled elsewhere, not replaced');
});

it('reads a stack that declares nothing and has dropped nothing as an answer, not an obstacle', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(whatAStackSaysOfItsCatalogue(['services' => [], 'removed' => []])))]);

    expect(whatTheStacksCatalogueComesTo(new Cataloguers(new PinnedClients())))->toBe('');
});

it('a catalogue this app cannot read is a stack that did not answer, never a shorter catalogue', function (array $changed): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(whatAStackSaysOfItsCatalogue($changed)))]);

    expect(whatTheStacksCatalogueComesTo(new Cataloguers(new PinnedClients())))->toEqual(KindOfObstacle::StackDidNotAnswer->name);
})->with([
    'no services' => [['services' => null]],
    'nothing dropped said at all' => [['removed' => 'none']],
    'a service that is not a row' => [['services' => ['sonarr']]],
    'a service with nothing said of the house without it' => [['services' => [['id' => 'sonarr', 'name' => 'Sonarr', 'describes' => 'Finds television', 'without_it' => ' ', 'criticality' => 'core']]]],
    'a service with no description' => [['services' => [['id' => 'sonarr', 'name' => 'Sonarr', 'without_it' => 'No television', 'criticality' => 'core']]]],
    'a service whose name is not text' => [['services' => [['id' => 'sonarr', 'name' => 42, 'describes' => 'Finds television', 'without_it' => 'No television', 'criticality' => 'core']]]],
    'a service mattering in a way this app has no word for' => [['services' => [['id' => 'sonarr', 'name' => 'Sonarr', 'describes' => 'Finds television', 'without_it' => 'No television', 'criticality' => 'vital']]]],
    'a dropped service that is not a row' => [['removed' => ['ombi']]],
    'a dropped service with no reason' => [['removed' => [['id' => 'ombi', 'removed_in' => '0.8.0', 'reason' => ' ']]]],
    'a dropped service with no version' => [['removed' => [['id' => 'ombi', 'reason' => 'It went']]]],
    'a dropped service replaced by a blank' => [['removed' => [['id' => 'ombi', 'removed_in' => '0.8.0', 'reason' => 'It went', 'replaced_by' => ' ']]]],
]);

it('a catalogue leaving out either list is a stack that did not answer', function (string $field): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(whatAStackSaysOfItsCatalogue(without: [$field])))]);

    expect(whatTheStacksCatalogueComesTo(new Cataloguers(new PinnedClients())))->toEqual(KindOfObstacle::StackDidNotAnswer->name);
})->with(['services', 'removed']);

it('tells a refused session from a stack that is not answering', function (): void {
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::of(KindOfObstacle::CredentialWasRefused)],
        [MockResponse::make('{"error":"gone"}', 500), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
        [MockResponse::make('not json at all'), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
    ];

    foreach ($table as [$answered, $why]) {
        MockClient::destroyGlobal();
        MockClient::global([$answered]);
        expect(whatTheStacksCatalogueComesTo(new Cataloguers(new PinnedClients())))->toBe($why->kind()->name)
            ->and(whatTheStacksCatalogueComesTo(AStackThatCatalogues::met($why)))->toBe($why->kind()->name);
    }
});

it('a stack that cannot read its own manifest is its refusal, in its words, with what it named', function (): void {
    foreach (everyManifestTheStackCannotRead() as $which => [, , , $summary, $meaning, , $detail]) {
        MockClient::destroyGlobal();
        MockClient::global([MockResponse::make((string) json_encode(aManifestTheStackCannotRead($which)), 500)]);
        $why = ARefusalInItsWords::said($summary, $meaning, $detail === '' ? WhatTheRefusalNamed::nothing() : WhatTheRefusalNamed::as($detail));
        $said = sprintf('refused: %s | %s | %s', $summary, $meaning, $detail);

        expect(whatTheStacksCatalogueComesTo(new Cataloguers(new PinnedClients())))->toBe($said, sprintf('%s, the adapter', $which))
            ->and(whatTheStacksCatalogueComesTo(AStackThatCatalogues::refusing($why)))->toBe($said, sprintf('%s, the fake', $which));
    }
});

it('a manifest problem at a status that says who may ask, or a sentence with no problem around it, is what was met', function (MockResponse $answered, Obstacle $why): void {
    MockClient::destroyGlobal();
    MockClient::global([$answered]);

    expect(whatTheStacksCatalogueComesTo(new Cataloguers(new PinnedClients())))->toBe($why->kind()->name);
})->with([
    'a refused session' => [MockResponse::make((string) json_encode(aManifestTheStackCannotRead('not written in the format')), 401), Obstacle::of(KindOfObstacle::CredentialWasRefused)],
    'an account that may not ask' => [MockResponse::make((string) json_encode(aManifestTheStackCannotRead('not written in the format')), 403), Obstacle::of(KindOfObstacle::NotForThisAccount)],
    'a sentence' => [MockResponse::make('This answer could not be rendered.', 500, ['Content-Type' => 'text/plain']), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
]);

it('asks the catalogue endpoint', function (): void {
    MockClient::destroyGlobal();
    $mock = MockClient::global([MockResponse::make('not json at all')]);

    whatTheStacksCatalogueComesTo(new Cataloguers(new PinnedClients()));

    expect($mock->getLastPendingRequest()?->getUrl())->toEndWith('/api/catalogue');
});

it('the fake remembers the stack it was asked about', function (): void {
    $catalogue = AStackThatCatalogues::with(theSameCatalogue());
    whatTheStacksCatalogueComesTo($catalogue);

    expect($catalogue->askings())->toBe(1)
        ->and($catalogue->askedAbout()?->id()->stored())->toBe(aStackWhoseCatalogueIsRead()->id()->stored());
});

it('stands in for a stack with a payload the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('CatalogueEnvelope', whatAStackSaysOfItsCatalogue()))
        ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n");

    foreach (array_keys(everyManifestTheStackCannotRead()) as $which) {
        expect(WhatTheContractAccepts::complaintsAbout('ErrorEnvelope', aManifestTheStackCannotRead($which)))->toBe([], $which);
    }
});
