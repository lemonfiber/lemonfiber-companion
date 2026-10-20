<?php

declare(strict_types=1);

use Modules\Kernel\Api\AChallengeToSign;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AKeyClaim;
use Modules\Kernel\Api\AKeysName;
use Modules\Kernel\Api\AKeyToMint;
use Modules\Kernel\Api\AnAttestation;
use Modules\Kernel\Api\APhoneKey;
use Modules\Kernel\Api\APhoneKeysName;
use Modules\Kernel\Api\APhoneKeyToRegister;
use Modules\Kernel\Api\AProofOfThisPhone;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\ATextToSign;
use Modules\Kernel\Api\Credential;
use Modules\Kernel\Api\CredentialIsSpent;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\MintingKeysAsClaims;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\WhatAKeyAdmits;
use Modules\Kernel\Api\WhatAKeyIsFor;
use Modules\Kernel\Api\WhatMintingCameTo;
use Modules\Kernel\Api\WhatTheRefusalNamed;
use Modules\Sdk\Api\KeyMinters;
use Modules\Sdk\Api\PinnedClients;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AKeyPairInSoftware;
use Tests\Support\Fakes\AStackThatMintsKeys;
use Tests\Support\TheWordCarriedOut;
use Tests\Support\WhatTheContractAccepts;

// The MintingKeysAsClaims contract, run against the adapter and against the fake. A mint is proved
// end to end: a real P-256 key registered, a challenge issued, the text signed and the claim made.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

const THE_REFUSED_MINT = 'This stack mints no keys from a phone.';

/** Named for this file: the root suites share one namespace (G10). */
function aStackThatMints(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('m', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('c', Fingerprint::CHARACTERS)),
    );
}

/** The session minting is asked with. */
function aSessionThatMints(): Session
{
    return Session::of('a-session-not-a-secret');
}

/** The key the operator asks for. */
function aKeyForHomeAssistant(?WhatAKeyAdmits $admits = null): AKeyToMint
{
    return AKeyToMint::of(AKeysName::of('Home Assistant'), $admits ?? WhatAKeyAdmits::reading(), WhatAKeyIsFor::HomeAssistant);
}

/** The phone key's name. */
function thePhoneMinting(): APhoneKeysName
{
    return APhoneKeysName::of('Galaxy A51 · 7f3a');
}

/**
 * What a stack answers to each step, as the contract writes it.
 *
 * @param array<mixed> $data
 *
 * @return array<string, mixed>
 */
function whatAStackSaysWhenMinting(string $kind, array $data = []): array
{
    return match ($kind) {
        'device-key' => ['api_version' => 1, 'kind' => 'device-key', 'data' => [...['name' => 'Galaxy A51 · 7f3a', 'fingerprint' => str_repeat('a', 64), 'backing' => 'tee', 'app' => sprintf('android:app.lemonfiber.companion@%s', str_repeat('5a', 32)), 'registered' => AStackThatMintsKeys::REGISTERED], ...$data]],
        'device-challenge' => ['api_version' => 1, 'kind' => 'device-challenge', 'data' => [...['challenge' => AStackThatMintsKeys::CHALLENGE, 'expires' => '2026-10-13T21:01:00'], ...$data]],
        default => ['api_version' => 1, 'kind' => 'key-claim', 'data' => [...['name' => 'Home Assistant', 'scope' => 'read', 'purpose' => 'home-assistant', 'link' => AStackThatMintsKeys::LINK, 'lapses' => AStackThatMintsKeys::LAPSES], ...$data]],
    };
}

/**
 * Both ways of minting, the adapter answering these responses in order.
 *
 * @return array<string, Closure(): MintingKeysAsClaims>
 */
function everyWayOfMinting(MockResponse ...$answers): array
{
    return [
        'the fake' => static fn(): MintingKeysAsClaims => AStackThatMintsKeys::answering(),
        'the adapter' => static function () use ($answers): MintingKeysAsClaims {
            MockClient::destroyGlobal();
            MockClient::global($answers);

            return new KeyMinters(new PinnedClients());
        },
    ];
}

