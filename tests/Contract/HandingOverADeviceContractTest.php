<?php

declare(strict_types=1);

use Modules\Kernel\Api\AClientToHandOver;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AHandoff;
use Modules\Kernel\Api\AMomentAsWritten;
use Modules\Kernel\Api\AnAddressToHand;
use Modules\Kernel\Api\ASignedInDevice;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HandingOverADevice;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\SomebodyInTheHousehold;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheClientsToHandOver;
use Modules\Kernel\Api\TheSignedInDevices;
use Modules\Kernel\Api\TheStepsOnTheirDevice;
use Modules\Kernel\Api\WhatBecameOfTheHandoff;
use Modules\Kernel\Api\WhatTheHandoffNeedsNext;
use Modules\Kernel\Api\WhatToHandThem;
use Modules\Kernel\Api\WhereTheHandoffStands;
use Modules\Sdk\Api\Connectors;
use Modules\Sdk\Api\PinnedClients;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AStackThatHandsDevicesOver;
use Tests\Support\Fakes\SequencedEntropy;
use Tests\Support\TheWordCarriedOut;
use Tests\Support\WhatTheContractAccepts;

// The HandingOverADevice contract, run against the adapter and against the fake.
//
// `MakingPairingCodesContractTest`'s shape: an asking that answers a handle,
// and a following that answers where the hand-off stands — with a refusal in
// the stack's own words beside it, since a name the stack does not know is the
// ordinary way asking comes to nothing.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** The stack a device is handed over from. */
function aStackHandingADeviceOver(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('e', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('f', Fingerprint::CHARACTERS)),
    );
}

/**
 * The payload a stack answers a hand-off with, changed where a case says.
 *
 * @param  array<mixed>         $changed
 * @return array<string, mixed>
 */
function whatAStackAnswersAHandoffWith(array $changed = []): array
{
    return ['api_version' => 1, 'kind' => 'handoff', 'data' => [
        'name' => 'Sam',
        'state' => 'pending',
        'reason' => 'The code went out and no device of theirs has signed in since.',
        'remedy' => 'ask-again',
        'address' => 'https://den.local:8920',
        'caution' => 'That address answers only on the home network.',
        'issued' => '2026-09-30T20:00:00Z',
        'quick_connect' => true,
        'rehearsed' => false,
        'steps' => ['Open the app on their device and give it the server.', 'Sign in as Sam with their own password.'],
        'clients' => [
            ['device' => 'An iPhone', 'client' => 'Swiftfin', 'open_source' => true, 'code' => 'swiftfin://server?url=https://den.local:8920', 'deep_link' => true],
            ['device' => 'A smart TV', 'client' => 'Jellyfin for TV', 'open_source' => false, 'code' => 'https://den.local:8920', 'deep_link' => false],
        ],
        'sessions' => [
            ['device' => 'Sam\'s laptop', 'client' => 'Jellyfin Web', 'last_seen' => '2026-09-30T21:18:23.8915360Z'],
            ['device' => 'An old tablet', 'client' => 'Jellyfin Android', 'last_seen' => null],
        ],
        ...$changed,
    ]];
}

/** The hand-off that payload stands for, as the fake is handed it. */
function theSameHandoff(): AHandoff
{
    return AHandoff::answered(
        SomebodyInTheHousehold::called('Sam'),
        WhereTheHandoffStands::Pending,
        'The code went out and no device of theirs has signed in since.',
        WhatTheHandoffNeedsNext::AskAgain,
        WhatToHandThem::of(
            AnAddressToHand::at('https://den.local:8920', 'That address answers only on the home network.'),
            TheStepsOnTheirDevice::of('Open the app on their device and give it the server.', 'Sign in as Sam with their own password.'),
            TheClientsToHandOver::of(
                AClientToHandOver::named('An iPhone', 'Swiftfin', openSource: true, code: 'swiftfin://server?url=https://den.local:8920', deepLink: true),
                AClientToHandOver::named('A smart TV', 'Jellyfin for TV', openSource: false, code: 'https://den.local:8920', deepLink: false),
            ),
        ),
        AMomentAsWritten::of('2026-09-30T20:00:00Z'),
        TheSignedInDevices::of(
            ASignedInDevice::listed('Sam\'s laptop', 'Jellyfin Web', AMomentAsWritten::of('2026-09-30T21:18:23.8915360Z')),
            ASignedInDevice::listed('An old tablet', 'Jellyfin Android', AMomentAsWritten::of('')),
        ),
    );
}

/**
 * Both ways of handing a device over, each set up to say the same.
 *
 * @return array<string, Closure(): HandingOverADevice>
 */
