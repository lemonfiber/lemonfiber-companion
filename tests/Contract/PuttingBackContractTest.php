<?php

declare(strict_types=1);

use Modules\Kernel\Api\ACopy;
use Modules\Kernel\Api\ACopyPutBack;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\ARelocation;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowPuttingItBackIsGoing;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\PuttingBack;
use Modules\Kernel\Api\ScopeOfACopy;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\WhatACopyHolds;
use Modules\Kernel\Api\WhatPuttingItBackWouldDo;
use Modules\Kernel\Api\WhatWroteACopy;
use Modules\Kernel\Api\WhereTheDataGoes;
use Modules\Sdk\Api\PinnedClients;
use Modules\Sdk\Api\Restorers;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AStackThatPutsCopiesBack;
use Tests\Support\Fakes\SequencedEntropy;
use Tests\Support\WhatAScopeSays;
use Tests\Support\WhatTheContractAccepts;

// The PuttingBack contract, run against the adapter and against the fake.
//
// Three questions of one port: what putting a copy back would do, the yes,
// and what became of it. `KeepingCurrentContractTest`'s shape for the second
// and third.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** The stack a copy is put back on. */
function aStackACopyIsPutBackOn(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('e', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('f', Fingerprint::CHARACTERS)),
    );
}

/** The copy these cases put back. */
function theCopyPutBack(): ACopy
{
    return ACopy::named('lemonfiber-20260924-0300-sonarr');
}

/**
 * What a stack answers about putting a copy back, changed where a case says.
 *
 * @param  array<mixed>      $would the listing's own fields that differ
 * @param  array<mixed>|null $done  what it did, where it did anything
 * @return array<string, mixed>
 */
function whatAStackSaysOfPuttingItBack(array $would = [], ?array $done = null): array
{
    return ['api_version' => 1, 'kind' => 'restore', 'data' => [
        'would' => [
            'agreement' => 'restore-one-service-sonarr-0.9.0',
            'downgrade' => false,
            'manifest' => [
                'created_at' => '2026-09-24T03:00:00Z',
                'data_root' => '/srv/old',
                'members' => [['archive_path' => 'services/sonarr', 'label' => 'sonarr configuration']],
                'product_version' => '0.9.0',
                'schema' => 1,
                'scope' => ['scope' => 'service', 'name' => 'sonarr'],
                'sensitive' => true,
            ],
            'relocation' => ['was' => '/srv/old', 'now' => '/srv/new'],
            ...$would,
        ],
        'done' => $done,
    ]];
}

/**
 * What a finished restore reports it did.
 *
 * @return array<string, mixed>
 */
function whatARestoreDid(): array
{
    return [
        'from_version' => '0.9.0',
        'relocated' => ['was' => '/srv/old', 'now' => '/srv/new'],
        'scope' => ['scope' => 'service', 'name' => 'sonarr'],
    ];
}

/** The listing that payload stands for, as the fake is handed it. */
function theSameListing(): WhatPuttingItBackWouldDo
{
    return WhatPuttingItBackWouldDo::listed(
        theCopyPutBack(),
        'restore-one-service-sonarr-0.9.0',
        ScopeOfACopy::oneService(ServiceId::called('sonarr')),
        WhatWroteACopy::of('0.9.0', '2026-09-24T03:00:00Z'),
        WhatACopyHolds::these('sonarr configuration'),
        older: false,
        data: WhereTheDataGoes::elsewhere(ARelocation::from('/srv/old', '/srv/new')),
    );
}

/** The report a finished restore stands for, as the fake is handed it. */
function theSameRestore(): ACopyPutBack
{
    return ACopyPutBack::reported(
        ScopeOfACopy::oneService(ServiceId::called('sonarr')),
        '0.9.0',
        WhereTheDataGoes::elsewhere(ARelocation::from('/srv/old', '/srv/new')),
    );
}

/**
 * Both ways of putting a copy back, each set up to say the same.
 *
 * @return array<string, Closure(): PuttingBack>
 */