/**
 * What the stack answered, as one line.
 *
 * @param WhatMintingCameTo<object> $answered
 */
function whatTheMintingStackSaid(WhatMintingCameTo $answered): string
{
    return $answered->either(
        answered: static fn(object $said): TheWordCarriedOut => new TheWordCarriedOut(match (true) {
            $said instanceof APhoneKey => sprintf('registered %s in %s at %d', $said->name()->shown(), $said->lives()->value, $said->registered()->read(read: static fn(Instant $at): TheWordCarriedOut => new TheWordCarriedOut((string) $at->epochSeconds()), unreadable: static fn(): TheWordCarriedOut => new TheWordCarriedOut('-'))->said),
            $said instanceof AChallengeToSign => sprintf('challenge %s, signing %s', $said->challenge(), json_encode($said->text()->said())),
            $said instanceof AKeyClaim => sprintf('claim %s for %s', $said->carried(), $said->name()->shown()),
            default => 'something else',
        }),
        unregistered: static fn(): TheWordCarriedOut => new TheWordCarriedOut('unregistered'),
        refused: static fn(ARefusalInItsWords $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('refused: %s', $why->summary())),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->value),
    )->said;
}

/**
 * The challenge answered, to sign; a challenge nobody can sign where there was none.
 *
 * @param WhatMintingCameTo<AChallengeToSign> $answered
 */
function theChallengeAnswered(WhatMintingCameTo $answered): AChallengeToSign
{
    return $answered->either(
        answered: static fn(AChallengeToSign $said): AChallengeToSign => $said,
        unregistered: static fn(): AChallengeToSign => AChallengeToSign::of('unregistered', ATextToSign::saying('')),
        refused: static fn(): AChallengeToSign => AChallengeToSign::of('refused', ATextToSign::saying('')),
        met: static fn(): AChallengeToSign => AChallengeToSign::of('met', ATextToSign::saying('')),
    );
}

/** A key made in software, registered on the stack under the phone's name. */
function aPhoneKeyRegisteredOn(MintingKeysAsClaims $minting, AKeyPairInSoftware $pair): string
{
    return whatTheMintingStackSaid($minting->registeredOn(aStackThatMints(), aSessionThatMints(), APhoneKeyToRegister::of(thePhoneMinting(), $pair->publicKey(), AnAttestation::fromAndroid('the leaf', 'the root')), Credential::of('the operator password')));
}

it('registers this phone\'s key, and hands back the key as the stack registered it', function (): void {
    foreach (everyWayOfMinting(MockResponse::make(whatAStackSaysWhenMinting('device-key'))) as $which => $make) {
        expect(aPhoneKeyRegisteredOn($make(), AKeyPairInSoftware::made()))->toBe('registered Galaxy A51 · 7f3a in tee at 1791925200', $which);
    }
});

it('spends the password on the one request that registers the key', function (): void {
    foreach (everyWayOfMinting(MockResponse::make(whatAStackSaysWhenMinting('device-key'))) as $make) {
        $password = Credential::of('the operator password');
        $make()->registeredOn(aStackThatMints(), aSessionThatMints(), APhoneKeyToRegister::of(thePhoneMinting(), 'a public half', AnAttestation::fromApple('an object')), $password);

        expect(static fn(): string => $password->forTheExchange())->toThrow(CredentialIsSpent::class);
    }
});