function everyWayOfHandingADeviceOver(MockResponse $answered, WhatBecameOfTheHandoff $became): array
{
    return [
        'the fake' => static fn(): HandingOverADevice => AStackThatHandsDevicesOver::whichTookItOn($became),
        'the adapter' => static function () use ($answered): HandingOverADevice {
            MockClient::destroyGlobal();
            MockClient::global([$answered]);

            return new Connectors(new PinnedClients(), SequencedEntropy::counting());
        },
    ];
}

/** A moment as written, as the seconds it names or a word saying it names none. */
function secondsIn(AMomentAsWritten $moment): string
{
    return $moment->read(
        read: static fn(Instant $at): TheWordCarriedOut => new TheWordCarriedOut((string) $at->epochSeconds()),
        unreadable: static fn(): TheWordCarriedOut => new TheWordCarriedOut('undated'),
    )->said;
}

/** Everything a hand-off says, one field after another. */
function everythingTheHandoffSays(AHandoff $handoff): string
{
    $lines = [
        sprintf(
            '%s|%s|%s|%s|%s|%s|%s',
            $handoff->who()->name(),
            $handoff->stands()->value,
            $handoff->reason(),
            $handoff->next()->name,
            $handoff->handed()->address()->url(),
            $handoff->handed()->address()->caution(),
            secondsIn($handoff->given()),
        ),
    ];

    foreach ($handoff->handed()->steps() as $step) {
        $lines[] = $step;
    }

    foreach ($handoff->handed()->clients() as $client) {
        $lines[] = sprintf('%s|%s|%s|%s|%s', $client->device(), $client->client(), $client->isOpenSource() ? 'open' : 'closed', $client->code(), $client->isALink() ? 'link' : 'address');
    }

    foreach ($handoff->signedIn() as $device) {
        $lines[] = sprintf('%s|%s|%s', $device->device(), $device->client(), secondsIn($device->lastSeen()));
    }

    return implode("\n", $lines);
}

/** Any answer about a hand-off, folded to one line. */
function saidOfTheHandoff(WhatBecameOfTheHandoff $became): string
{
    return $became->either(
        underway: static fn(Job $job): TheWordCarriedOut => new TheWordCarriedOut(sprintf('following %s', $job->shown())),
        answered: static fn(AHandoff $handoff): TheWordCarriedOut => new TheWordCarriedOut(everythingTheHandoffSays($handoff)),
        ended: static fn(): TheWordCarriedOut => new TheWordCarriedOut('ended'),
        refused: static fn(string $because): TheWordCarriedOut => new TheWordCarriedOut(sprintf('refused %s', $because)),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->name),
    )->said;
}

/** What asking to hand Sam's device over came to. */
function howTheHandoffWasAskedFor(HandingOverADevice $handing): string
{
    return saidOfTheHandoff($handing->handOver(aStackHandingADeviceOver(), Session::of('a-session-not-a-secret'), SomebodyInTheHousehold::called('Sam')));
}

/** What asking after a hand-off came to. */
function whatBecameOfTheHandoff(HandingOverADevice $handing): string
{
    return saidOfTheHandoff($handing->whatBecameOf(aStackHandingADeviceOver(), Session::of('a-session-not-a-secret'), Job::named(AStackThatHandsDevicesOver::THE_JOB)));
}

/** What a stack answers an asking it took on with. */
function aHandoffTakenOn(): MockResponse
{
    return MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'job', 'data' => ['action' => 'household-handoff', 'job' => AStackThatHandsDevicesOver::THE_JOB]]), 202);
}

it('comes away from asking with the job the stack named', function (): void {
    foreach (everyWayOfHandingADeviceOver(aHandoffTakenOn(), WhatBecameOfTheHandoff::ended()) as $which => $build) {
        expect(howTheHandoffWasAskedFor($build()))->toBe(sprintf('following %s', AStackThatHandsDevicesOver::THE_JOB), $which);
    }
});

it('asks the hand-off action by the person\'s name, under a key of its own', function (): void {
    MockClient::destroyGlobal();
    $mock = MockClient::global([aHandoffTakenOn()]);

    howTheHandoffWasAskedFor(new Connectors(new PinnedClients(), SequencedEntropy::counting()));
    $sent = $mock->getLastPendingRequest();

    expect($sent?->getUrl())->toEndWith('/api/actions/household-handoff')
        ->and($sent?->body()?->all())->toBe(['name' => 'Sam'])
        ->and($sent?->headers()->get('Idempotency-Key'))->not->toBeNull();
});