function everyWayOfPuttingACopyBack(MockResponse $answered, HowPuttingItBackIsGoing $became): array
{
    return [
        'the fake' => static fn(): PuttingBack => AStackThatPutsCopiesBack::listing(theSameListing(), $became),
        'the adapter' => static function () use ($answered): PuttingBack {
            MockClient::destroyGlobal();
            MockClient::global([$answered]);

            return new Restorers(new PinnedClients(), SequencedEntropy::counting());
        },
    ];
}

/** One line carried out of an `either()` arm. */
final readonly class WhatPuttingItBackSaid
{
    public function __construct(public string $said) {}
}

/** Where the data goes, as a line. */
function whereTheDataGoesSaid(WhereTheDataGoes $data): string
{
    return $data->either(
        whereItWas: static fn(): WhatPuttingItBackSaid => new WhatPuttingItBackSaid('where it was'),
        elsewhere: static fn(ARelocation $moved): WhatPuttingItBackSaid => new WhatPuttingItBackSaid(sprintf('%s to %s', $moved->was(), $moved->now())),
    )->said;
}

/** What asking to rehearse came to, every part of a listing folded to one line. */
function whatPuttingItBackWouldDoSaid(PuttingBack $puttingBack): string
{
    return $puttingBack->rehearse(aStackACopyIsPutBackOn(), Session::of('a-session-not-a-secret'), theCopyPutBack())->either(
        listed: static fn(WhatPuttingItBackWouldDo $listing): WhatPuttingItBackSaid => new WhatPuttingItBackSaid(sprintf(
            '%s|%s|%s|by %s at %s|holds %s|%s|%s',
            $listing->copy()->name(),
            $listing->agreement(),
            WhatAScopeSays::of($listing->scope()),
            $listing->takenBy(),
            $listing->takenAt(),
            implode(',', iterator_to_array($listing->contents(), preserve_keys: false)),
            $listing->isOlder() ? 'older' : 'this version',
            whereTheDataGoesSaid($listing->whereTheDataGoes()),
        )),
        met: static fn(Obstacle $why): WhatPuttingItBackSaid => new WhatPuttingItBackSaid($why->name),
    )->said;
}

/** What the yes came to, as a line. */
function howPuttingItBackWasAgreed(PuttingBack $puttingBack): string
{
    return $puttingBack->putBack(aStackACopyIsPutBackOn(), Session::of('a-session-not-a-secret'), theSameListing())->either(
        started: static fn(Job $job): WhatPuttingItBackSaid => new WhatPuttingItBackSaid(sprintf('following %s', $job->shown())),
        met: static fn(Obstacle $why): WhatPuttingItBackSaid => new WhatPuttingItBackSaid($why->name),
    )->said;
}

/** What asking after the restore came to, every part of a report folded to one line. */
function whatBecameOfPuttingItBack(PuttingBack $puttingBack): string
{
    return $puttingBack->whatBecameOf(aStackACopyIsPutBackOn(), Session::of('a-session-not-a-secret'), Job::named(AStackThatPutsCopiesBack::THE_JOB))->either(
        stillRunning: static fn(): WhatPuttingItBackSaid => new WhatPuttingItBackSaid('still running'),
        done: static fn(ACopyPutBack $report): WhatPuttingItBackSaid => new WhatPuttingItBackSaid(sprintf(
            '%s|by %s|%s',
            WhatAScopeSays::of($report->scope()),
            $report->takenBy(),
            whereTheDataGoesSaid($report->whereTheDataWent()),
        )),
        ended: static fn(): WhatPuttingItBackSaid => new WhatPuttingItBackSaid('ended'),
        met: static fn(Obstacle $why): WhatPuttingItBackSaid => new WhatPuttingItBackSaid($why->name),
    )->said;
}

/** What a stack answers a yes it took on with. */
function aRestoreTakenOn(): MockResponse
{
    return MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'job', 'data' => ['action' => 'restore', 'job' => AStackThatPutsCopiesBack::THE_JOB]]), 202);
}

