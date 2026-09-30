<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\APairingCode;
use Modules\Kernel\Api\APairingLine;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\MakingPairingCodes;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\WhatBecameOfThePairingCode;
use Modules\Sdk\Api\Pairers;
use Modules\Sdk\Api\PinnedClients;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AStackThatMakesPairingCodes;
use Tests\Support\WhatTheContractAccepts;

// The MakingPairingCodes contract, run against the adapter and against the fake.
//
// `TakingCopiesContractTest`'s shape for the same kind of act: an asking that
// answers a handle, and a following that answers the code — with a refusal in
// the stack's own words beside it, since a stack not served encrypted on the
// network is the ordinary way asking for one comes to nothing.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** The stack a code is asked of. */
function aStackAskedForACode(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('c', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('d', Fingerprint::CHARACTERS)),
    );
}

/** The line a stack writes, as the one a code carries. */
const THE_LINE = '{"address":"https://den.local:8443","fingerprint":"abab","expires":1790813400,"stack":"000102030405060708090a0b0c0d0e0f"}';

/**
 * The payload a stack answers a made code with, changed where a case says.
 *
 * @param  array<mixed>         $changed
 * @return array<string, mixed>
 */
function whatAStackAnswersACodeWith(array $changed = []): array
{
    return ['api_version' => 1, 'kind' => 'pairing', 'data' => [
        'material' => [
            'address' => 'https://den.local:8443',
            'fingerprint' => str_repeat('ab', 32),
            'expires' => 1_790_813_400,
            'stack' => '000102030405060708090a0b0c0d0e0f',
        ],
        'written' => THE_LINE,
        'compare' => '22VK-KPHH-NKH9-TUWA',
        'until' => '2026-10-01T00:10:00',
        'replacing' => 'The certificate this address presents was made by lemonfiber and nothing renews it.',
        'caution' => 'That address is a number.',
        ...$changed,
    ]];
}

/** The code that payload stands for, as the fake is handed it. */
function theSameCode(): APairingCode
{
    return APairingCode::made(
        APairingLine::asWritten(THE_LINE),
        '22VK-KPHH-NKH9-TUWA',
        Instant::atEpochSeconds(1_790_813_400),
        'https://den.local:8443',
        'That address is a number.',
    );
}

/**
 * Both ways of asking for a code, each set up to say the same.
 *
 * @return array<string, Closure(): MakingPairingCodes>
 */
function everyWayOfAskingForACode(MockResponse $answered, WhatBecameOfThePairingCode $became): array
{
    return [
        'the fake' => static fn(): MakingPairingCodes => AStackThatMakesPairingCodes::whichTookItOn($became),
        'the adapter' => static function () use ($answered): MakingPairingCodes {
            MockClient::destroyGlobal();
            MockClient::global([$answered]);

            return new Pairers(new PinnedClients());
        },
    ];
}

/** One line carried out of an `either()` arm. */
final readonly class WhatAskingForACodeSaid
{
    public function __construct(public string $said) {}
}

/** Any answer about a code, folded to one line. */
function saidOfTheCode(WhatBecameOfThePairingCode $became): string
{
    return $became->either(
        underway: static fn(Job $job): WhatAskingForACodeSaid => new WhatAskingForACodeSaid(sprintf('following %s', $job->shown())),
        made: static fn(APairingCode $code): WhatAskingForACodeSaid => new WhatAskingForACodeSaid(sprintf(
            '%s|%s|%d|%s|%s|%s',
            $code->line()->carried(),
            $code->compare(),
            $code->expiresAt()->epochSeconds(),
            $code->address(),
            $code->caution(),
            $code->hasExpiredBy(Instant::atEpochSeconds(1_790_813_400)) ? 'expired at its time' : 'still good at its time',
        )),
        ended: static fn(): WhatAskingForACodeSaid => new WhatAskingForACodeSaid('ended'),
        refused: static fn(string $because): WhatAskingForACodeSaid => new WhatAskingForACodeSaid(sprintf('refused %s', $because)),
        met: static fn(Obstacle $why): WhatAskingForACodeSaid => new WhatAskingForACodeSaid($why->name),
    )->said;
}

/** What asking for a code came to. */
function howTheCodeWasAskedFor(MakingPairingCodes $pairing): string
{
    return saidOfTheCode($pairing->make(aStackAskedForACode(), Session::of('a-session-not-a-secret')));
}

/** What asking after a code came to. */
function whatBecameOfTheCode(MakingPairingCodes $pairing): string
{
    return saidOfTheCode($pairing->whatBecameOf(aStackAskedForACode(), Session::of('a-session-not-a-secret'), Job::named(AStackThatMakesPairingCodes::THE_JOB)));
}

/** What a stack answers an asking it took on with. */
function aCodeTakenOn(): MockResponse
{
    return MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'job', 'data' => ['action' => 'companion-pair', 'job' => AStackThatMakesPairingCodes::THE_JOB]]), 202);
}

it('comes away from asking for a code with the job the stack named', function (): void {
    foreach (everyWayOfAskingForACode(aCodeTakenOn(), WhatBecameOfThePairingCode::ended()) as $which => $build) {
        expect(howTheCodeWasAskedFor($build()))->toBe(sprintf('following %s', AStackThatMakesPairingCodes::THE_JOB), $which);
    }
});

