<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AKeyForAnApp;
use Modules\Kernel\Api\AKeysName;
use Modules\Kernel\Api\AMomentAsWritten;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KeepingKeysForApps;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheKeysForApps;
use Modules\Kernel\Api\WhatAKeyAdmits;
use Modules\Kernel\Api\WhatAKeyIsFor;
use Modules\Kernel\Api\WhatWasFoundOfTheKeys;
use Modules\Kernel\Api\WhereAKeyStands;
use Modules\Sdk\Api\KeyKeepers;
use Modules\Sdk\Api\PinnedClients;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AStackThatKeepsKeys;
use Tests\Support\TheWordCarriedOut;
use Tests\Support\WhatTheContractAccepts;

// The KeepingKeysForApps contract, run against the adapter and against the fake.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

const A_KEYS_SENTENCE = 'A purpose is what whoever minted the key said; this machine cannot check it.';

function aStackKeepingKeys(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('k', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('c', Fingerprint::CHARACTERS)),
    );
}

/** What both implementations list. */
function theSameKeys(WhereAKeyStands $den = WhereAKeyStands::Active): TheKeysForApps
{
    return TheKeysForApps::of(
        A_KEYS_SENTENCE,
        AKeyForAnApp::listed(AKeysName::of('den'), WhatAKeyAdmits::acting(), WhatAKeyIsFor::HomeAssistant, $den, AMomentAsWritten::of('2026-10-01T09:00:00Z'), AMomentAsWritten::of('2026-10-11T08:30:00Z'), memberMinted: false),
        AKeyForAnApp::listed(AKeysName::of('sanne-assistant'), WhatAKeyAdmits::aMembersOwn('Sanne'), WhatAKeyIsFor::AnAssistant, WhereAKeyStands::Waiting, AMomentAsWritten::of('2026-10-11T09:00:00Z'), AMomentAsWritten::of(''), memberMinted: true),
    );
}

/**
 * The payload a stack lists those keys with.
 *
 * @return array<string, mixed>
 */
function whatAStackSaysOfItsKeys(string $denStands = 'active', string $denScope = 'act'): array
{
    return [
        'api_version' => 1,
        'kind' => 'keys',
        'data' => [
            'purposes' => A_KEYS_SENTENCE,
            'rehearsed' => false,
            'revoked' => $denStands === 'revoked' ? 'den' : null,
            'keys' => [
                ['name' => 'den', 'scope' => $denScope, 'purpose' => 'home-assistant', 'state' => $denStands, 'minted' => '2026-10-01T09:00:00', 'used' => '2026-10-11T08:30:00', 'member_minted' => false, 'revoked' => $denStands === 'revoked' ? '2026-10-11T10:00:00' : null],
                ['name' => 'sanne-assistant', 'scope' => 'member:Sanne', 'purpose' => 'mcp', 'state' => 'waiting', 'minted' => '2026-10-11T09:00:00', 'used' => null, 'member_minted' => true, 'revoked' => null],
            ],
        ],
    ];
}

/**
 * Both ways of keeping keys, each set up to answer the same.
 *
 * @return array<string, Closure(): KeepingKeysForApps>
 */
function everyWayOfKeepingKeys(MockResponse $answered, ?Obstacle $why = null): array
{
    return [
        'the fake' => static fn(): KeepingKeysForApps => $why instanceof Obstacle ? AStackThatKeepsKeys::met($why) : AStackThatKeepsKeys::listing(theSameKeys()),
        'the adapter' => static function () use ($answered): KeepingKeysForApps {
            MockClient::destroyGlobal();
            MockClient::global([$answered]);

            return new KeyKeepers(new PinnedClients());
        },
    ];
}

/** Every key, folded to one line, so two answers can be compared. */
function everythingTheKeysSay(WhatWasFoundOfTheKeys $found): string
{
    return $found->either(
        found: static function (TheKeysForApps $keys): TheWordCarriedOut {
            $lines = [$keys->purposes()];

            foreach ($keys as $key) {
                $lines[] = sprintf(
                    '%s|%s|%s|%s|%s|%s|%s',
                    $key->name()->shown(),
                    $key->admits()->either(reading: static fn(): TheWordCarriedOut => new TheWordCarriedOut('read'), acting: static fn(): TheWordCarriedOut => new TheWordCarriedOut('act'), member: static fn(string $member): TheWordCarriedOut => new TheWordCarriedOut(sprintf('member:%s', $member)))->said,
                    $key->isFor()->value,
                    $key->stands()->value,
                    $key->minted()->read(read: static fn(Instant $at): TheWordCarriedOut => new TheWordCarriedOut((string) $at->epochSeconds()), unreadable: static fn(): TheWordCarriedOut => new TheWordCarriedOut('-'))->said,
                    $key->used()->read(read: static fn(Instant $at): TheWordCarriedOut => new TheWordCarriedOut((string) $at->epochSeconds()), unreadable: static fn(): TheWordCarriedOut => new TheWordCarriedOut('never'))->said,
                    $key->wasMintedByAMember() ? 'by a member' : 'by the operator',
                );
            }

            return new TheWordCarriedOut(implode("\n", $lines));
        },
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->value),
    )->said;
}

