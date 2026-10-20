<?php

declare(strict_types=1);

use Lemonfiber\Native\PhoneKeyMade;
use Lemonfiber\Native\PhoneKeyPlace;
use Lemonfiber\Native\PhoneKeys;
use Lemonfiber\Native\PhoneKeySigned;
use Lemonfiber\Native\WhatForgettingAKeyFound;
use Lemonfiber\Native\WhyNoPhoneKey;
use Lemonfiber\Native\WhyNothingWasSigned;
use Native\Mobile\Testing\FakeBridge;

beforeEach(function (): void {
    FakeBridge::disable();
});

/** These bytes as the device writes them. */
function inBase64(string $bytes): string
{
    return sodium_bin2base64($bytes, SODIUM_BASE64_VARIANT_ORIGINAL);
}

/** Where the phone said a key could live, or the word for why not. */
function wherePhoneKeysCouldLive(PhoneKeyPlace $place): string
{
    return $place->either(
        lives: static fn(string $where): string => sprintf('lives %s', $where),
        not: static fn(WhyNoPhoneKey $why): string => sprintf('not %s', $why->value),
    );
}

/** What making a key came to, as one line naming the arm and what it carried. */
function whatMakingAPhoneKeyGave(PhoneKeyMade $made): string
{
    return $made->either(
        android: static fn(string $lives, string $publicKey, array $chain): string
            => sprintf('android %s %s %s', $lives, $publicKey, implode('+', $chain)),
        ios: static fn(string $lives, string $publicKey, string $attestation): string
            => sprintf('ios %s %s %s', $lives, $publicKey, $attestation),
        not: static fn(WhyNoPhoneKey $why): string => sprintf('not %s', $why->value),
    );
}

/** The signature, or the word for why there is none. */
function whatAPhoneKeySigned(PhoneKeySigned $signed): string
{
    return $signed->either(
        signed: static fn(string $signature): string => sprintf('signed %s', $signature),
        not: static fn(WhyNothingWasSigned $why): string => sprintf('not %s', $why->value),
    );
}

it('asks where a key could live without carrying anything', function (): void {
    $bridge = FakeBridge::enable()->respondTo('Lemonfiber.PhoneKey.Where', ['outcome' => 'can', 'lives' => 'strongbox']);

    expect(wherePhoneKeysCouldLive(new PhoneKeys()->where()))->toBe('lives strongbox');

    $bridge->assertCalled('Lemonfiber.PhoneKey.Where');
});

it('hands back why a phone could keep no key', function (): void {
    FakeBridge::enable()->respondTo('Lemonfiber.PhoneKey.Where', ['outcome' => 'cannot', 'because' => 'no_biometric']);

    expect(wherePhoneKeysCouldLive(new PhoneKeys()->where()))->toBe('not no_biometric');
});

it('reads a machine that is not a handset as a phone with nowhere to keep one', function (): void {
    expect(wherePhoneKeysCouldLive(new PhoneKeys()->where()))->toBe('not no_hardware');
});

it('reads a reason it does not know as a failure worth trying again', function (): void {
    FakeBridge::enable()->respondTo('Lemonfiber.PhoneKey.Where', ['outcome' => 'cannot', 'because' => 'the moon is full']);

    expect(wherePhoneKeysCouldLive(new PhoneKeys()->where()))->toBe('not failed');
});

it('makes the key under the alias named, and decodes what Android attested', function (): void {
    $bridge = FakeBridge::enable()->respondTo('Lemonfiber.PhoneKey.Make', [
        'outcome' => 'made',
        'lives' => 'tee',
        'public_key' => inBase64('the public half'),
        'chain' => [inBase64('the leaf'), inBase64('the root')],
    ]);

    expect(whatMakingAPhoneKeyGave(new PhoneKeys()->make('a stack')))
        ->toBe('android tee the public half the leaf+the root');

    $bridge->assertCalled('Lemonfiber.PhoneKey.Make', static fn(array $sent): bool => $sent === ['alias' => 'a stack']);
});

it('decodes what App Attest attested', function (): void {
    FakeBridge::enable()->respondTo('Lemonfiber.PhoneKey.Make', [
        'outcome' => 'made',
        'lives' => 'enclave',
        'public_key' => inBase64('the public half'),
        'attestation' => inBase64('the object'),
    ]);

    expect(whatMakingAPhoneKeyGave(new PhoneKeys()->make('a stack')))->toBe('ios enclave the public half the object');
});

it('reads a key made with no attestation as one nothing vouched for', function (array $answer): void {
    FakeBridge::enable()->respondTo('Lemonfiber.PhoneKey.Make', [
        'outcome' => 'made',
        'lives' => 'tee',
        'public_key' => inBase64('the public half'),
        ...$answer,
    ]);

    expect(whatMakingAPhoneKeyGave(new PhoneKeys()->make('a stack')))->toBe('not unattested');
})->with([
    'nothing at all' => [[]],
    'a chain with a gap in it' => [['chain' => [inBase64('the leaf'), 7]]],
    'a chain that is not base64' => [['chain' => ['not base64 at all!']]],
    'an empty attestation' => [['attestation' => '']],
]);

it('reads a made key with no usable public half as a failure', function (array $answer): void {
    FakeBridge::enable()->respondTo('Lemonfiber.PhoneKey.Make', [
        'outcome' => 'made',
        'attestation' => inBase64('the object'),
        ...$answer,
    ]);

    expect(whatMakingAPhoneKeyGave(new PhoneKeys()->make('a stack')))->toBe('not failed');
})->with([
    'no public half' => [['lives' => 'enclave']],
    'a public half that is not base64' => [['lives' => 'enclave', 'public_key' => 'not base64 at all!']],
    'nowhere it lives' => [['public_key' => inBase64('the public half')]],
]);

