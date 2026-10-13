<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AMomentAsWritten;
use Modules\Kernel\Api\APhoneKey;
use Modules\Kernel\Api\APhoneKeysName;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KeepingPhoneKeys;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\ThePhoneKeys;
use Modules\Kernel\Api\WhatTheRefusalNamed;
use Modules\Kernel\Api\WhatWasFoundOfThePhoneKeys;
use Modules\Kernel\Api\WhereAPhoneKeyLives;
use Modules\Sdk\Api\PhoneKeyKeepers;
use Modules\Sdk\Api\PinnedClients;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AStackThatKeepsPhoneKeys;
use Tests\Support\TheWordCarriedOut;
use Tests\Support\WhatTheContractAccepts;

// The KeepingPhoneKeys contract, run against the adapter and against the fake.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

const THE_REFUSED_REVOKE = 'No phone key is called that.';

function aStackKeepingPhoneKeys(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('p', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('c', Fingerprint::CHARACTERS)),
    );
}

/** What both implementations list. */
function theSamePhoneKeys(): ThePhoneKeys
{
    return ThePhoneKeys::of(
        APhoneKey::standing(APhoneKeysName::of('Galaxy A51 · 7f3a'), WhereAPhoneKeyLives::TrustedEnvironment, AMomentAsWritten::of('2026-10-01T09:00:00Z'), AMomentAsWritten::of('2026-10-11T08:30:00Z')),
        APhoneKey::standing(APhoneKeysName::of('iPhone 12 mini · 01bc'), WhereAPhoneKeyLives::Enclave, AMomentAsWritten::of('2026-10-11T09:00:00Z'), AMomentAsWritten::of('')),
    );
}

/**
 * The payload a stack lists those phone keys with.
 *
 * @return array<string, mixed>
 */
function whatAStackSaysOfItsPhoneKeys(bool $revoked = false, string $backing = 'tee', mixed $first = null, bool $listing = true): array
{
    $said = [
        'api_version' => 1,
        'kind' => 'device-keys',
        'data' => [
            'rehearsed' => false,
            'revoked' => $revoked ? 'Galaxy A51 · 7f3a' : null,
            'keys' => [
                $first ?? ['name' => 'Galaxy A51 · 7f3a', 'fingerprint' => str_repeat('a', 64), 'backing' => $backing, 'app' => sprintf('android:app.lemonfiber.companion@%s', str_repeat('5a', 32)), 'registered' => '2026-10-01T09:00:00', 'used' => '2026-10-11T08:30:00', 'revoked' => $revoked ? '2026-10-13T10:00:00' : null],
                ['name' => 'iPhone 12 mini · 01bc', 'fingerprint' => str_repeat('b', 64), 'backing' => 'enclave', 'app' => 'ios:NV5645J73B.app.lemonfiber.companion', 'registered' => '2026-10-11T09:00:00', 'used' => null, 'revoked' => null],
            ],
        ],
    ];

    if (! $listing) {
        $said['data'] = ['rehearsed' => false, 'revoked' => null];
    }

    return $said;
}

/**
 * Both ways of keeping phone keys, each set up to answer the same.
 *
 * @return array<string, Closure(): KeepingPhoneKeys>
 */
function everyWayOfKeepingPhoneKeys(MockResponse $answered, ?Obstacle $why = null): array
{
    return [
        'the fake' => static fn(): KeepingPhoneKeys => $why instanceof Obstacle ? AStackThatKeepsPhoneKeys::met($why) : AStackThatKeepsPhoneKeys::listing(theSamePhoneKeys()),
        'the adapter' => static function () use ($answered): KeepingPhoneKeys {
            MockClient::destroyGlobal();
            MockClient::global([$answered]);

            return new PhoneKeyKeepers(new PinnedClients());
        },
    ];
}

function aMomentSaid(AMomentAsWritten $moment, string $none): string
{
    return $moment->read(read: static fn(Instant $at): TheWordCarriedOut => new TheWordCarriedOut((string) $at->epochSeconds()), unreadable: static fn(): TheWordCarriedOut => new TheWordCarriedOut($none))->said;
}

