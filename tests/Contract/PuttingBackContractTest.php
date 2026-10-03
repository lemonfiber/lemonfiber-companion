<?php

declare(strict_types=1);

use Modules\Kernel\Api\ACopy;
use Modules\Kernel\Api\ACopyPutBack;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\ARelocation;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowPuttingItBackIsGoing;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\KindOfObstacle;
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
use Modules\Kernel\Api\WhatTheRefusalNamed;
use Modules\Kernel\Api\WhatWroteACopy;
use Modules\Kernel\Api\WhereTheDataGoes;
use Modules\Sdk\Api\PinnedClients;
use Modules\Sdk\Api\Restorers;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AStackThatPutsCopiesBack;
use Tests\Support\Fakes\SequencedEntropy;
use Tests\Support\TheWordCarriedOut;
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
        'rehearsed' => false,
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

/** Where the data goes, as a line. */
function whereTheDataGoesSaid(WhereTheDataGoes $data): string
{
    return $data->either(
        whereItWas: static fn(): TheWordCarriedOut => new TheWordCarriedOut('where it was'),
        elsewhere: static fn(ARelocation $moved): TheWordCarriedOut => new TheWordCarriedOut(sprintf('%s to %s', $moved->was(), $moved->now())),
    )->said;
}

/** What asking to rehearse came to, every part of a listing folded to one line. */
function whatPuttingItBackWouldDoSaid(PuttingBack $puttingBack): string
{
    return $puttingBack->rehearse(aStackACopyIsPutBackOn(), Session::of('a-session-not-a-secret'), theCopyPutBack())->either(
        listed: static fn(WhatPuttingItBackWouldDo $listing): TheWordCarriedOut => new TheWordCarriedOut(sprintf(
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
        refused: static fn(ARefusalInItsWords $why): TheWordCarriedOut => new TheWordCarriedOut(aRestoreRefusalSaid($why)),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->name),
    )->said;
}

/** A refusal in the stack's words, every part of it on one line. */
function aRestoreRefusalSaid(ARefusalInItsWords $why): string
{
    return sprintf('refused: %s | %s | %s', $why->summary(), $why->meaning(), $why->named()->forTheOperator());
}

/**
 * Every problem the stack documents refusing a restore with, in its own words.
 *
 * Keyed by what each is; each holds its code, severity, state, summary,
 * meaning and detail, the detail null where the stack names nothing.
 *
 * @return array<string, array{string, string, string, string, string, ?string}>
 */
function everyProblemARestoreStopsOn(): array
{
    return [
        'nowhere to look' => ['RESTORE-9', 'error', 'guided', 'This run has nowhere it knows to look for a backup', 'Backups are kept in lemonfiber\'s own directory, and this machine would not say where that is. Nothing was touched.', null],
        'not kept here' => ['RESTORE-8', 'error', 'guided', '`lemonfiber-20260924-0300-sonarr` is not one of the backups kept here', 'A restore asked for by name restores one of the archives this machine took, which are files in one directory. A name holding a path, or climbing out of that directory, is refused rather than followed. Nothing was touched.', null],
        'unreadable' => ['RESTORE-1', 'error', 'guided', 'The backup could not be read', 'A restore verifies the archive before it changes anything, and this one could not be read — most often it is truncated or not a lemonfiber backup. Nothing was touched.', 'unexpected end of archive'],
        'writing outside where it should' => ['RESTORE-4', 'critical', 'actionable', 'This backup would write outside where it should', 'One or more of its entries name a path that leaves the directory they belong in, which a genuine lemonfiber backup never does. It is refused, and nothing was touched.', '../../etc/passwd'],
        'too new' => ['RESTORE-2', 'error', 'guided', 'This backup is from a newer lemonfiber', 'It may hold configuration this version would not restore correctly, so it is refused rather than half-applied. Nothing was touched.', 'the backup is 2.0.0, this is 0.9.0'],
        'another format' => ['RESTORE-3', 'error', 'guided', 'This backup is not in a format this lemonfiber can restore', 'Restoring it could leave the configuration in a state neither version expects, so it is refused. Nothing was touched.', 'schema 3, and this build reads 1'],
        'a setup lemonfiber does not manage' => ['RESTORE-12', 'error', 'guided', 'This backup holds a setup lemonfiber does not manage', 'It was captured before lemonfiber took over, so what is inside it belongs to the setup that was already here rather than to lemonfiber\'s own layout. Putting it back means writing into directories lemonfiber does not manage, which is not something it will do on your behalf. Nothing was touched.', 'taken from the project media, covering /srv/media/config'],
        'the listing moved on' => ['RESTORE-11', 'warning', 'guided', 'What you agreed to is not what this backup would do now', 'The listing you answered was restore-one-service-sonarr-0.9.0, and a fresh look at the archive lists restore-one-service-sonarr-0.9.1. Something has changed since you read it, so nothing was overwritten.', null],
        'the stack running' => ['RESTORE-7', 'error', 'guided', 'The stack is running, so a restore would not be safe', 'A restore touches the service databases, which must not happen while the services are running and writing to them. Nothing was touched.', null],
        'the stack not proven stopped' => ['RESTORE-7', 'error', 'guided', 'The stack cannot be confirmed stopped, so a restore was not attempted', 'lemonfiber could not reach the container engine, so it cannot prove nothing is writing to a service database — and will not risk a restore over one. Nothing was touched.', null],
        'another data root' => ['RESTORE-5', 'warning', 'guided', 'This backup was taken against a different data root', 'Restoring it unchanged would keep the data-root setting the backup was taken with, which names a location that is not on this machine. Accepting re-pointing continues the restore and records that it must use this machine\'s data root instead.', 'was /srv/old, now /srv/new'],
        'not unpacked' => ['RESTORE-6', 'error', 'actionable', 'The backup could not be unpacked', 'The restore was stopped part-way through writing the configuration back. Run it again once the cause is fixed; a seed afterwards will reconcile anything left half-written.', '/srv/stack/config: permission denied'],
        'not re-pointed' => ['RESTORE-10', 'error', 'guided', 'The restored settings still name the backup\'s own data root', 'The archive was unpacked, and the data root it recorded could not be changed to this machine\'s — so the restored settings point at a library that is not here.', null],
    ];
}

/**
 * The problems a restore is refused with before anything is agreed to.
 *
 * The listing is answered at once, and reads the archive: it can find nowhere
 * to look, a name that is not kept here, or an archive it will not restore.
 * The rest wait for the yes.
 *
 * @return list<string>
 */
function whatAListingIsRefusedWith(): array
{
    return ['nowhere to look', 'not kept here', 'unreadable', 'writing outside where it should', 'too new', 'another format', 'a setup lemonfiber does not manage'];
}

/**
 * One problem as the error envelope the stack answers with it.
 *
 * @return array<string, mixed>
 */
function aRestoreProblemSaying(string $which): array
{
    [$code, $severity, $state, $summary, $meaning, $detail] = everyProblemARestoreStopsOn()[$which];
    $problem = ['code' => $code, 'severity' => $severity, 'state' => $state, 'summary' => $summary, 'meaning' => $meaning, 'remedies' => [['action' => 'Check the archive, or restore from a different backup']]];

    return ['api_version' => 1, 'kind' => 'error', 'data' => $detail === null ? $problem : [...$problem, 'detail' => $detail]];
}

/**
 * One problem as the stack answers with it, the refusal the fake is handed for it, and the line both fold to.
 *
 * @return array{MockResponse, ARefusalInItsWords, string}
 */
function aRestoreRefusedWith(string $which): array
{
    [, , , $summary, $meaning, $detail] = everyProblemARestoreStopsOn()[$which];
    $why = ARefusalInItsWords::said($summary, $meaning, $detail === null ? WhatTheRefusalNamed::nothing() : WhatTheRefusalNamed::as($detail));

    return [MockResponse::make((string) json_encode(aRestoreProblemSaying($which)), 500), $why, sprintf('refused: %s | %s | %s', $summary, $meaning, $detail ?? '')];
}

/** What the yes came to, as a line. */
function howPuttingItBackWasAgreed(PuttingBack $puttingBack): string
{
    return $puttingBack->putBack(aStackACopyIsPutBackOn(), Session::of('a-session-not-a-secret'), theSameListing())->either(
        started: static fn(Job $job): TheWordCarriedOut => new TheWordCarriedOut(sprintf('following %s', $job->shown())),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->name),
    )->said;
}