it('reads every part of the listing, and where the data would go', function (): void {
    $listed = MockResponse::make((string) json_encode(whatAStackSaysOfPuttingItBack()));

    foreach (everyWayOfPuttingACopyBack($listed, HowPuttingItBackIsGoing::stillRunning()) as $which => $build) {
        expect(whatPuttingItBackWouldDoSaid($build()))
            ->toBe('lemonfiber-20260924-0300-sonarr|restore-one-service-sonarr-0.9.0|service:sonarr|by 0.9.0 at 2026-09-24T03:00:00Z|holds sonarr configuration|this version|/srv/old to /srv/new', $which);
    }
});

it('reads a listing whose data goes back where it came from, said as nothing', function (array $would): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(whatAStackSaysOfPuttingItBack($would)))]);

    expect(whatPuttingItBackWouldDoSaid(new Restorers(new PinnedClients(), SequencedEntropy::counting())))
        ->toEndWith('|where it was');
})->with([
    'null' => [['relocation' => null]],
]);

it('refuses to list a copy the stack would not, with the obstacle rather than a listing', function (): void {
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::CredentialWasRefused],
        [MockResponse::make('{"error":"too new"}', 409), Obstacle::StackDidNotAnswer],
        [MockResponse::make('not json at all'), Obstacle::StackDidNotAnswer],
    ];

    foreach ($table as [$answered, $why]) {
        MockClient::destroyGlobal();
        MockClient::global([$answered]);
        expect(whatPuttingItBackWouldDoSaid(new Restorers(new PinnedClients(), SequencedEntropy::counting())))->toBe($why->name);
        expect(whatPuttingItBackWouldDoSaid(AStackThatPutsCopiesBack::met($why)))->toBe($why->name);
    }
});

it('a listing this app cannot read is a stack that did not answer, never a shorter listing', function (array $would): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(whatAStackSaysOfPuttingItBack($would)))]);

    expect(whatPuttingItBackWouldDoSaid(new Restorers(new PinnedClients(), SequencedEntropy::counting())))->toBe(Obstacle::StackDidNotAnswer->name);
})->with([
    'a blank agreement' => [['agreement' => ' ']],
    'no manifest' => [['manifest' => 'none']],
    'a relocation that is not two roots' => [['relocation' => '/srv/new']],
    'a relocation with a blank root' => [['relocation' => ['was' => '/srv/old', 'now' => ' ']]],
    'a service with a blank name' => [['manifest' => ['created_at' => '2026-09-24T03:00:00Z', 'data_root' => '/srv', 'members' => [], 'product_version' => '0.9.0', 'schema' => 1, 'scope' => ['scope' => 'service', 'name' => ' '], 'sensitive' => true]]],
    'a thing held with a blank label' => [['manifest' => ['created_at' => '2026-09-24T03:00:00Z', 'data_root' => '/srv', 'members' => [['archive_path' => 'config', 'label' => ' ']], 'product_version' => '0.9.0', 'schema' => 1, 'scope' => ['scope' => 'whole_stack'], 'sensitive' => true]]],
]);

it('comes away from a yes with the job the stack named', function (): void {
    foreach (everyWayOfPuttingACopyBack(aRestoreTakenOn(), HowPuttingItBackIsGoing::stillRunning()) as $which => $build) {
        expect(howPuttingItBackWasAgreed($build()))->toBe(sprintf('following %s', AStackThatPutsCopiesBack::THE_JOB), $which);
    }
});

it('comes away from a refused yes with the obstacle rather than a job', function (): void {
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::CredentialWasRefused],
        [MockResponse::make('{"error":"still running"}', 409), Obstacle::StackDidNotAnswer],
        [MockResponse::make('not json at all'), Obstacle::StackDidNotAnswer],
        [MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'job', 'data' => ['action' => 'restore', 'job' => ' ']]), 202), Obstacle::StackDidNotAnswer],
    ];

    foreach ($table as [$answered, $why]) {
        MockClient::destroyGlobal();
        MockClient::global([$answered]);
        expect(howPuttingItBackWasAgreed(new Restorers(new PinnedClients(), SequencedEntropy::counting())))->toBe($why->name);
        expect(howPuttingItBackWasAgreed(AStackThatPutsCopiesBack::listingButRefusing(theSameListing(), $why)))->toBe($why->name);
    }
});