it('hands back the challenge with the exact text the stack verifies a mint over', function (WhatAKeyAdmits $admits, string $scope): void {
    foreach (everyWayOfMinting(MockResponse::make(whatAStackSaysWhenMinting('device-key')), MockResponse::make(whatAStackSaysWhenMinting('device-challenge'))) as $which => $make) {
        $minting = $make();
        aPhoneKeyRegisteredOn($minting, AKeyPairInSoftware::made());

        expect(whatTheMintingStackSaid($minting->challengedOn(aStackThatMints(), aSessionThatMints(), thePhoneMinting(), aKeyForHomeAssistant($admits))))
            ->toBe(sprintf('challenge %s, signing %s', AStackThatMintsKeys::CHALLENGE, json_encode(sprintf("lemonfiber-mint/1\n%s\nHome Assistant\n%s\nhome-assistant\nclaim\n", AStackThatMintsKeys::CHALLENGE, $scope))), $which);
    }
})->with([
    'reading' => [WhatAKeyAdmits::reading(), 'read'],
    'acting' => [WhatAKeyAdmits::acting(), 'act'],
    'a member\'s own' => [WhatAKeyAdmits::aMembersOwn('ada'), 'member:ada'],
]);

it('mints a key as a claim over a signature of that text, and hands back the link', function (): void {
    $answers = [MockResponse::make(whatAStackSaysWhenMinting('device-key')), MockResponse::make(whatAStackSaysWhenMinting('device-challenge')), MockResponse::make(whatAStackSaysWhenMinting('key-claim'))];

    foreach (everyWayOfMinting(...$answers) as $which => $make) {
        $minting = $make();
        $pair = AKeyPairInSoftware::made();
        aPhoneKeyRegisteredOn($minting, $pair);
        $challenge = theChallengeAnswered($minting->challengedOn(aStackThatMints(), aSessionThatMints(), thePhoneMinting(), aKeyForHomeAssistant()));

        expect(whatTheMintingStackSaid($minting->claimedOn(aStackThatMints(), aSessionThatMints(), aKeyForHomeAssistant(), AProofOfThisPhone::of(thePhoneMinting(), $challenge, $pair->signed($challenge->text()->said())))))
            ->toBe(sprintf('claim %s for Home Assistant', AStackThatMintsKeys::LINK), $which);
    }
});

it('sends each step to its endpoint, a mint always as a claim with the phone\'s proof', function (): void {
    MockClient::destroyGlobal();
    $mock = MockClient::global([MockResponse::make(whatAStackSaysWhenMinting('device-key')), MockResponse::make(whatAStackSaysWhenMinting('device-challenge')), MockResponse::make(whatAStackSaysWhenMinting('key-claim'))]);
    $minting = new KeyMinters(new PinnedClients());
    $pair = AKeyPairInSoftware::made();

    aPhoneKeyRegisteredOn($minting, $pair);
    $registered = $mock->getLastPendingRequest();
    $challenge = theChallengeAnswered($minting->challengedOn(aStackThatMints(), aSessionThatMints(), thePhoneMinting(), aKeyForHomeAssistant()));
    $challenged = $mock->getLastPendingRequest();
    $minting->claimedOn(aStackThatMints(), aSessionThatMints(), aKeyForHomeAssistant(), AProofOfThisPhone::of(thePhoneMinting(), $challenge, 'a signature'));
    $claimed = $mock->getLastPendingRequest();

    expect(parse_url((string) $registered?->getUrl(), PHP_URL_PATH))->toBe('/api/device-keys')
        ->and($registered?->getMethod())->toBe(Method::POST)
        ->and(parse_url((string) $challenged?->getUrl(), PHP_URL_PATH))->toBe('/api/device-keys/Galaxy%20A51%20%C2%B7%207f3a/challenge')
        ->and(parse_url((string) $claimed?->getUrl(), PHP_URL_PATH))->toBe('/api/keys')
        ->and($claimed?->body()?->all())->toMatchArray(['name' => 'Home Assistant', 'scope' => 'read', 'purpose' => 'home-assistant', 'claim' => true, 'device' => 'Galaxy A51 · 7f3a', 'challenge' => AStackThatMintsKeys::CHALLENGE]);
});

