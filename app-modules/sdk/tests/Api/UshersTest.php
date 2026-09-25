<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function afterEach;
use function expect;
use function implode;
use function is_array;
use function is_string;
use function it;
use function json_encode;

use Lemonfiber\Sdk\Contract\Api;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AnAddressToHand;
use Modules\Kernel\Api\AnInvitation;
use Modules\Kernel\Api\AnInvitationAgreed;
use Modules\Kernel\Api\AnInvitationAskedFor;
use Modules\Kernel\Api\AnInvitationToHand;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\SomebodyInTheHousehold;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheLibraries;
use Modules\Kernel\Api\TheMembers;
use Modules\Kernel\Api\WhatBecameOfTheInvitation;
use Modules\Kernel\Api\WhatBecomesOfUnrated;
use Modules\Kernel\Api\WhereTheInvitationStands;
use Modules\Kernel\Api\WhetherTheyCanAsk;
use Modules\Kernel\Api\WhoWasTakenBack;
use Modules\Sdk\Api\PinnedClients;
use Modules\Sdk\Api\Ushers;
use RuntimeException;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\PendingRequest;

use function sprintf;
use function str_repeat;

use Tests\Support\Fakes\SequencedEntropy;
use Tests\Support\WhatTheContractAccepts;

/**
 * What the adapter puts on the wire for each act, and what it makes of every answer.
 *
 * `InvitingContractTest` holds it beside the fake; a fake dials nothing, so the
 * path, the body, the key and a refusal's own sentence are checked here.
 */
afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** The stack somebody is asked in to. */
function theStackSomebodyIsAskedInTo(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('e', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('f', Fingerprint::CHARACTERS)),
    );
}

/** The adapter, answering with whatever a case says, and the mock to read what it sent. */
function theUshersAnswering(MockResponse ...$answers): Ushers
{
    MockClient::destroyGlobal();
    MockClient::global($answers);

    return new Ushers(new PinnedClients(), SequencedEntropy::counting());
}

/** The mock the adapter was last set up to ask. */
function theMockTheUshersAsk(): MockClient
{
    return MockClient::getGlobal() ?? throw new RuntimeException('No answers were laid out, so this case read nothing.');
}

/** Whether the last request the adapter sent named the attempt it was part of. */
function itNamedTheAttempt(MockClient $mock): bool
{
    $key = theLastRequestSent($mock)->headers()->get(Api::IDEMPOTENCY_HEADER);

    return is_string($key) && $key !== '';
}

/** One word carried out of an arm. */
final readonly class WhereTheUshersGot
{
    public function __construct(public string $said) {}
}

/** Which arm an answer took, with what it carried. */
function whereItGot(WhatBecameOfTheInvitation $became): string
{
    return $became->either(
        underway: static fn(Job $job): WhereTheUshersGot => new WhereTheUshersGot(sprintf('underway:%s', $job->shown())),
        answered: static fn(AnInvitation $invitation): WhereTheUshersGot => new WhereTheUshersGot(sprintf('answered:%s:%s', $invitation->toHand()->name(), $invitation->standing()->value)),
        ended: static fn(): WhereTheUshersGot => new WhereTheUshersGot('ended'),
        refused: static fn(string $because): WhereTheUshersGot => new WhereTheUshersGot(sprintf('refused:%s', $because)),
        met: static fn(Obstacle $why): WhereTheUshersGot => new WhereTheUshersGot(sprintf('met:%s', $why->name)),
    )->said;
}

/**
 * The work taken on under the name `j-1`.
 *
 * @return array<string, mixed>
 */
function theHandleJ1(): array
{
    return ['api_version' => 1, 'kind' => 'job', 'data' => ['job' => 'j-1', 'action' => 'invite']];
}

/** A connection refused before any answer exists, as a stack that is off or out of reach meets the device. */
function nothingAnswering(): MockResponse
{
    return MockResponse::make()->throw(static fn(PendingRequest $asked): FatalRequestException
        => new FatalRequestException(new RuntimeException('Connection refused'), $asked));
}

/** A stack taking the work on under the name `j-1`. */
function aHandleNamedJ1(): MockResponse
{
    return MockResponse::make((string) json_encode(theHandleJ1()), 202);
}

/**
 * A refusal in the stack's own words.
 *
 * @return array<string, mixed>
 */