/** What asking after the restore came to, every part of a report folded to one line. */
function whatBecameOfPuttingItBack(PuttingBack $puttingBack): string
{
    return $puttingBack->whatBecameOf(aStackACopyIsPutBackOn(), Session::of('a-session-not-a-secret'), Job::named(AStackThatPutsCopiesBack::THE_JOB))->either(
        stillRunning: static fn(): TheWordCarriedOut => new TheWordCarriedOut('still running'),
        done: static fn(ACopyPutBack $report): TheWordCarriedOut => new TheWordCarriedOut(sprintf(
            '%s|by %s|%s',
            WhatAScopeSays::of($report->scope()),
            $report->takenBy(),
            whereTheDataGoesSaid($report->whereTheDataWent()),
        )),
        refused: static fn(ARefusalInItsWords $why): TheWordCarriedOut => new TheWordCarriedOut(aRestoreRefusalSaid($why)),
        ended: static fn(): TheWordCarriedOut => new TheWordCarriedOut('ended'),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->name),
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
        [MockResponse::make('{"error":"no"}', 401), Obstacle::of(KindOfObstacle::CredentialWasRefused)],
        [MockResponse::make('{"error":"too new"}', 422), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
        [MockResponse::make('not json at all'), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
    ];

    foreach ($table as [$answered, $why]) {
        MockClient::destroyGlobal();
        MockClient::global([$answered]);
        expect(whatPuttingItBackWouldDoSaid(new Restorers(new PinnedClients(), SequencedEntropy::counting())))->toBe($why->kind()->name);
        expect(whatPuttingItBackWouldDoSaid(AStackThatPutsCopiesBack::met($why)))->toBe($why->kind()->name);
    }
});