it('reads what a finished restore did, and where the data went', function (): void {
    $finished = MockResponse::make((string) json_encode(whatAStackSaysOfPuttingItBack(done: whatARestoreDid())));

    foreach (everyWayOfPuttingACopyBack($finished, HowPuttingItBackIsGoing::done(theSameRestore())) as $which => $build) {
        expect(whatBecameOfPuttingItBack($build()))->toBe('service:sonarr|by 0.9.0|/srv/old to /srv/new', $which);
    }
});

it('reads a finished restore that put the data back where it came from', function (array $done): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(whatAStackSaysOfPuttingItBack(done: $done)))]);

    expect(whatBecameOfPuttingItBack(new Restorers(new PinnedClients(), SequencedEntropy::counting())))->toBe('service:sonarr|by 0.9.0|where it was');
})->with([
    'said as null' => [['from_version' => '0.9.0', 'relocated' => null, 'scope' => ['scope' => 'service', 'name' => 'sonarr']]],
    'left out' => [['from_version' => '0.9.0', 'scope' => ['scope' => 'service', 'name' => 'sonarr']]],
]);

it('a finished restore that says nothing of what it did is a stack that did not answer', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(whatAStackSaysOfPuttingItBack()))]);

    expect(whatBecameOfPuttingItBack(new Restorers(new PinnedClients(), SequencedEntropy::counting())))->toBe(Obstacle::StackDidNotAnswer->name);
});

it('a restore still running is its own answer', function (): void {
    foreach (everyWayOfPuttingACopyBack(aRestoreTakenOn(), HowPuttingItBackIsGoing::stillRunning()) as $which => $build) {
        expect(whatBecameOfPuttingItBack($build()))->toBe('still running', $which);
    }
});

it('a restore the stack no longer has a job for is ended, not unreachable and not running', function (MockResponse $forgotten): void {
    foreach (everyWayOfPuttingACopyBack($forgotten, HowPuttingItBackIsGoing::ended()) as $which => $build) {
        expect(whatBecameOfPuttingItBack($build()))->toBe('ended', $which);
    }
})->with([
    'let go of' => [MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'job', 'data' => ['action' => 'restore', 'job' => AStackThatPutsCopiesBack::THE_JOB]]))],
    'never known' => [MockResponse::make('{"error":"no such job"}', 404)],
]);

it('asking after a restore tells a refused session from a stack that is not answering', function (): void {
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::CredentialWasRefused],
        [MockResponse::make('{"error":"gone"}', 500), Obstacle::StackDidNotAnswer],
        [MockResponse::make('not json at all'), Obstacle::StackDidNotAnswer],
    ];

    foreach ($table as [$answered, $why]) {
        foreach (everyWayOfPuttingACopyBack($answered, HowPuttingItBackIsGoing::met($why)) as $which => $build) {
            expect(whatBecameOfPuttingItBack($build()))->toBe($why->name, $which);
        }
    }
});

it('the fake remembers the copy rehearsed, the listing agreed to and the handle followed', function (): void {
    $puttingBack = AStackThatPutsCopiesBack::listing(theSameListing(), HowPuttingItBackIsGoing::stillRunning());
    whatPuttingItBackWouldDoSaid($puttingBack);
    howPuttingItBackWasAgreed($puttingBack);
    whatBecameOfPuttingItBack($puttingBack);

    expect(array_map(static fn(ACopy $copy): string => $copy->name(), $puttingBack->rehearsed()))->toBe(['lemonfiber-20260924-0300-sonarr'])
        ->and(array_map(static fn(WhatPuttingItBackWouldDo $listed): string => $listed->agreement(), $puttingBack->agreed()))->toBe(['restore-one-service-sonarr-0.9.0'])
        ->and(array_map(static fn(Job $job): string => $job->shown(), $puttingBack->followed()))->toBe([AStackThatPutsCopiesBack::THE_JOB]);
});

it('stands in for a stack with payloads the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('RestoreEnvelope', whatAStackSaysOfPuttingItBack()))
        ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n")
        ->and(WhatTheContractAccepts::complaintsAbout('RestoreEnvelope', whatAStackSaysOfPuttingItBack(done: whatARestoreDid())))
        ->toBe([]);
});