function aRefusalSaying(string $summary): array
{
    return [
        'api_version' => 1,
        'kind' => 'error',
        'data' => [
            'code' => 'LF-HOUSEHOLD-001',
            'summary' => $summary,
            'meaning' => 'The name is not one the media server holds',
            'remedies' => [],
            'severity' => 'error',
            'state' => 'actionable',
        ],
    ];
}

/** A refusal in the stack's own words, at the status given. */
function aRefusalAt(int $status, string $summary): MockResponse
{
    return MockResponse::make((string) json_encode(aRefusalSaying($summary)), $status, ['Content-Type' => 'application/json']);
}

/**
 * The invitation Anna's password being taken off came to, with any field of it said otherwise.
 *
 * @param  array<string, mixed> $otherwise
 * @return array<string, mixed>
 */
function thePasswordTakenOffAnna(array $otherwise = []): array
{
    return [
        'api_version' => 1,
        'kind' => 'invitation',
        'data' => [
            'name' => 'anna',
            'address' => 'http://loft.local:8096',
            'hours' => 72,
            'linked' => 'made',
            'rehearsed' => false,
            'standing' => 'reset',
            'withdrawn' => [],
            ...$otherwise,
        ],
    ];
}

/** What was asked for, with what it was asked. */
function anInvitationForAnnaAsked(): AnInvitationAskedFor
{
    return AnInvitationAskedFor::heldToAge(12, 'anna', TheLibraries::of('Films', 'Kids'))->withUnrated(WhatBecomesOfUnrated::HeldBack);
}

/** The rehearsal that request was answered with. */
function theRehearsalAnnaWasShown(): AnInvitation
{
    return AnInvitation::rehearsed(
        AnInvitationToHand::to('anna', AnAddressToHand::at('http://loft.local:8096', ''), 72),
        WhereTheInvitationStands::Made,
        WhetherTheyCanAsk::NotTried,
        WhoWasTakenBack::of(),
    );
}

/** The last request the adapter sent. */
function theLastRequestSent(MockClient $mock): PendingRequest
{
    return $mock->getLastPendingRequest() ?? throw new RuntimeException('The adapter sent nothing, so this case read nothing.');
}

/**
 * The body of the last request the adapter sent.
 *
 * @return array<mixed>
 */
function theBodySent(MockClient $mock): array
{
    $body = theLastRequestSent($mock)->body()?->all();

    return is_array($body) ? $body : [];
}

it('asks for the rehearsal at the invite action, with everything asked and no yes, and no key', function (): void {
    $ushers = theUshersAnswering(aHandleNamedJ1());
    $mock = theMockTheUshersAsk();

    expect(whereItGot($ushers->wouldInvite(theStackSomebodyIsAskedInTo(), Session::of('a-session-not-a-secret'), anInvitationForAnnaAsked())))->toBe('underway:j-1')
        ->and(theLastRequestSent($mock)->getUrl())->toEndWith('/api/actions/invite')
        ->and(theBodySent($mock))->toBe(['name' => 'anna', 'libraries' => ['Films', 'Kids'], 'confirm' => false, 'age_limit' => 12, 'unrated' => 'block'])
        ->and(itNamedTheAttempt($mock))->toBeFalse();
});

it('sends nothing for an age or for unrated material that was not said', function (): void {
    $ushers = theUshersAnswering(aHandleNamedJ1());
    $mock = theMockTheUshersAsk();

    $ushers->wouldInvite(theStackSomebodyIsAskedInTo(), Session::of('a-session-not-a-secret'), AnInvitationAskedFor::for('anna', TheLibraries::of()));

    expect(theBodySent($mock))->toBe(['name' => 'anna', 'libraries' => [], 'confirm' => false]);
});

it('sends the same request with the yes, under a key naming the attempt', function (): void {
    $ushers = theUshersAnswering(aHandleNamedJ1());
    $mock = theMockTheUshersAsk();
    $agreed = AnInvitationAgreed::after(anInvitationForAnnaAsked(), theRehearsalAnnaWasShown());

    expect(whereItGot($ushers->invite(theStackSomebodyIsAskedInTo(), Session::of('a-session-not-a-secret'), $agreed)))->toBe('underway:j-1')
        ->and(theLastRequestSent($mock)->getUrl())->toEndWith('/api/actions/invite')
        ->and(theBodySent($mock))->toBe(['name' => 'anna', 'libraries' => ['Films', 'Kids'], 'confirm' => true, 'age_limit' => 12, 'unrated' => 'block'])
        ->and(itNamedTheAttempt($mock))->toBeTrue();
});

