<?php

declare(strict_types=1);

use Modules\Kernel\Api\ACheckAfterAStart;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AskingHowItCameBack;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowLongItHasFailed;
use Modules\Kernel\Api\HowTheCheckAfterAStartEnded;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheCheckKept;
use Modules\Kernel\Api\ThePortAfterAStart;
use Modules\Kernel\Api\TheServicesNamed;
use Modules\Kernel\Api\TheTunnelAfterAStart;
use Modules\Kernel\Api\WhatTheLastStartCameTo;
use Modules\Kernel\Api\WhenItSays;
use Modules\Sdk\Api\PinnedClients;
use Modules\Sdk\Api\Restarts;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AStackThatSaysHowItCameBack;
use Tests\Support\TheWordCarriedOut;
use Tests\Support\WhatTheContractAccepts;

// The AskingHowItCameBack contract, run against the adapter and against the fake.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** When the machine in these tests last started. */
const WHEN_IT_LAST_STARTED = 1_790_000_000;

/** The stack asked about its last start. */
function aStackThatRestarted(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** What both implementations answer with: a start, a failed check after it, and two starts failed in a row. */
function theSameRestart(): WhatTheLastStartCameTo
{
    $check = TheCheckKept::this(ACheckAfterAStart::ran(
        Instant::atEpochSeconds(WHEN_IT_LAST_STARTED + 120),
        HowTheCheckAfterAStartEnded::Failed,
        TheServicesNamed::of('sonarr', 'radarr'),
        TheTunnelAfterAStart::notEstablished('No handshake within 30 seconds'),
        ThePortAfterAStart::moved(51413, 51999, everyClientTakesIt: false),
        'Two services and the tunnel did not come back',
    ));

    return WhatTheLastStartCameTo::said(WhenItSays::at(Instant::atEpochSeconds(WHEN_IT_LAST_STARTED)), $check, $check, '', HowLongItHasFailed::after(2, WhenItSays::at(Instant::atEpochSeconds(WHEN_IT_LAST_STARTED - 86_400))));
}

/**
 * The payload a stack sends for that start.
 *
 * @param  array<mixed>         $changed
 * @return array<string, mixed>
 */
function whatAStackSaysOfItsLastStart(array $changed = []): array
{
    $check = [
        'against' => WHEN_IT_LAST_STARTED,
        'at' => WHEN_IT_LAST_STARTED + 120,
        'not_back' => ['sonarr', 'radarr'],
        'outcome' => 'failed',
        'port' => ['was' => 51413, 'now' => 51999, 'pushed' => false],
        'said' => 'Two services and the tunnel did not come back',
        'tunnel' => ['state' => 'not-established', 'said' => 'No handshake within 30 seconds'],
    ];

    return [
        'api_version' => 1,
        'kind' => 'boot',
        'data' => [
            'booted_at' => WHEN_IT_LAST_STARTED,
            'verified' => $check,
            'last_verified' => $check,
            'failed_boots' => 2,
            'failing_since' => WHEN_IT_LAST_STARTED - 86_400,
            ...$changed,
        ],
    ];
}

/**
 * Both ways of asking, each set up to produce the same answer.
 *
 * @return array<string, Closure(): AskingHowItCameBack>
 */
function everyWayOfAskingHowItCameBack(MockResponse $answered, ?Obstacle $why = null): array
{
    return [
        'the fake' => static fn(): AskingHowItCameBack => $why instanceof Obstacle
            ? AStackThatSaysHowItCameBack::met($why)
            : AStackThatSaysHowItCameBack::with(theSameRestart()),
        'the adapter' => static function () use ($answered): AskingHowItCameBack {
            MockClient::destroyGlobal();
            MockClient::global([$answered]);

            return new Restarts(new PinnedClients());
        },
    ];
}

/** A moment the stack named, or that it named none. */
function momentSaid(WhenItSays $when): string
{
    return $when->either(
        at: static fn(Instant $at): TheWordCarriedOut => new TheWordCarriedOut((string) $at->epochSeconds()),
        unsaid: static fn(): TheWordCarriedOut => new TheWordCarriedOut('unsaid'),
    )->said;
}

/** One check kept, folded to one line, or that none is. */
function checkSaid(TheCheckKept $kept): string
{
    return $kept->either(
        kept: static function (ACheckAfterAStart $check): TheWordCarriedOut {
            $notBack = [];

            foreach ($check->notBack() as $service) {
                $notBack[] = $service;
            }

            return new TheWordCarriedOut(sprintf(
                'at %d: %s, not back [%s], tunnel %s (%s), port %s taken by all %s, said "%s"',
                $check->at()->epochSeconds(),
                $check->ended()->value,
                implode(',', $notBack),
                $check->tunnel()->came()->value,
                $check->tunnel()->said(),
                $check->port()->either(
                    same: static fn(int $is): TheWordCarriedOut => new TheWordCarriedOut(sprintf('same %d', $is)),
                    moved: static fn(int $was, int $is): TheWordCarriedOut => new TheWordCarriedOut(sprintf('%d to %d', $was, $is)),
                    now: static fn(int $is): TheWordCarriedOut => new TheWordCarriedOut(sprintf('now %d', $is)),
                    unsaid: static fn(): TheWordCarriedOut => new TheWordCarriedOut('unsaid'),
                    notForwarded: static fn(): TheWordCarriedOut => new TheWordCarriedOut('none'),
                )->said,
                $check->port()->everyClientTakesIt() ? 'yes' : 'no',
                $check->said(),
            ));
        },
        none: static fn(): TheWordCarriedOut => new TheWordCarriedOut('none'),
    )->said;
}

/** Everything the answer says, folded to one line, so two answers can be compared. */
function everythingTheRestartSays(AskingHowItCameBack $asking): string
{
    return $asking->afterTheLastStart(aStackThatRestarted(), Session::of('a-session-not-a-secret'))->either(
        said: static fn(WhatTheLastStartCameTo $last): TheWordCarriedOut => new TheWordCarriedOut(sprintf(
            'started %s; since it %s; newest %s; declined "%s"; %s',
            momentSaid($last->started()),
            checkSaid($last->sinceIt()),
            checkSaid($last->newest()),
            $last->declined(),
            $last->failing()->either(
                failing: static fn(int $starts, WhenItSays $since): TheWordCarriedOut => new TheWordCarriedOut(sprintf('failed %d since %s', $starts, momentSaid($since))),
                notFailing: static fn(): TheWordCarriedOut => new TheWordCarriedOut('not failing'),
            )->said,
        )),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->value),
    )->said;
}