it('reads where the hand-off stands and everything it hands over, in the stack\'s order', function (): void {
    $answered = MockResponse::make((string) json_encode(whatAStackAnswersAHandoffWith()));

    foreach (everyWayOfHandingADeviceOver($answered, WhatBecameOfTheHandoff::answered(theSameHandoff())) as $which => $build) {
        expect(whatBecameOfTheHandoff($build()))->toBe(
            "Sam|pending|The code went out and no device of theirs has signed in since.|AskAgain|https://den.local:8920|That address answers only on the home network.|1790798400\n"
            . "Open the app on their device and give it the server.\n"
            . "Sign in as Sam with their own password.\n"
            . "An iPhone|Swiftfin|open|swiftfin://server?url=https://den.local:8920|link\n"
            . "A smart TV|Jellyfin for TV|closed|https://den.local:8920|address\n"
            . "Sam's laptop|Jellyfin Web|1790803103\n"
            . 'An old tablet|Jellyfin Android|undated',
            $which,
        );
    }
});

it('reads what the contract makes optional as absent where the stack sent nothing', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(whatAStackAnswersAHandoffWith([
        'state' => 'unprovisioned',
        'reason' => 'Nobody called Sam has an account yet.',
        'remedy' => 'invite',
        'address' => null,
        'caution' => null,
        'issued' => null,
        'steps' => [],
        'clients' => [],
        'sessions' => [],
    ])))]);

    expect(whatBecameOfTheHandoff(new Connectors(new PinnedClients(), SequencedEntropy::counting())))
        ->toBe('Sam|unprovisioned|Nobody called Sam has an account yet.|Invite|||undated');
});

it('reads a hand-off with nothing left to do as naming nothing next', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(whatAStackAnswersAHandoffWith(['state' => 'connected', 'reason' => null, 'remedy' => null])))]);

    expect(whatBecameOfTheHandoff(new Connectors(new PinnedClients(), SequencedEntropy::counting())))->toStartWith('Sam|connected||Nothing|');
});

it('reads every remedy the contract names', function (WhatTheHandoffNeedsNext $next): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(whatAStackAnswersAHandoffWith(['state' => 'failed', 'remedy' => $next->value])))]);

    expect(whatBecameOfTheHandoff(new Connectors(new PinnedClients(), SequencedEntropy::counting())))->toStartWith(sprintf('Sam|failed|The code went out and no device of theirs has signed in since.|%s|', $next->name));
})->with(array_filter(WhatTheHandoffNeedsNext::cases(), static fn(WhatTheHandoffNeedsNext $next): bool => $next !== WhatTheHandoffNeedsNext::Nothing));

it('hands on a refusal in the stack\'s own words, asking or following', function (): void {
    $said = 'Sam is not a name the media server knows';
    $refused = static fn(): MockResponse => MockResponse::make($said, 422, ['Content-Type' => 'text/plain']);
    $fake = AStackThatHandsDevicesOver::answering(WhatBecameOfTheHandoff::refused($said));

    MockClient::destroyGlobal();
    MockClient::global([$refused()]);
    expect(howTheHandoffWasAskedFor(new Connectors(new PinnedClients(), SequencedEntropy::counting())))->toBe(sprintf('refused %s', $said))
        ->and(howTheHandoffWasAskedFor($fake))->toBe(sprintf('refused %s', $said));

    MockClient::destroyGlobal();
    MockClient::global([$refused()]);
    expect(whatBecameOfTheHandoff(new Connectors(new PinnedClients(), SequencedEntropy::counting())))->toBe(sprintf('refused %s', $said))
        ->and(whatBecameOfTheHandoff($fake))->toBe(sprintf('refused %s', $said));
});

it('tells a refused session and a silent stack from a refusal', function (): void {
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::of(KindOfObstacle::CredentialWasRefused)],
        [MockResponse::make('{"error":"gone"}', 500), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
        [MockResponse::make('not json at all'), Obstacle::of(KindOfObstacle::AnswerCouldNotBeRead)],
    ];

    foreach ($table as [$answered, $why]) {
        foreach (everyWayOfHandingADeviceOver($answered, WhatBecameOfTheHandoff::met($why)) as $which => $build) {
            expect(whatBecameOfTheHandoff($build()))->toBe($why->kind()->name, $which);
        }

        MockClient::destroyGlobal();
        MockClient::global([$answered]);
        expect(howTheHandoffWasAskedFor(new Connectors(new PinnedClients(), SequencedEntropy::counting())))->toBe($why->kind()->name)
            ->and(howTheHandoffWasAskedFor(AStackThatHandsDevicesOver::answering(WhatBecameOfTheHandoff::met($why))))->toBe($why->kind()->name);
    }
});