/** Every phone key, folded to one line, so two answers can be compared. */
function everythingThePhoneKeysSay(WhatWasFoundOfThePhoneKeys $found): string
{
    return $found->either(
        found: static function (ThePhoneKeys $keys): TheWordCarriedOut {
            $lines = [];

            foreach ($keys as $key) {
                $lines[] = sprintf(
                    '%s|%s|%s|%s|%s',
                    $key->name()->shown(),
                    $key->lives()->value,
                    aMomentSaid($key->registered(), '-'),
                    aMomentSaid($key->used(), 'never'),
                    $key->wasRevoked() ? sprintf('revoked %s', aMomentSaid($key->whenRevoked(), '-')) : 'standing',
                );
            }

            return new TheWordCarriedOut(implode("\n", $lines));
        },
        refused: static fn(ARefusalInItsWords $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('refused: %s', $why->summary())),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->value),
    )->said;
}

it('lists every phone key with where it lives, when it was registered and last used', function (): void {
    foreach (everyWayOfKeepingPhoneKeys(MockResponse::make(whatAStackSaysOfItsPhoneKeys())) as $which => $make) {
        expect(everythingThePhoneKeysSay($make()->listedOn(aStackKeepingPhoneKeys(), Session::of('a-session-not-a-secret'))))->toBe(
            "Galaxy A51 · 7f3a|tee|1790845200|1791707400|standing\niPhone 12 mini · 01bc|enclave|1791709200|never|standing",
            $which,
        );
    }
});

it('revokes a phone key by name and comes away with the listing as it stands after', function (): void {
    foreach (everyWayOfKeepingPhoneKeys(MockResponse::make(whatAStackSaysOfItsPhoneKeys(revoked: true))) as $which => $make) {
        $said = everythingThePhoneKeysSay($make()->revokedOn(aStackKeepingPhoneKeys(), Session::of('a-session-not-a-secret'), APhoneKeysName::of('Galaxy A51 · 7f3a')));

        expect($said)->toStartWith('Galaxy A51 · 7f3a|tee|1790845200|1791707400|revoked 1791885600', $which)
            ->and($said)->toEndWith('iPhone 12 mini · 01bc|enclave|1791709200|never|standing', $which);
    }
});

it('sends a revoke as a DELETE for the phone key it names, and a listing to the device keys endpoint alone', function (): void {
    MockClient::destroyGlobal();
    $mock = MockClient::global([MockResponse::make(whatAStackSaysOfItsPhoneKeys()), MockResponse::make(whatAStackSaysOfItsPhoneKeys(revoked: true))]);
    $keeping = new PhoneKeyKeepers(new PinnedClients());

    $keeping->listedOn(aStackKeepingPhoneKeys(), Session::of('a-session-not-a-secret'));
    $listed = $mock->getLastPendingRequest();
    $keeping->revokedOn(aStackKeepingPhoneKeys(), Session::of('a-session-not-a-secret'), APhoneKeysName::of('the den'));
    $revoked = $mock->getLastPendingRequest();

    expect(parse_url((string) $listed?->getUrl(), PHP_URL_PATH))->toBe('/api/device-keys')
        ->and($listed?->getMethod())->toBe(Method::GET)
        ->and(parse_url((string) $revoked?->getUrl(), PHP_URL_PATH))->toBe('/api/device-keys/the%20den')
        ->and($revoked?->getMethod())->toBe(Method::DELETE);
});