it('takes a password off by naming the person and nothing else, under a key', function (): void {
    $ushers = theUshersAnswering(aHandleNamedJ1());
    $mock = theMockTheUshersAsk();

    expect(whereItGot($ushers->takeThePasswordOff(theStackSomebodyIsAskedInTo(), Session::of('a-session-not-a-secret'), SomebodyInTheHousehold::called('anna'))))->toBe('underway:j-1')
        ->and(theLastRequestSent($mock)->getUrl())->toEndWith('/api/actions/reissue')
        ->and(theBodySent($mock))->toBe(['name' => 'anna'])
        ->and(itNamedTheAttempt($mock))->toBeTrue();
});

it('hands on the stack\'s own sentence where it refuses the asking or the naming, and an obstacle otherwise', function (): void {
    $table = [
        [aRefusalAt(400, 'There is no library called Cartoons'), 'refused:There is no library called Cartoons'],
        [aRefusalAt(404, 'Nobody is called bob here'), 'refused:Nobody is called bob here'],
        [MockResponse::make('`unrated` is neither `block` nor `allow`', 400), 'refused:`unrated` is neither `block` nor `allow`'],
        [MockResponse::make('', 400), 'met:StackDidNotAnswer'],
        [aRefusalAt(502, 'The media server did not answer'), 'met:StackDidNotAnswer'],
        [aRefusalAt(399, 'Not a refusal'), 'met:StackDidNotAnswer'],
        [aRefusalAt(499, 'The last refusal there is'), 'refused:The last refusal there is'],
        [aRefusalAt(500, 'The first failure there is'), 'met:StackDidNotAnswer'],
        [aRefusalAt(401, 'Who are you'), 'met:CredentialWasRefused'],
        [aRefusalAt(403, 'Not you'), 'met:NotForThisAccount'],
        [MockResponse::make('not json at all', 202), 'met:StackDidNotAnswer'],
        [MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'job', 'data' => ['job' => ' ', 'action' => 'invite']]), 202), 'met:StackDidNotAnswer'],
        [MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'job', 'data' => ['action' => 'invite']]), 202), 'met:StackDidNotAnswer'],
        [MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'invitation', 'data' => []]), 202), 'met:StackDidNotAnswer'],
        [MockResponse::make((string) json_encode(['api_version' => 99, 'kind' => 'job', 'data' => ['job' => 'j-1', 'action' => 'invite']]), 202), 'met:StackDidNotAnswer'],
        [nothingAnswering(), 'met:StackDidNotAnswer'],
    ];

    foreach ($table as [$answer, $expected]) {
        $stack = theStackSomebodyIsAskedInTo();
        $session = Session::of('a-session-not-a-secret');
        $agreed = AnInvitationAgreed::after(anInvitationForAnnaAsked(), theRehearsalAnnaWasShown());

        expect(whereItGot(theUshersAnswering($answer)->wouldInvite($stack, $session, anInvitationForAnnaAsked())))->toBe($expected, sprintf('rehearsing: %s', $expected))
            ->and(whereItGot(theUshersAnswering($answer)->invite($stack, $session, $agreed)))->toBe($expected, sprintf('inviting: %s', $expected))
            ->and(whereItGot(theUshersAnswering($answer)->takeThePasswordOff($stack, $session, SomebodyInTheHousehold::called('anna'))))->toBe($expected, sprintf('resetting: %s', $expected));
    }
});