it('lists every key with what it admits and is for, where it stands, when it was made and used, and who made it', function (): void {
    foreach (everyWayOfKeepingKeys(MockResponse::make(whatAStackSaysOfItsKeys())) as $which => $make) {
        expect(everythingTheKeysSay($make()->listedOn(aStackKeepingKeys(), Session::of('a-session-not-a-secret'))))->toBe(
            sprintf("%s\n%s\n%s", A_KEYS_SENTENCE, 'den|act|home-assistant|active|1790845200|1791707400|by the operator', 'sanne-assistant|member:Sanne|mcp|waiting|1791709200|never|by a member'),
            $which,
        );
    }
});

it('revokes a key by name and comes away with the listing as it stands after', function (): void {
    foreach (everyWayOfKeepingKeys(MockResponse::make(whatAStackSaysOfItsKeys('revoked'))) as $which => $make) {
        $said = everythingTheKeysSay($make()->revokedOn(aStackKeepingKeys(), Session::of('a-session-not-a-secret'), AKeysName::of('den')));

        expect(str_contains($said, 'den|act|home-assistant|revoked|'))->toBeTrue($which);
    }
});

it('sends a revoke as a DELETE for the key it names, and a listing to the keys endpoint alone', function (): void {
    MockClient::destroyGlobal();
    $mock = MockClient::global([MockResponse::make(whatAStackSaysOfItsKeys()), MockResponse::make(whatAStackSaysOfItsKeys('revoked'))]);
    $keeping = new KeyKeepers(new PinnedClients());

    $keeping->listedOn(aStackKeepingKeys(), Session::of('a-session-not-a-secret'));
    $listed = $mock->getLastPendingRequest();
    $keeping->revokedOn(aStackKeepingKeys(), Session::of('a-session-not-a-secret'), AKeysName::of('the den'));
    $revoked = $mock->getLastPendingRequest();

    expect(parse_url((string) $listed?->getUrl(), PHP_URL_PATH))->toBe('/api/keys')
        ->and($listed?->getMethod())->toBe(Method::GET)
        ->and(parse_url((string) $revoked?->getUrl(), PHP_URL_PATH))->toBe('/api/keys/the%20den')
        ->and($revoked?->getMethod())->toBe(Method::DELETE);
});

it('tells a session that has ended from a stack that is not answering, and a key it cannot read from either', function (): void {
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::of(KindOfObstacle::CredentialWasRefused)],
        [MockResponse::make('{"error":"gone"}', 500), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
        [MockResponse::make(whatAStackSaysOfItsKeys('expired')), Obstacle::of(KindOfObstacle::AnswerCouldNotBeRead)],
        [MockResponse::make(whatAStackSaysOfItsKeys(denScope: 'admin')), Obstacle::of(KindOfObstacle::AnswerCouldNotBeRead)],
        [MockResponse::make(whatAStackSaysOfItsKeys(denScope: 'member: ')), Obstacle::of(KindOfObstacle::AnswerCouldNotBeRead)],
    ];

    foreach ($table as [$answered, $why]) {
        foreach (everyWayOfKeepingKeys($answered, $why) as $which => $make) {
            expect(everythingTheKeysSay($make()->listedOn(aStackKeepingKeys(), Session::of('a-session-not-a-secret'))))->toBe($why->kind()->value, sprintf('%s / %s', $which, $why->kind()->value));
        }
    }
});

it('says a revoke the stack refused as what stood in the way', function (): void {
    $answered = MockResponse::make(['api_version' => 1, 'kind' => 'error', 'data' => ['code' => 'KEY-10', 'summary' => 'No key is called that.', 'meaning' => 'There is nothing to revoke.', 'severity' => 'error', 'state' => 'actionable', 'remedies' => []]], 404);

    $ways = [
        'the fake' => static fn(): KeepingKeysForApps => AStackThatKeepsKeys::refusingToRevoke(theSameKeys(), Obstacle::of(KindOfObstacle::StackDidNotAnswer)),
        'the adapter' => static function () use ($answered): KeepingKeysForApps {
            MockClient::destroyGlobal();
            MockClient::global([$answered]);

            return new KeyKeepers(new PinnedClients());
        },
    ];

    foreach ($ways as $which => $make) {
        expect(everythingTheKeysSay($make()->revokedOn(aStackKeepingKeys(), Session::of('a-session-not-a-secret'), AKeysName::of('gone'))))->toBe(KindOfObstacle::StackDidNotAnswer->value, $which);
    }
});

it('stands in for a stack with a payload the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('KeysEnvelope', whatAStackSaysOfItsKeys()))
        ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n");
});