it('refuses a mint over a signature the registered key does not make', function (): void {
    $minting = AStackThatMintsKeys::answering();
    aPhoneKeyRegisteredOn($minting, AKeyPairInSoftware::made());
    $challenge = theChallengeAnswered($minting->challengedOn(aStackThatMints(), aSessionThatMints(), thePhoneMinting(), aKeyForHomeAssistant()));

    expect(whatTheMintingStackSaid($minting->claimedOn(aStackThatMints(), aSessionThatMints(), aKeyForHomeAssistant(), AProofOfThisPhone::of(thePhoneMinting(), $challenge, AKeyPairInSoftware::made()->signed($challenge->text()->said())))))
        ->toBe(sprintf('refused: %s', AStackThatMintsKeys::WRONG_SIGNATURE))
        ->and($minting->claimed())->toBe([]);
});

it('says when the stack knows no phone key by this phone\'s name, on a challenge and on a mint', function (): void {
    $unregistered = MockResponse::make(['api_version' => 1, 'kind' => 'error', 'data' => ['code' => 'DEVICE-9', 'summary' => 'No device key goes by that name.', 'meaning' => 'It was never registered, or it was revoked.', 'severity' => 'error', 'state' => 'actionable', 'remedies' => []]], 404);
    $ways = [
        'the fake' => static function (): MintingKeysAsClaims {
            $minting = AStackThatMintsKeys::answering();
            aPhoneKeyRegisteredOn($minting, AKeyPairInSoftware::made());
            $minting->revoke(thePhoneMinting()->shown());

            return $minting;
        },
        'the adapter' => static function () use ($unregistered): MintingKeysAsClaims {
            MockClient::destroyGlobal();
            MockClient::global([$unregistered, $unregistered]);

            return new KeyMinters(new PinnedClients());
        },
    ];

    foreach ($ways as $which => $make) {
        $minting = $make();

        expect(whatTheMintingStackSaid($minting->challengedOn(aStackThatMints(), aSessionThatMints(), thePhoneMinting(), aKeyForHomeAssistant())))->toBe('unregistered', $which)
            ->and(whatTheMintingStackSaid($minting->claimedOn(aStackThatMints(), aSessionThatMints(), aKeyForHomeAssistant(), AProofOfThisPhone::of(thePhoneMinting(), AChallengeToSign::of('c', ATextToSign::saying('t')), 's'))))->toBe('unregistered', $which);
    }
});

it('says a refusal in the stack\'s words, and a stack it could not ask as what stood in the way', function (): void {
    $refusal = MockResponse::make(['api_version' => 1, 'kind' => 'error', 'data' => ['code' => 'DEVICE-1', 'summary' => THE_REFUSED_MINT, 'meaning' => 'Minting from a phone is off.', 'severity' => 'error', 'state' => 'actionable', 'remedies' => []]], 409);
    $ways = [
        'the fake' => static fn(): MintingKeysAsClaims => AStackThatMintsKeys::refusing(ARefusalInItsWords::said(THE_REFUSED_MINT, 'Minting from a phone is off.', WhatTheRefusalNamed::nothing())),
        'the adapter' => static function () use ($refusal): MintingKeysAsClaims {
            MockClient::destroyGlobal();
            MockClient::global([$refusal, $refusal, $refusal]);

            return new KeyMinters(new PinnedClients());
        },
    ];

    foreach ($ways as $which => $make) {
        $minting = $make();

        expect(aPhoneKeyRegisteredOn($minting, AKeyPairInSoftware::made()))->toBe(sprintf('refused: %s', THE_REFUSED_MINT), $which)
            ->and(whatTheMintingStackSaid($minting->challengedOn(aStackThatMints(), aSessionThatMints(), thePhoneMinting(), aKeyForHomeAssistant())))->toBe(sprintf('refused: %s', THE_REFUSED_MINT), $which)
            ->and(whatTheMintingStackSaid($minting->claimedOn(aStackThatMints(), aSessionThatMints(), aKeyForHomeAssistant(), AProofOfThisPhone::of(thePhoneMinting(), AChallengeToSign::of('c', ATextToSign::saying('t')), 's'))))->toBe(sprintf('refused: %s', THE_REFUSED_MINT), $which);
    }

    $ways = [
        'the fake' => static fn(): MintingKeysAsClaims => AStackThatMintsKeys::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)),
        'the adapter' => static function (): MintingKeysAsClaims {
            MockClient::destroyGlobal();
            MockClient::global([MockResponse::make('{"error":"gone"}', 500)]);

            return new KeyMinters(new PinnedClients());
        },
    ];

    foreach ($ways as $which => $make) {
        expect(aPhoneKeyRegisteredOn($make(), AKeyPairInSoftware::made()))->toBe(KindOfObstacle::StackDidNotAnswer->value, $which);
    }
});

