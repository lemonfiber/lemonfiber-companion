<?php

declare(strict_types=1);

use Modules\Kernel\Api\ACopyRunning;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\FollowingTheCopyRunning;
use Modules\Kernel\Api\HowTheCopyMoves;
use Modules\Kernel\Api\HowTheCopyRunningWasRead;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\WhatAStreamSaid;
use Modules\Kernel\Api\WhatIsBeingCopied;
use Modules\Kernel\Api\WhatIsCopied;
use Modules\Sdk\Api\CopyWatchers;
use Modules\Sdk\Api\PinnedClients;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AStackThatCopies;
use Tests\Support\TheWordCarriedOut;
use Tests\Support\WhatTheContractAccepts;
use Tests\Support\WhereEachOpenResumed;

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** A house whose operator follows a copy. */
function aHouseThatCopies(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('k', Nonce::SHORTEST))),
        StackName::of('Home'),
        Address::of('https://192.168.1.46:8443'),
        Fingerprint::of(str_repeat('d', Fingerprint::CHARACTERS)),
    );
}

function theOperatorsSessionWhileCopying(): Session
{
    return Session::of('an-operator-session-not-a-secret');
}

/**
 * What the core says is running, changed where a case says.
 *
 * @param  array<mixed>  $changed the running copy's own fields that differ
 * @return array<string, mixed>
 */
function whatTheCoreSaysIsBeingCopied(array $changed = []): array
{
    return ['api_version' => 1, 'kind' => 'copying', 'data' => ['running' => [
        'what' => 'backup',
        'scope' => ['scope' => 'whole_stack'],
        'job' => '9f2c1a7e04b3d815',
        'started' => 1_795_563_600,
        'written' => 734_003_200,
        'whole' => 2_147_483_648,
        'part' => 'sonarr database',
        'state' => 'working',
        ...$changed,
    ]]];
}

/**
 * That nothing is running, as the core says it.
 *
 * @return array<string, mixed>
 */
function nothingTheCoreIsCopying(): array
{
    return ['api_version' => 1, 'kind' => 'copying', 'data' => ['running' => null]];
}

/**
 * One `copying` event, as the core frames it on the stream.
 *
 * @param  array<string, mixed>  $envelope
 */
function aCopyingEvent(array $envelope): string
{
    return sprintf("id: run-1\nevent: copying\ndata: %s\n\n", json_encode($envelope, JSON_THROW_ON_ERROR));
}

/**
 * Both ways of following the copy running, each set up to say the same.
 *
 * @param  list<MockResponse>                        $answered what the far end answers, in order
 * @param  list<WhatAStreamSaid<WhatIsBeingCopied>>  $script   what the fake hears, wake by wake
 * @return array<string, Closure(): FollowingTheCopyRunning>
 */
function everyWayOfFollowingTheCopy(array $answered, HowTheCopyRunningWasRead $now, array $script): array
{
    return [
        'the fake' => static fn(): FollowingTheCopyRunning => AStackThatCopies::thenEnding($now, ...$script),
        'the adapter' => static function () use ($answered): FollowingTheCopyRunning {
            MockClient::destroyGlobal();
            MockClient::global($answered);

            return new CopyWatchers(new PinnedClients());
        },
    ];
}

/** What is being copied, folded to one line. */
function whatIsBeingCopiedAsAWord(WhatIsBeingCopied $copying): string
{
    return $copying->either(
        nothing: static fn(): TheWordCarriedOut => new TheWordCarriedOut('none running'),
        running: static fn(ACopyRunning $running): TheWordCarriedOut => new TheWordCarriedOut(sprintf(
            '%s since %d: %d of %d, %s, %s',
            $running->what->value,
            $running->started->epochSeconds(),
            $running->written,
            $running->whole,
            $running->part,
            $running->moves->either(
                working: static fn(): TheWordCarriedOut => new TheWordCarriedOut('working'),
                paced: static fn(int $limit): TheWordCarriedOut => new TheWordCarriedOut(sprintf('paced to %d', $limit)),
                stalled: static fn(Instant $wrote): TheWordCarriedOut => new TheWordCarriedOut(sprintf('stalled since %d', $wrote->epochSeconds())),
            )->said,
        )),
    )->said;
}

function howTheCopyWasReadAsAWord(HowTheCopyRunningWasRead $read): string
{
    return $read->either(
        said: static fn(WhatIsBeingCopied $copying): TheWordCarriedOut => new TheWordCarriedOut(whatIsBeingCopiedAsAWord($copying)),
        refused: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->value),
    )->said;
}