it('comes away with when the machine started, the check after it, and how long it has failed to come back', function (): void {
    $answered = MockResponse::make((string) json_encode(whatAStackSaysOfItsLastStart()));
    $said = [];

    foreach (everyWayOfAskingHowItCameBack($answered) as $which => $make) {
        $said[$which] = everythingTheRestartSays($make());
    }

    expect($said['the adapter'])->toBe($said['the fake'])
        ->and($said['the fake'])->toBe(sprintf('started %d; since it at %d: failed, not back [sonarr,radarr], tunnel not-established (No handshake within 30 seconds), port 51413 to 51999 taken by all no, said "Two services and the tunnel did not come back"; newest at %d: failed, not back [sonarr,radarr], tunnel not-established (No handshake within 30 seconds), port 51413 to 51999 taken by all no, said "Two services and the tunnel did not come back"; declined ""; failed 2 since %d', WHEN_IT_LAST_STARTED, WHEN_IT_LAST_STARTED + 120, WHEN_IT_LAST_STARTED + 120, WHEN_IT_LAST_STARTED - 86_400));
});

it('tells a session that has ended from a stack that is not answering', function (): void {
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::of(KindOfObstacle::CredentialWasRefused)],
        [MockResponse::make('{"error":"gone"}', 500), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
        [MockResponse::make('not json at all'), Obstacle::of(KindOfObstacle::AnswerCouldNotBeRead)],
    ];

    foreach ($table as [$answered, $why]) {
        foreach (everyWayOfAskingHowItCameBack($answered, $why) as $which => $make) {
            expect(everythingTheRestartSays($make()))->toBe($why->kind()->value, sprintf('%s / %s', $which, $why->kind()->value));
        }
    }
});

