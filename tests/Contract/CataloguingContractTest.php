<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AServiceDropped;
use Modules\Kernel\Api\Cataloguing;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowMuchItMatters;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheCatalogue;
use Modules\Kernel\Api\WhatAServiceIsFor;
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
        met: static fn(Obstacle $why): WhatTheCatalogueWasSaid => new WhatTheCatalogueWasSaid($why->name),
    )->said;
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

    expect(whatTheStacksCatalogueComesTo(new Cataloguers(new PinnedClients())))->toBe(Obstacle::StackDidNotAnswer->name);
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

    expect(whatTheStacksCatalogueComesTo(new Cataloguers(new PinnedClients())))->toBe(Obstacle::StackDidNotAnswer->name);
})->with(['services', 'removed']);

it('tells a refused session from a stack that is not answering', function (): void {
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::CredentialWasRefused],
        [MockResponse::make('{"error":"gone"}', 500), Obstacle::StackDidNotAnswer],
        [MockResponse::make('not json at all'), Obstacle::StackDidNotAnswer],
    ];

    foreach ($table as [$answered, $why]) {
        MockClient::destroyGlobal();
        MockClient::global([$answered]);
        expect(whatTheStacksCatalogueComesTo(new Cataloguers(new PinnedClients())))->toBe($why->name)
            ->and(whatTheStacksCatalogueComesTo(AStackThatCatalogues::met($why)))->toBe($why->name);
    }
});

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
});