it('reads the code the stack made: the line, the compare code, the moment it stops being good, and the address with its caution', function (): void {
    $made = MockResponse::make((string) json_encode(whatAStackAnswersACodeWith()));

    foreach (everyWayOfAskingForACode($made, WhatBecameOfThePairingCode::made(theSameCode())) as $which => $build) {
        expect(whatBecameOfTheCode($build()))
            ->toBe(sprintf('%s|22VK-KPHH-NKH9-TUWA|1790813400|https://den.local:8443|That address is a number.|expired at its time', THE_LINE), $which);
    }
});

it('reads an address with nothing to say about it as having no caution', function (mixed $caution): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(whatAStackAnswersACodeWith(['caution' => $caution])))]);

    expect(whatBecameOfTheCode(new Pairers(new PinnedClients())))->toEndWith('https://den.local:8443||expired at its time');
})->with(['null' => [null]]);

it('hands on a refusal in the stack\'s own words, asking or following', function (): void {
    $said = 'lemonfiber has not been served encrypted on your network, so a phone has nothing to reach';
    $refused = static fn(): MockResponse => MockResponse::make($said, 409, ['Content-Type' => 'text/plain']);
    $fake = AStackThatMakesPairingCodes::answering(WhatBecameOfThePairingCode::refused($said));

    MockClient::destroyGlobal();
    MockClient::global([$refused()]);
    expect(howTheCodeWasAskedFor(new Pairers(new PinnedClients())))->toBe(sprintf('refused %s', $said))
        ->and(howTheCodeWasAskedFor($fake))->toBe(sprintf('refused %s', $said));

    MockClient::destroyGlobal();
    MockClient::global([$refused()]);
    expect(whatBecameOfTheCode(new Pairers(new PinnedClients())))->toBe(sprintf('refused %s', $said))
        ->and(whatBecameOfTheCode($fake))->toBe(sprintf('refused %s', $said));
});

it('tells a refused session and a silent stack from a refusal', function (): void {
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::CredentialWasRefused],
        [MockResponse::make('{"error":"gone"}', 500), Obstacle::StackDidNotAnswer],
        [MockResponse::make('not json at all'), Obstacle::StackDidNotAnswer],
    ];

    foreach ($table as [$answered, $why]) {
        foreach (everyWayOfAskingForACode($answered, WhatBecameOfThePairingCode::met($why)) as $which => $build) {
            expect(whatBecameOfTheCode($build()))->toBe($why->name, $which);
        }

        MockClient::destroyGlobal();
        MockClient::global([$answered]);
        expect(howTheCodeWasAskedFor(new Pairers(new PinnedClients())))->toBe($why->name);
        expect(howTheCodeWasAskedFor(AStackThatMakesPairingCodes::answering(WhatBecameOfThePairingCode::met($why))))->toBe($why->name);
    }
});

it('an asking answered with a handle it cannot follow is a stack that did not answer', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'job', 'data' => ['action' => 'companion-pair', 'job' => ' ']]), 202)]);

    expect(howTheCodeWasAskedFor(new Pairers(new PinnedClients())))->toBe(Obstacle::StackDidNotAnswer->name);
});

it('a code still being made is its own answer, and one the stack forgot is ended', function (MockResponse $answered, string $said): void {
    $became = $said === 'ended' ? WhatBecameOfThePairingCode::ended() : WhatBecameOfThePairingCode::underway(Job::named(AStackThatMakesPairingCodes::THE_JOB));

    foreach (everyWayOfAskingForACode($answered, $became) as $which => $build) {
        expect(whatBecameOfTheCode($build()))->toBe($said, $which);
    }
})->with([
    'still running' => [aCodeTakenOn(), sprintf('following %s', AStackThatMakesPairingCodes::THE_JOB)],
    'let go of' => [MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'job', 'data' => ['action' => 'companion-pair', 'job' => AStackThatMakesPairingCodes::THE_JOB]])), 'ended'],
    'never known' => [MockResponse::make('{"error":"no such job"}', 404), 'ended'],
]);

it('a code this app cannot read is a stack that did not answer, never a code with a part missing', function (array $changed): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(whatAStackAnswersACodeWith($changed)))]);

    expect(whatBecameOfTheCode(new Pairers(new PinnedClients())))->toBe(Obstacle::StackDidNotAnswer->name);
})->with([
    'no material' => [['material' => 'here']],
    'no expiry' => [['material' => ['address' => 'https://den.local:8443', 'expires' => 'soon']]],
    'no address' => [['material' => ['expires' => 1_790_813_400]]],
    'a blank line' => [['written' => '  ']],
    'no line' => [['written' => null]],
    'a blank compare code' => [['compare' => ' ']],
    'a caution that is not words' => [['caution' => 7]],
]);

it('a payload that is not a pairing at all is a stack that did not answer', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'pairing', 'data' => 'nothing']))]);

    expect(whatBecameOfTheCode(new Pairers(new PinnedClients())))->toBe(Obstacle::StackDidNotAnswer->name);
});

it('the fake counts each asking and names the handle each following asked by', function (): void {
    $pairing = AStackThatMakesPairingCodes::whichTookItOn(WhatBecameOfThePairingCode::ended());
    howTheCodeWasAskedFor($pairing);
    howTheCodeWasAskedFor($pairing);
    whatBecameOfTheCode($pairing);

    expect($pairing->asked())->toBe(2)
        ->and($pairing->followed())->toHaveCount(1)
        ->and($pairing->followed()[0]->shown())->toBe(AStackThatMakesPairingCodes::THE_JOB);
});

it('stands in for a stack with payloads the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('PairingEnvelope', whatAStackAnswersACodeWith()))
        ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n");
});