it('reads a stack that keeps nothing of a start as one that keeps nothing', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'boot', 'data' => []]))]);

    expect(everythingTheRestartSays(new Restarts(new PinnedClients())))->toBe('started unsaid; since it none; newest none; declined ""; not failing');
});

it('reads a check whose tunnel held and whose port and services were not named', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(whatAStackSaysOfItsLastStart(['verified' => ['at' => WHEN_IT_LAST_STARTED + 60, 'outcome' => 'passed', 'tunnel' => ['state' => 'held']], 'failed_boots' => null, 'failing_since' => null, 'declined' => 'Autostart is off'])))]);

    $said = everythingTheRestartSays(new Restarts(new PinnedClients()));

    expect($said)->toContain(sprintf('started %d; since it at %d: passed, not back [], tunnel held (), port none taken by all yes, said ""; newest at %d', WHEN_IT_LAST_STARTED, WHEN_IT_LAST_STARTED + 60, WHEN_IT_LAST_STARTED + 120))
        ->and($said)->toEndWith('declined "Autostart is off"; not failing');
});

it('reads a port the stack named only after the start, one it could not name after it, and one that stayed', function (array $port, string $said): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(whatAStackSaysOfItsLastStart(['verified' => ['at' => WHEN_IT_LAST_STARTED + 60, 'outcome' => 'passed', 'port' => $port, 'tunnel' => ['state' => 'held']]])))]);

    expect(everythingTheRestartSays(new Restarts(new PinnedClients())))->toContain(sprintf('since it at %d: passed, not back [], tunnel held (), %s, said ""', WHEN_IT_LAST_STARTED + 60, $said));
})->with([
    'named only after' => [['now' => 51999, 'pushed' => true], 'port now 51999 taken by all yes'],
    'not named after' => [['was' => 51413, 'now' => null, 'pushed' => false], 'port unsaid taken by all no'],
    'the same' => [['was' => 51413, 'now' => 51413, 'pushed' => true], 'port same 51413 taken by all yes'],
]);

// `boot` is stood in for and not judged: each case is a payload the contract does not describe, which no stack sends.
it('refuses what it cannot read, rather than drawing a check that did not pass as one that did', function (array $changed): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(whatAStackSaysOfItsLastStart($changed)))]);

    expect(everythingTheRestartSays(new Restarts(new PinnedClients())))->toBe(KindOfObstacle::AnswerCouldNotBeRead->value);
})->with([
    'an outcome it has no case for' => [['verified' => ['at' => 1, 'outcome' => 'fine', 'tunnel' => ['state' => 'held']]]],
    'a tunnel it has no case for' => [['verified' => ['at' => 1, 'outcome' => 'passed', 'tunnel' => ['state' => 'sideways']]]],
    'a tunnel that failed with no words' => [['verified' => ['at' => 1, 'outcome' => 'failed', 'tunnel' => ['state' => 'not-established', 'said' => ' ']]]],
    'a check with no time' => [['verified' => ['outcome' => 'passed', 'tunnel' => ['state' => 'held']]]],
    'a check against the latest start from a machine that will not say when it started' => [['booted_at' => null]],
    'a service not back that is not a name' => [['verified' => ['at' => 1, 'outcome' => 'failed', 'not_back' => [7], 'tunnel' => ['state' => 'held']]]],
    'a port with no word on its clients' => [['verified' => ['at' => 1, 'outcome' => 'passed', 'port' => ['now' => 1], 'tunnel' => ['state' => 'held']]]],
]);

it('stands in for a stack with a payload the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('BootEnvelope', whatAStackSaysOfItsLastStart()))
        ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n");
});