it('reads an answer it cannot trust as one it could not read', function (string $kind, array $data, string $step): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make(whatAStackSaysWhenMinting($kind, $data))]);
    $minting = new KeyMinters(new PinnedClients());

    $said = match ($step) {
        'register' => aPhoneKeyRegisteredOn($minting, AKeyPairInSoftware::made()),
        'challenge' => whatTheMintingStackSaid($minting->challengedOn(aStackThatMints(), aSessionThatMints(), thePhoneMinting(), aKeyForHomeAssistant())),
        default => whatTheMintingStackSaid($minting->claimedOn(aStackThatMints(), aSessionThatMints(), aKeyForHomeAssistant(), AProofOfThisPhone::of(thePhoneMinting(), AChallengeToSign::of('c', ATextToSign::saying('t')), 's'))),
    };

    expect($said)->toBe(KindOfObstacle::AnswerCouldNotBeRead->value);
})->with([
    'a key living somewhere unknown' => ['device-key', ['backing' => 'software'], 'register'],
    'a key with no name' => ['device-key', ['name' => ' '], 'register'],
    'a challenge that is blank' => ['device-challenge', ['challenge' => ''], 'challenge'],
    'a challenge holding a line feed' => ['device-challenge', ['challenge' => "a\nb"], 'challenge'],
    'a claim with no link' => ['key-claim', ['link' => ''], 'claim'],
    'a claim with no lapse' => ['key-claim', ['lapses' => ''], 'claim'],
]);

it('reads a payload that is not a table as one it could not read', function (string $kind, string $step): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make([...whatAStackSaysWhenMinting($kind), 'data' => 'nothing'])]);
    $minting = new KeyMinters(new PinnedClients());

    $said = match ($step) {
        'register' => aPhoneKeyRegisteredOn($minting, AKeyPairInSoftware::made()),
        'challenge' => whatTheMintingStackSaid($minting->challengedOn(aStackThatMints(), aSessionThatMints(), thePhoneMinting(), aKeyForHomeAssistant())),
        default => whatTheMintingStackSaid($minting->claimedOn(aStackThatMints(), aSessionThatMints(), aKeyForHomeAssistant(), AProofOfThisPhone::of(thePhoneMinting(), AChallengeToSign::of('c', ATextToSign::saying('t')), 's'))),
    };

    expect($said)->toBe(KindOfObstacle::AnswerCouldNotBeRead->value);
})->with([
    'device-key' => ['device-key', 'register'],
    'device-challenge' => ['device-challenge', 'challenge'],
    'key-claim' => ['key-claim', 'claim'],
]);

it('refuses a mint whose name could not be signed over', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([]);

    expect(whatTheMintingStackSaid(new KeyMinters(new PinnedClients())->challengedOn(aStackThatMints(), aSessionThatMints(), thePhoneMinting(), AKeyToMint::of(AKeysName::of("Home\nAssistant"), WhatAKeyAdmits::reading(), WhatAKeyIsFor::HomeAssistant))))
        ->toBe(KindOfObstacle::AnswerCouldNotBeRead->value);
});

it('stands in for a stack with payloads the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('DeviceKeyEnvelope', whatAStackSaysWhenMinting('device-key')))->toBe([])
        ->and(WhatTheContractAccepts::complaintsAbout('DeviceChallengeEnvelope', whatAStackSaysWhenMinting('device-challenge')))->toBe([])
        ->and(WhatTheContractAccepts::complaintsAbout('KeyClaimEnvelope', whatAStackSaysWhenMinting('key-claim')))->toBe([]);
});