it('a copy the stack will not list is its refusal, in its words, with what it named', function (): void {
    foreach (whatAListingIsRefusedWith() as $which) {
        [$answered, $why, $said] = aRestoreRefusedWith($which);
        MockClient::destroyGlobal();
        MockClient::global([$answered]);

        expect(whatPuttingItBackWouldDoSaid(new Restorers(new PinnedClients(), SequencedEntropy::counting())))->toBe($said, sprintf('%s, the adapter', $which))
            ->and(whatPuttingItBackWouldDoSaid(AStackThatPutsCopiesBack::refusingToList($why)))->toBe($said, sprintf('%s, the fake', $which));
    }
});

it('a listing refused with a problem at a status that says who may ask is what was met', function (): void {
    $problem = (string) json_encode(aRestoreProblemSaying('too new'));
    $table = [
        [MockResponse::make($problem, 401), Obstacle::of(KindOfObstacle::CredentialWasRefused)],
        [MockResponse::make($problem, 403), Obstacle::of(KindOfObstacle::NotForThisAccount)],
        [MockResponse::make('This machine would not supply the randomness a job needs to be named.', 500, ['Content-Type' => 'text/plain']), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
    ];

    foreach ($table as [$refused, $why]) {
        MockClient::destroyGlobal();
        MockClient::global([$refused]);
        expect(whatPuttingItBackWouldDoSaid(new Restorers(new PinnedClients(), SequencedEntropy::counting())))->toBe($why->kind()->name);
    }
});

it('a listing this app cannot read is a stack that did not answer, never a shorter listing', function (array $would): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(whatAStackSaysOfPuttingItBack($would)))]);

    expect(whatPuttingItBackWouldDoSaid(new Restorers(new PinnedClients(), SequencedEntropy::counting())))->toEqual(KindOfObstacle::StackDidNotAnswer->name);
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
        [MockResponse::make('{"error":"no"}', 401), Obstacle::of(KindOfObstacle::CredentialWasRefused)],
        [MockResponse::make('{"error":"still running"}', 422), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
        [MockResponse::make('not json at all'), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
        [MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'job', 'data' => ['action' => 'restore', 'job' => ' ']]), 202), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
    ];

    foreach ($table as [$answered, $why]) {
        MockClient::destroyGlobal();
        MockClient::global([$answered]);
        expect(howPuttingItBackWasAgreed(new Restorers(new PinnedClients(), SequencedEntropy::counting())))->toBe($why->kind()->name);
        expect(howPuttingItBackWasAgreed(AStackThatPutsCopiesBack::listingButRefusing(theSameListing(), $why)))->toBe($why->kind()->name);
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

    expect(whatBecameOfPuttingItBack(new Restorers(new PinnedClients(), SequencedEntropy::counting())))->toEqual(KindOfObstacle::StackDidNotAnswer->name);
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
        [MockResponse::make('{"error":"no"}', 401), Obstacle::of(KindOfObstacle::CredentialWasRefused)],
        [MockResponse::make('{"error":"gone"}', 500), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
        [MockResponse::make('not json at all'), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
    ];

    foreach ($table as [$answered, $why]) {
        foreach (everyWayOfPuttingACopyBack($answered, HowPuttingItBackIsGoing::met($why)) as $which => $build) {
            expect(whatBecameOfPuttingItBack($build()))->toBe($why->kind()->name, $which);
        }
    }
});