/** @param WhatAStreamSaid<WhatIsBeingCopied> $said */
function whatTheStreamSaidOfTheCopy(WhatAStreamSaid $said): string
{
    return $said->either(
        nothing: static fn(): TheWordCarriedOut => new TheWordCarriedOut('nothing'),
        alive: static fn(): TheWordCarriedOut => new TheWordCarriedOut('alive'),
        said: static fn(WhatIsBeingCopied $copying): TheWordCarriedOut => new TheWordCarriedOut(whatIsBeingCopiedAsAWord($copying)),
        closed: static fn(): TheWordCarriedOut => new TheWordCarriedOut('closed'),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->value),
    )->said;
}

function aBackupWorking(): WhatIsBeingCopied
{
    return WhatIsBeingCopied::running(new ACopyRunning(WhatIsCopied::Backup, Instant::atEpochSeconds(1_795_563_600), 734_003_200, 2_147_483_648, 'sonarr database', HowTheCopyMoves::working()));
}

const A_BACKUP_WORKING = 'backup since 1795563600: 734003200 of 2147483648, sonarr database, working';

it('reads a running copy as the core says it, and none running as nothing', function (): void {
    $table = [
        [whatTheCoreSaysIsBeingCopied(), aBackupWorking(), A_BACKUP_WORKING],
        [nothingTheCoreIsCopying(), WhatIsBeingCopied::nothing(), 'none running'],
        [['api_version' => 1, 'kind' => 'copying', 'data' => []], WhatIsBeingCopied::nothing(), 'none running'],
    ];

    foreach ($table as [$payload, $copying, $word]) {
        foreach (everyWayOfFollowingTheCopy([MockResponse::make($payload)], HowTheCopyRunningWasRead::said($copying), []) as $which => $make) {
            expect(howTheCopyWasReadAsAWord($make()->now(aHouseThatCopies(), theOperatorsSessionWhileCopying())))->toBe($word, $which);
        }
    }
});

it('reads a restore, a paced copy with its limit and a stalled one with when it last wrote, each as the stack says', function (): void {
    $table = [
        [['what' => 'restore'], 'restore since 1795563600: 734003200 of 2147483648, sonarr database, working'],
        [['state' => 'paced', 'limit' => 52_428_800], 'backup since 1795563600: 734003200 of 2147483648, sonarr database, paced to 52428800'],
        [['state' => 'stalled', 'wrote' => 1_795_563_900], 'backup since 1795563600: 734003200 of 2147483648, sonarr database, stalled since 1795563900'],
    ];

    foreach ($table as [$changed, $word]) {
        MockClient::destroyGlobal();
        MockClient::global([MockResponse::make(whatTheCoreSaysIsBeingCopied($changed))]);

        expect(howTheCopyWasReadAsAWord(new CopyWatchers(new PinnedClients())->now(aHouseThatCopies(), theOperatorsSessionWhileCopying())))->toBe($word);
    }
});

it('hears the copy on the stream, and then that the stream ended', function (): void {
    foreach (everyWayOfFollowingTheCopy(
        [MockResponse::make(aCopyingEvent(whatTheCoreSaysIsBeingCopied()))],
        HowTheCopyRunningWasRead::said(WhatIsBeingCopied::nothing()),
        [WhatAStreamSaid::said(aBackupWorking())],
    ) as $which => $make) {
        $following = $make();

        expect([
            whatTheStreamSaidOfTheCopy($following->heard(aHouseThatCopies(), theOperatorsSessionWhileCopying())),
            whatTheStreamSaidOfTheCopy($following->heard(aHouseThatCopies(), theOperatorsSessionWhileCopying())),
        ])->toBe([A_BACKUP_WORKING, 'closed'], $which);
    }
});

it('hears nothing from a stream that has said nothing yet, and then that it closed', function (): void {
    foreach (everyWayOfFollowingTheCopy(
        [MockResponse::make('')],
        HowTheCopyRunningWasRead::said(WhatIsBeingCopied::nothing()),
        [WhatAStreamSaid::nothing(), WhatAStreamSaid::closed()],
    ) as $which => $make) {
        $following = $make();

        expect([
            whatTheStreamSaidOfTheCopy($following->heard(aHouseThatCopies(), theOperatorsSessionWhileCopying())),
            whatTheStreamSaidOfTheCopy($following->heard(aHouseThatCopies(), theOperatorsSessionWhileCopying())),
        ])->toBe(['nothing', 'closed'], $which);
    }
});