it('tells a session that has ended from a stack that is not answering, and a phone key it cannot read from either', function (): void {
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::of(KindOfObstacle::CredentialWasRefused)],
        [MockResponse::make('{"error":"gone"}', 500), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
        [MockResponse::make(whatAStackSaysOfItsPhoneKeys(backing: 'software')), Obstacle::of(KindOfObstacle::AnswerCouldNotBeRead)],
        [MockResponse::make(whatAStackSaysOfItsPhoneKeys(first: ['name' => ' ', 'fingerprint' => str_repeat('a', 64), 'backing' => 'tee', 'app' => 'ios:NV5645J73B.app.lemonfiber.companion', 'registered' => '2026-10-01T09:00:00'])), Obstacle::of(KindOfObstacle::AnswerCouldNotBeRead)],
        [MockResponse::make(whatAStackSaysOfItsPhoneKeys(first: 'Galaxy A51')), Obstacle::of(KindOfObstacle::AnswerCouldNotBeRead)],
        [MockResponse::make(whatAStackSaysOfItsPhoneKeys(listing: false)), Obstacle::of(KindOfObstacle::AnswerCouldNotBeRead)],
        [MockResponse::make(['api_version' => 1, 'kind' => 'device-keys', 'data' => 'keys']), Obstacle::of(KindOfObstacle::AnswerCouldNotBeRead)],
        [MockResponse::make(['api_version' => 1, 'kind' => 'device-keys', 'data' => ['rehearsed' => false, 'revoked' => null, 'keys' => ['first' => ['name' => 'iPhone 12 mini · 01bc', 'fingerprint' => str_repeat('b', 64), 'backing' => 'enclave', 'app' => 'ios:NV5645J73B.app.lemonfiber.companion', 'registered' => '2026-10-11T09:00:00', 'used' => null, 'revoked' => null]]]]), Obstacle::of(KindOfObstacle::AnswerCouldNotBeRead)],
    ];

    foreach ($table as [$answered, $why]) {
        foreach (everyWayOfKeepingPhoneKeys($answered, $why) as $which => $make) {
            expect(everythingThePhoneKeysSay($make()->listedOn(aStackKeepingPhoneKeys(), Session::of('a-session-not-a-secret'))))->toBe($why->kind()->value, sprintf('%s / %s', $which, $why->kind()->value));
        }
    }
});

it('says a revoke the stack refused in its words, and one it could not be asked as what stood in the way', function (): void {
    $refusal = MockResponse::make(['api_version' => 1, 'kind' => 'error', 'data' => ['code' => 'DEVICE-9', 'summary' => THE_REFUSED_REVOKE, 'meaning' => 'There is nothing to revoke.', 'severity' => 'error', 'state' => 'actionable', 'remedies' => []]], 404);

    $ways = [
        'the fake' => static fn(): KeepingPhoneKeys => AStackThatKeepsPhoneKeys::refusingToRevoke(theSamePhoneKeys(), ARefusalInItsWords::said(THE_REFUSED_REVOKE, 'There is nothing to revoke.', WhatTheRefusalNamed::nothing())),
        'the adapter' => static function () use ($refusal): KeepingPhoneKeys {
            MockClient::destroyGlobal();
            MockClient::global([$refusal]);

            return new PhoneKeyKeepers(new PinnedClients());
        },
    ];

    foreach ($ways as $which => $make) {
        expect(everythingThePhoneKeysSay($make()->revokedOn(aStackKeepingPhoneKeys(), Session::of('a-session-not-a-secret'), APhoneKeysName::of('gone'))))->toBe(sprintf('refused: %s', THE_REFUSED_REVOKE), $which);
    }

    foreach (everyWayOfKeepingPhoneKeys(MockResponse::make('{"error":"gone"}', 500), Obstacle::of(KindOfObstacle::StackDidNotAnswer)) as $which => $make) {
        expect(everythingThePhoneKeysSay($make()->revokedOn(aStackKeepingPhoneKeys(), Session::of('a-session-not-a-secret'), APhoneKeysName::of('gone'))))->toBe(KindOfObstacle::StackDidNotAnswer->value, $which);
    }
});

it('the fake remembers each revoke', function (): void {
    $keeping = AStackThatKeepsPhoneKeys::listing(theSamePhoneKeys());
    $keeping->revokedOn(aStackKeepingPhoneKeys(), Session::of('a-session-not-a-secret'), APhoneKeysName::of('Galaxy A51 · 7f3a'));

    expect($keeping->revoked())->toBe(['Galaxy A51 · 7f3a']);
});

it('stands in for a stack with a payload the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('DeviceKeysEnvelope', whatAStackSaysOfItsPhoneKeys()))
        ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n")
        ->and(WhatTheContractAccepts::complaintsAbout('DeviceKeysEnvelope', whatAStackSaysOfItsPhoneKeys(revoked: true)))->toBe([]);
});