it('an asking answered with a handle it cannot follow is an answer it could not read', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'job', 'data' => ['action' => 'household-handoff', 'job' => ' ']]), 202)]);

    expect(howTheHandoffWasAskedFor(new Connectors(new PinnedClients(), SequencedEntropy::counting())))->toEqual(KindOfObstacle::AnswerCouldNotBeRead->name);
});

it('a hand-off still being worked out is its own answer, and one the stack forgot is ended', function (MockResponse $answered, string $said): void {
    $became = $said === 'ended' ? WhatBecameOfTheHandoff::ended() : WhatBecameOfTheHandoff::underway(Job::named(AStackThatHandsDevicesOver::THE_JOB));

    foreach (everyWayOfHandingADeviceOver($answered, $became) as $which => $build) {
        expect(whatBecameOfTheHandoff($build()))->toBe($said, $which);
    }
})->with([
    'still running' => [aHandoffTakenOn(), sprintf('following %s', AStackThatHandsDevicesOver::THE_JOB)],
    'let go of' => [MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'job', 'data' => ['action' => 'household-handoff', 'job' => AStackThatHandsDevicesOver::THE_JOB]])), 'ended'],
    'never known' => [MockResponse::make('{"error":"no such job"}', 404), 'ended'],
]);

it('a hand-off this app cannot read is an answer it could not read, never one with a part missing', function (array $changed): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(whatAStackAnswersAHandoffWith($changed)))]);

    expect(whatBecameOfTheHandoff(new Connectors(new PinnedClients(), SequencedEntropy::counting())))->toEqual(KindOfObstacle::AnswerCouldNotBeRead->name);
})->with([
    'no name' => [['name' => null]],
    'a blank name' => [['name' => ' ']],
    'a state with no case' => [['state' => 'arrived']],
    'a remedy with no case' => [['remedy' => 'reboot']],
    'a blank reason' => [['reason' => ' ']],
    'a blank caution' => [['caution' => ' ']],
    'steps that are not a list' => [['steps' => 'Open the app']],
    'a step that is not words' => [['steps' => ['Open the app', 7]]],
    'a blank step' => [['steps' => [' ']]],
    'no clients' => [['clients' => null]],
    'a client that is not one' => [['clients' => ['Swiftfin']]],
    'a client with no code' => [['clients' => [['device' => 'An iPhone', 'client' => 'Swiftfin', 'open_source' => true, 'deep_link' => false]]]],
    'a client with a blank code' => [['clients' => [['device' => 'An iPhone', 'client' => 'Swiftfin', 'open_source' => true, 'code' => ' ', 'deep_link' => false]]]],
    'a client that will not say whether it is open source' => [['clients' => [['device' => 'An iPhone', 'client' => 'Swiftfin', 'code' => 'x', 'deep_link' => false]]]],
    'a client that will not say whether its code is a link' => [['clients' => [['device' => 'An iPhone', 'client' => 'Swiftfin', 'open_source' => true, 'code' => 'x', 'deep_link' => 'yes']]]],
    'no sessions' => [['sessions' => ['device' => 'A phone']]],
    'a session with no device' => [['sessions' => [['client' => 'Jellyfin Web']]]],
    'a session seen at a moment that is not words' => [['sessions' => [['device' => 'A phone', 'client' => 'Jellyfin Web', 'last_seen' => 7]]]],
]);

it('a payload that is not a hand-off at all is an answer it could not read', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'handoff', 'data' => 'nothing']))]);

    expect(whatBecameOfTheHandoff(new Connectors(new PinnedClients(), SequencedEntropy::counting())))->toEqual(KindOfObstacle::AnswerCouldNotBeRead->name);
});

it('the fake names each person asked for and each handle asked after', function (): void {
    $handing = AStackThatHandsDevicesOver::whichTookItOn(WhatBecameOfTheHandoff::ended());
    howTheHandoffWasAskedFor($handing);
    howTheHandoffWasAskedFor($handing);
    whatBecameOfTheHandoff($handing);

    expect($handing->asked())->toBe(['Sam', 'Sam'])
        ->and($handing->followed())->toHaveCount(1)
        ->and($handing->followed()[0]->shown())->toBe(AStackThatHandsDevicesOver::THE_JOB);
});

it('stands in for a stack with payloads the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('HandoffEnvelope', whatAStackAnswersAHandoffWith()))
        ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n");
});