it('follows the work by its name to the invitation, and says when it is still going or has gone', function (): void {
    $table = [
        [MockResponse::make((string) json_encode(thePasswordTakenOffAnna())), 'answered:anna:reset'],
        [aHandleNamedJ1(), 'underway:j-1'],
        [MockResponse::make((string) json_encode(theHandleJ1())), 'ended'],
        [MockResponse::make('{"error":"no such job"}', 404), 'ended'],
        [aRefusalAt(404, 'Nobody is called bob here'), 'refused:Nobody is called bob here'],
        [aRefusalAt(401, 'Who are you'), 'met:CredentialWasRefused'],
        [MockResponse::make((string) json_encode(thePasswordTakenOffAnna(['standing' => 'declined']))), 'met:StackDidNotAnswer'],
        [MockResponse::make((string) json_encode(thePasswordTakenOffAnna(['hours' => -1]))), 'met:StackDidNotAnswer'],
        [MockResponse::make((string) json_encode([...thePasswordTakenOffAnna(), 'kind' => 'repair'])), 'met:StackDidNotAnswer'],
        [MockResponse::make('not json at all'), 'met:StackDidNotAnswer'],
    ];

    foreach ($table as [$answer, $expected]) {
        $ushers = theUshersAnswering($answer);
        $mock = theMockTheUshersAsk();

        expect(whereItGot($ushers->whatBecameOf(theStackSomebodyIsAskedInTo(), Session::of('a-session-not-a-secret'), Job::named('j-1'))))->toBe($expected, $expected)
            ->and(theLastRequestSent($mock)->getUrl())->toEndWith('/api/jobs/j-1');
    }
});

it('stands in for a stack with payloads the contract would accept', function (): void {
    // The spoiled bodies above are refused before anything reads them as a
    // stack's; these are the three a stack does send.
    expect(WhatTheContractAccepts::complaintsAbout('JobEnvelope', theHandleJ1()))->toBe([])
        ->and(WhatTheContractAccepts::complaintsAbout('ErrorEnvelope', aRefusalSaying('Nobody is called bob here')))->toBe([])
        ->and(WhatTheContractAccepts::complaintsAbout('InvitationEnvelope', thePasswordTakenOffAnna()))->toBe([]);
});

it('reads who is in off the household the operator\'s requests are read from, and answers what it cannot read', function (): void {
    $household = ['api_version' => 1, 'kind' => 'household', 'data' => ['available' => true, 'findings' => [], 'members' => [
        ['name' => 'anna', 'access' => ['administrator' => false, 'disabled' => false, 'every_library' => true, 'libraries' => [], 'restriction' => 'unrestricted', 'unrated' => 'let-through'], 'claimed' => true, 'to_hand_over' => [], 'requests' => []],
    ]]];
    $table = [
        [MockResponse::make((string) json_encode($household)), 'anna'],
        [MockResponse::make((string) json_encode([...$household, 'data' => [...$household['data'], 'available' => false]])), 'met:StackDidNotAnswer'],
        [MockResponse::make((string) json_encode([...$household, 'data' => [...$household['data'], 'members' => [['name' => ' ', 'claimed' => true]]]])), 'met:StackDidNotAnswer'],
        [aRefusalAt(401, 'Who are you'), 'met:CredentialWasRefused'],
        [MockResponse::make('not json at all'), 'met:StackDidNotAnswer'],
    ];

    foreach ($table as [$answer, $expected]) {
        $ushers = theUshersAnswering($answer);
        $mock = theMockTheUshersAsk();
        $said = $ushers->whoIsIn(theStackSomebodyIsAskedInTo(), Session::of('a-session-not-a-secret'))->either(
            found: static function (TheMembers $members): WhereTheUshersGot {
                $names = [];

                foreach ($members as $member) {
                    $names[] = $member->name();
                }

                return new WhereTheUshersGot(implode(',', $names));
            },
            met: static fn(Obstacle $why): WhereTheUshersGot => new WhereTheUshersGot(sprintf('met:%s', $why->name)),
        )->said;

        expect($said)->toBe($expected, $expected)
            ->and(theLastRequestSent($mock)->getUrl())->toEndWith('/api/requests');
    }

    expect(WhatTheContractAccepts::complaintsAbout('HouseholdEnvelope', $household))->toBe([]);
});

it('answers work it cannot ask after, and a household it cannot read, as a stack that did not answer', function (): void {
    $stack = theStackSomebodyIsAskedInTo();
    $session = Session::of('a-session-not-a-secret');

    $followed = whereItGot(theUshersAnswering(nothingAnswering())->whatBecameOf($stack, $session, Job::named('j-1')));
    $read = theUshersAnswering(nothingAnswering())->whoIsIn($stack, $session)->either(
        found: static fn(TheMembers $members): WhereTheUshersGot => new WhereTheUshersGot(sprintf('found:%d', $members->count())),
        met: static fn(Obstacle $why): WhereTheUshersGot => new WhereTheUshersGot(sprintf('met:%s', $why->name)),
    )->said;

    expect($followed)->toBe('met:StackDidNotAnswer')
        ->and($read)->toBe('met:StackDidNotAnswer');
});