it('hands back why no key was made', function (): void {
    FakeBridge::enable()->respondTo('Lemonfiber.PhoneKey.Make', ['outcome' => 'not_made', 'because' => 'in_software']);

    expect(whatMakingAPhoneKeyGave(new PhoneKeys()->make('a stack')))->toBe('not in_software');
});

it('reads a machine that is not a handset as a key that failed to be made', function (): void {
    expect(whatMakingAPhoneKeyGave(new PhoneKeys()->make('a stack')))->toBe('not failed');
});

it('signs with the key named, captioning the prompt with the sentence given', function (): void {
    $bridge = FakeBridge::enable()->respondTo('Lemonfiber.PhoneKey.Sign', [
        'outcome' => 'signed',
        'signature' => inBase64('the signature'),
    ]);

    expect(whatAPhoneKeySigned(new PhoneKeys()->sign('a stack', 'the text', 'Prove it is you')))
        ->toBe('signed the signature');

    $bridge->assertCalled(
        'Lemonfiber.PhoneKey.Sign',
        static fn(array $sent): bool => $sent === ['alias' => 'a stack', 'text' => 'the text', 'reason' => 'Prove it is you'],
    );
});

it('hands back why nothing was signed', function (string $because): void {
    FakeBridge::enable()->respondTo('Lemonfiber.PhoneKey.Sign', ['outcome' => 'not_signed', 'because' => $because]);

    expect(whatAPhoneKeySigned(new PhoneKeys()->sign('a stack', 'the text', '')))->toBe(sprintf('not %s', $because));
})->with(['cancelled', 'gone', 'failed']);

it('reads a signing that answered nothing usable as a failure', function (array $answer): void {
    FakeBridge::enable()->respondTo('Lemonfiber.PhoneKey.Sign', $answer);

    expect(whatAPhoneKeySigned(new PhoneKeys()->sign('a stack', 'the text', '')))->toBe('not failed');
})->with([
    'a signature that is not base64' => [['outcome' => 'signed', 'signature' => 'not base64 at all!']],
    'no signature' => [['outcome' => 'signed']],
    'a reason it does not know' => [['outcome' => 'not_signed', 'because' => 'the moon is full']],
    'no reason' => [['outcome' => 'not_signed']],
]);

it('reads a machine that is not a handset as a signing that failed', function (): void {
    expect(whatAPhoneKeySigned(new PhoneKeys()->sign('a stack', 'the text', '')))->toBe('not failed');
});

it('throws the key named away, and says whether one was there', function (string $outcome, WhatForgettingAKeyFound $found): void {
    $bridge = FakeBridge::enable()->respondTo('Lemonfiber.PhoneKey.Forget', ['outcome' => $outcome]);

    expect(new PhoneKeys()->forget('a stack'))->toBe($found);

    $bridge->assertCalled('Lemonfiber.PhoneKey.Forget', static fn(array $sent): bool => $sent === ['alias' => 'a stack']);
})->with([
    'one was' => ['forgotten', WhatForgettingAKeyFound::Forgotten],
    'none was' => ['none_held', WhatForgettingAKeyFound::NoneHeld],
]);

it('never reads a key the phone would not throw away as gone', function (array $answer): void {
    FakeBridge::enable()->respondTo('Lemonfiber.PhoneKey.Forget', $answer);

    expect(new PhoneKeys()->forget('a stack'))->toBe(WhatForgettingAKeyFound::Failed);
})->with([
    'a refusal' => [['outcome' => 'not_forgotten', 'because' => 'failed']],
    'a word it does not know' => [['outcome' => 'the moon is full']],
]);

it('reads a machine that is not a handset as holding no key to throw away', function (): void {
    expect(new PhoneKeys()->forget('a stack'))->toBe(WhatForgettingAKeyFound::NoneHeld);
});

it('asks whether the key named is held', function (string $outcome, bool $held): void {
    $bridge = FakeBridge::enable()->respondTo('Lemonfiber.PhoneKey.Held', ['outcome' => $outcome]);

    expect(new PhoneKeys()->holds('a stack'))->toBe($held);

    $bridge->assertCalled('Lemonfiber.PhoneKey.Held', static fn(array $sent): bool => $sent === ['alias' => 'a stack']);
})->with([
    'held' => ['held', true],
    'none held' => ['none_held', false],
    'a phone that could not tell' => ['unknown', true],
    'a word it does not know' => ['the moon is full', true],
]);

it('reads a machine that is not a handset as holding no key', function (): void {
    expect(new PhoneKeys()->holds('a stack'))->toBeFalse();
});

it('asks what this phone is called, carrying nothing', function (): void {
    $bridge = FakeBridge::enable()->respondTo('Lemonfiber.PhoneKey.Model', ['outcome' => 'named', 'model' => ' Galaxy A51 ']);

    expect(new PhoneKeys()->model())->toBe('Galaxy A51');

    $bridge->assertCalled('Lemonfiber.PhoneKey.Model');
});

it('reads a phone that names nothing as having no name', function (array $answer): void {
    FakeBridge::enable()->respondTo('Lemonfiber.PhoneKey.Model', $answer);

    expect(new PhoneKeys()->model())->toBe('');
})->with([
    'no model' => [['outcome' => 'named']],
    'another word' => [['outcome' => 'cannot', 'model' => 'Galaxy A51']],
]);

it('reads a machine that is not a handset as having no name', function (): void {
    expect(new PhoneKeys()->model())->toBe('');
});