it('a restore the stack stopped on a problem is its refusal, in its words, with what it named', function (): void {
    foreach (array_keys(everyProblemARestoreStopsOn()) as $which) {
        [$answered, $why, $said] = aRestoreRefusedWith($which);

        foreach (everyWayOfPuttingACopyBack($answered, HowPuttingItBackIsGoing::refused($why)) as $way => $build) {
            expect(whatBecameOfPuttingItBack($build()))->toBe($said, sprintf('%s, %s', $which, $way));
        }
    }
});

it('a restore refused with no problem the stack wrote, or at a status that says who may ask, is what was met', function (MockResponse $answered, Obstacle $why): void {
    MockClient::destroyGlobal();
    MockClient::global([$answered]);

    expect(whatBecameOfPuttingItBack(new Restorers(new PinnedClients(), SequencedEntropy::counting())))->toBe($why->kind()->name);
})->with([
    'a problem to a refused session' => [MockResponse::make((string) json_encode(aRestoreProblemSaying('not unpacked')), 401), Obstacle::of(KindOfObstacle::CredentialWasRefused)],
    'a problem to an account that may not ask' => [MockResponse::make((string) json_encode(aRestoreProblemSaying('not unpacked')), 403), Obstacle::of(KindOfObstacle::NotForThisAccount)],
    'a problem with a blank summary' => [MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'error', 'data' => ['code' => 'RESTORE-6', 'severity' => 'error', 'state' => 'actionable', 'summary' => ' ', 'meaning' => 'Stopped part-way.', 'remedies' => []]]), 500), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
    'a problem missing its remedies' => [MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'error', 'data' => ['code' => 'RESTORE-6', 'severity' => 'error', 'state' => 'actionable', 'summary' => 'The backup could not be unpacked', 'meaning' => 'Stopped part-way.']]), 500), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
]);

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

    foreach (array_keys(everyProblemARestoreStopsOn()) as $which) {
        expect(WhatTheContractAccepts::complaintsAbout('ErrorEnvelope', aRestoreProblemSaying($which)))->toBe([], $which);
    }
});