it('hears a sign of life in a heartbeat, and closes when let go of', function (): void {
    foreach (everyWayOfFollowingTheCopy(
        [MockResponse::make(": beat\n\n")],
        HowTheCopyRunningWasRead::said(WhatIsBeingCopied::nothing()),
        [WhatAStreamSaid::aSignOfLife()],
    ) as $which => $make) {
        $following = $make();

        expect(whatTheStreamSaidOfTheCopy($following->heard(aHouseThatCopies(), theOperatorsSessionWhileCopying())))->toBe('alive', $which)
            ->and(whatTheStreamSaidOfTheCopy($following->letGo()))->toBe('closed', $which);
    }
});

it('says a refused session as refused, and a reading it cannot read as an answer it could not read', function (): void {
    $unreadable = [
        ['what' => 'archive'],
        ['what' => null],
        ['started' => 'yesterday'],
        ['written' => -1],
        ['whole' => '2 GB'],
        ['part' => ' '],
        ['state' => 'resting'],
        ['state' => null],
        ['state' => 'paced'],
        ['state' => 'stalled'],
    ];
    $table = [
        [MockResponse::make('{"error":"no"}', 401), KindOfObstacle::CredentialWasRefused],
        [MockResponse::make(['api_version' => 1, 'kind' => 'copying', 'data' => ['running' => 'a backup']]), KindOfObstacle::AnswerCouldNotBeRead],
        [MockResponse::make(['api_version' => 1, 'kind' => 'copying', 'data' => 'a backup']), KindOfObstacle::AnswerCouldNotBeRead],
        ...array_map(static fn(array $changed): array => [MockResponse::make(whatTheCoreSaysIsBeingCopied($changed)), KindOfObstacle::AnswerCouldNotBeRead], $unreadable),
    ];

    foreach ($table as [$answered, $kind]) {
        foreach (everyWayOfFollowingTheCopy([$answered], HowTheCopyRunningWasRead::refused(Obstacle::of($kind)), []) as $which => $make) {
            expect(howTheCopyWasReadAsAWord($make()->now(aHouseThatCopies(), theOperatorsSessionWhileCopying())))->toBe($kind->value, sprintf('%s, %s', $which, $kind->value));
        }
    }
});

it('stands in for a stack with payloads the contract would accept', function (): void {
    foreach ([whatTheCoreSaysIsBeingCopied(), whatTheCoreSaysIsBeingCopied(['state' => 'paced', 'limit' => 52_428_800]), whatTheCoreSaysIsBeingCopied(['what' => 'restore', 'state' => 'stalled', 'wrote' => 1_795_563_900]), nothingTheCoreIsCopying()] as $payload) {
        expect(WhatTheContractAccepts::complaintsAbout('CopyingEnvelope', $payload))
            ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n");
    }
});

it('opens the stream again on the next wake after one that said what it could not read, from past it, rather than saying it closed', function (): void {
    MockClient::destroyGlobal();
    $stack = MockClient::global([
        MockResponse::make(aCopyingEvent(whatTheCoreSaysIsBeingCopied(['state' => 'resting']))),
        MockResponse::make(aCopyingEvent(whatTheCoreSaysIsBeingCopied())),
    ]);
    $following = new CopyWatchers(new PinnedClients());

    expect([
        whatTheStreamSaidOfTheCopy($following->heard(aHouseThatCopies(), theOperatorsSessionWhileCopying())),
        whatTheStreamSaidOfTheCopy($following->heard(aHouseThatCopies(), theOperatorsSessionWhileCopying())),
    ])->toBe([KindOfObstacle::AnswerCouldNotBeRead->value, A_BACKUP_WORKING])
        ->and(WhereEachOpenResumed::in($stack))->toBe([null, 'run-1']);
});

it('opens again the next time it is asked once let go of, forgetting where the stream left off', function (): void {
    MockClient::destroyGlobal();
    $stack = MockClient::global([
        MockResponse::make(aCopyingEvent(whatTheCoreSaysIsBeingCopied())),
        MockResponse::make(aCopyingEvent(nothingTheCoreIsCopying())),
    ]);
    $following = new CopyWatchers(new PinnedClients());

    expect(whatTheStreamSaidOfTheCopy($following->heard(aHouseThatCopies(), theOperatorsSessionWhileCopying())))->toBe(A_BACKUP_WORKING)
        ->and(whatTheStreamSaidOfTheCopy($following->letGo()))->toBe('closed')
        ->and(whatTheStreamSaidOfTheCopy($following->heard(aHouseThatCopies(), theOperatorsSessionWhileCopying())))->toBe('none running')
        ->and(WhereEachOpenResumed::in($stack))->toBe([null, null]);
});
