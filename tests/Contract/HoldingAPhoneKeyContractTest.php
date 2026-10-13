<?php

declare(strict_types=1);

use Modules\Device\Api\PlatformPhoneKeys;
use Modules\Kernel\Api\AnAttestation;
use Modules\Kernel\Api\APhoneKeyMade;
use Modules\Kernel\Api\ATextToSign;
use Modules\Kernel\Api\HoldingAPhoneKey;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\WhatForgettingAPhoneKeyFound;
use Modules\Kernel\Api\WhatSigningCameTo;
use Modules\Kernel\Api\WhereAPhoneKeyLives;
use Modules\Kernel\Api\WhereThisPhoneCanKeepAKey;
use Modules\Kernel\Api\WhyTheKeySignedNothing;
use Modules\Kernel\Api\WhyThisPhoneHoldsNoKey;
use Native\Mobile\Testing\FakeBridge;
use Tests\Support\Catalogue;
use Tests\Support\Fakes\AKeyPairInSoftware;
use Tests\Support\Fakes\APhoneKeyInMemory;
use Tests\Support\Fakes\APhoneKeyOnAHandset;
use Tests\Support\TheWordCarriedOut;

// The HoldingAPhoneKey contract, run against the adapter over a scripted handset and against the
// fake. Both hold real P-256 keys, so every signature below is verified for real against the public
// half the same implementation made.

beforeEach(function (): void {
    FakeBridge::disable();
});

/**
 * The adapter, over a bridge scripted to answer the way this handset would.
 *
 * Named for this file: the root suites share one namespace (`G10`).
 */
function overAHandsetsKeyStore(APhoneKeyOnAHandset $handset): PlatformPhoneKeys
{
    return $handset->theAdapterOverIt();
}

/**
 * Every implementation of the port that keeps keys, on each platform.
 *
 * @return array<string, Closure(): HoldingAPhoneKey>
 */
function everyPhoneThatKeepsKeys(): array
{
    return [
        'the fake, on Android' => static fn(): HoldingAPhoneKey => APhoneKeyInMemory::onAnAndroidPhone(),
        'the fake, on an iPhone' => static fn(): HoldingAPhoneKey => APhoneKeyInMemory::onAnIphone(),
        'the adapter, on Android' => static fn(): HoldingAPhoneKey => overAHandsetsKeyStore(APhoneKeyOnAHandset::onAnAndroidPhone()),
        'the adapter, on an iPhone' => static fn(): HoldingAPhoneKey => overAHandsetsKeyStore(APhoneKeyOnAHandset::onAnIphone()),
    ];
}

/**
 * Every implementation of the port on a phone that keeps none, for this reason.
 *
 * @return array<string, Closure(): HoldingAPhoneKey>
 */
function everyPhoneKeepingNone(WhyThisPhoneHoldsNoKey $why): array
{
    return [
        'the fake' => static fn(): HoldingAPhoneKey => APhoneKeyInMemory::keepingNone($why),
        'the adapter' => static fn(): HoldingAPhoneKey => overAHandsetsKeyStore(APhoneKeyOnAHandset::keepingNone($why)),
    ];
}

/**
 * Every implementation of the port whose prompts end this way.
 *
 * @return array<string, Closure(): HoldingAPhoneKey>
 */
function everyPhoneDeclining(WhyTheKeySignedNothing $why): array
{
    return [
        'the fake' => static fn(): HoldingAPhoneKey => APhoneKeyInMemory::onAnAndroidPhone()->declining($why),
        'the adapter' => static fn(): HoldingAPhoneKey => overAHandsetsKeyStore(APhoneKeyOnAHandset::onAnAndroidPhone()->declining($why)),
    ];
}

/** A stack the keys are made for. */
function aStackForAPhoneKey(string $which = 'a stack'): StackId
{
    return StackId::rememberedAs($which);
}

/** The public half of the key made, or the case name of why none was. */
function thePublicHalfMade(APhoneKeyMade $made): string
{
    return $made->either(
        made: static fn(WhereAPhoneKeyLives $where, string $publicKey): TheWordCarriedOut => new TheWordCarriedOut($publicKey),
        not: static fn(WhyThisPhoneHoldsNoKey $why): TheWordCarriedOut => new TheWordCarriedOut($why->name),
    )->said;
}

/** The signature, or the case name of why there is none. */
function theSignatureMade(WhatSigningCameTo $signed): string
{
    return $signed->either(
        signed: static fn(string $signature): TheWordCarriedOut => new TheWordCarriedOut($signature),
        not: static fn(WhyTheKeySignedNothing $why): TheWordCarriedOut => new TheWordCarriedOut($why->name),
    )->said;
}

/** Where a key could live, or the case name of why nowhere. */
function whereAPhoneKeyCouldLive(WhereThisPhoneCanKeepAKey $place): string
{
    return $place->either(
        in: static fn(WhereAPhoneKeyLives $where): TheWordCarriedOut => new TheWordCarriedOut($where->value),
        nowhere: static fn(WhyThisPhoneHoldsNoKey $why): TheWordCarriedOut => new TheWordCarriedOut($why->name),
    )->said;
}

it('signs with the key it made, behind a signature the public half verifies', function (): void {
    $text = 'lemonfiber key mint v1 a challenge';

    foreach (everyPhoneThatKeepsKeys() as $which => $make) {
        $phone = $make();
        $publicKey = thePublicHalfMade($phone->madeFor(aStackForAPhoneKey()));
        $signature = theSignatureMade($phone->signedFor(aStackForAPhoneKey(), ATextToSign::saying($text)));

        expect(AKeyPairInSoftware::verifies($publicKey, $text, $signature))->toBeTrue($which)
            ->and(AKeyPairInSoftware::verifies($publicKey, 'another text', $signature))->toBeFalse($which);
    }
});

it('hands back the attestation its platform makes', function (): void {
    $expected = [
        'the fake, on Android' => 'android',
        'the fake, on an iPhone' => 'apple',
        'the adapter, on Android' => 'android',
        'the adapter, on an iPhone' => 'apple',
    ];

    foreach (everyPhoneThatKeepsKeys() as $which => $make) {
        $platform = $make()->madeFor(aStackForAPhoneKey())->either(
            made: static fn(WhereAPhoneKeyLives $where, string $publicKey, AnAttestation $attestation): TheWordCarriedOut => $attestation->either(
                android: static fn(array $chain): TheWordCarriedOut => new TheWordCarriedOut($chain === [] ? 'an empty chain' : 'android'),
                apple: static fn(string $object): TheWordCarriedOut => new TheWordCarriedOut($object === '' ? 'nothing' : 'apple'),
            ),
            not: static fn(WhyThisPhoneHoldsNoKey $why): TheWordCarriedOut => new TheWordCarriedOut($why->name),
        )->said;

        expect($platform)->toBe($expected[$which], $which);
    }
});

it('says where a key could live', function (): void {
    foreach (everyPhoneThatKeepsKeys() as $which => $make) {
        expect(whereAPhoneKeyCouldLive($make()->whereItCouldLive()))->not->toBe('', $which)
            ->and(WhereAPhoneKeyLives::tryFrom(whereAPhoneKeyCouldLive($make()->whereItCouldLive())))->not->toBeNull($which);
    }
});

it('says why a phone could keep no key, and makes none', function (): void {
    foreach (WhyThisPhoneHoldsNoKey::cases() as $why) {
        foreach (everyPhoneKeepingNone($why) as $which => $make) {
            $phone = $make();

            expect(whereAPhoneKeyCouldLive($phone->whereItCouldLive()))->toBe($why->name, $which)
                ->and(thePublicHalfMade($phone->madeFor(aStackForAPhoneKey())))->toBe($why->name, $which);
        }
    }
});

it('says why nothing was signed', function (): void {
    foreach (WhyTheKeySignedNothing::cases() as $why) {
        foreach (everyPhoneDeclining($why) as $which => $make) {
            $phone = $make();
            $phone->madeFor(aStackForAPhoneKey());

            expect(theSignatureMade($phone->signedFor(aStackForAPhoneKey(), ATextToSign::saying('a text'))))
                ->toBe($why->name, $which);
        }
    }
});

it('holds one key per stack, and forgetting one leaves the others', function (): void {
    foreach (everyPhoneThatKeepsKeys() as $which => $make) {
        $phone = $make();
        $first = thePublicHalfMade($phone->madeFor(aStackForAPhoneKey('the first')));
        $second = thePublicHalfMade($phone->madeFor(aStackForAPhoneKey('the second')));

        expect($first)->not->toBe($second, $which)
            ->and($phone->forgetFor(aStackForAPhoneKey('the first')))->toBe(WhatForgettingAPhoneKeyFound::ThrownAway, $which)
            ->and(theSignatureMade($phone->signedFor(aStackForAPhoneKey('the first'), ATextToSign::saying('a text'))))->toBe('Gone', $which)
            ->and(AKeyPairInSoftware::verifies($second, 'a text', theSignatureMade($phone->signedFor(aStackForAPhoneKey('the second'), ATextToSign::saying('a text')))))->toBeTrue($which);
    }
});

it('replaces the key it held when it makes another', function (): void {
    foreach (everyPhoneThatKeepsKeys() as $which => $make) {
        $phone = $make();
        $before = thePublicHalfMade($phone->madeFor(aStackForAPhoneKey()));
        $after = thePublicHalfMade($phone->madeFor(aStackForAPhoneKey()));
        $signature = theSignatureMade($phone->signedFor(aStackForAPhoneKey(), ATextToSign::saying('a text')));

        expect(AKeyPairInSoftware::verifies($after, 'a text', $signature))->toBeTrue($which)
            ->and(AKeyPairInSoftware::verifies($before, 'a text', $signature))->toBeFalse($which);
    }
});

it('says whether forgetting found a key to throw away', function (): void {
    foreach (everyPhoneThatKeepsKeys() as $which => $make) {
        $phone = $make();
        $phone->madeFor(aStackForAPhoneKey());

        expect($phone->forgetFor(aStackForAPhoneKey()))->toBe(WhatForgettingAPhoneKeyFound::ThrownAway, $which)
            ->and($phone->forgetFor(aStackForAPhoneKey()))->toBe(WhatForgettingAPhoneKeyFound::NoneHeld, $which);
    }
});

it('never says a key it could not throw away is gone', function (): void {
    $phones = [
        'the fake' => static fn(): HoldingAPhoneKey => APhoneKeyInMemory::onAnAndroidPhone()->holdingOnToItsKeys(),
        'the adapter' => static fn(): HoldingAPhoneKey => overAHandsetsKeyStore(APhoneKeyOnAHandset::onAnAndroidPhone()->holdingOnToItsKeys()),
    ];

    foreach ($phones as $which => $make) {
        $phone = $make();
        $phone->madeFor(aStackForAPhoneKey());

        expect($phone->forgetFor(aStackForAPhoneKey()))->toBe(WhatForgettingAPhoneKeyFound::StillHeld, $which);
    }
});

it('captions the prompt with this application\'s own sentence', function (): void {
    $handset = APhoneKeyOnAHandset::onAnAndroidPhone();
    $phone = overAHandsetsKeyStore($handset);

    $phone->madeFor(aStackForAPhoneKey());
    $phone->signedFor(aStackForAPhoneKey(), ATextToSign::saying('a text'));

    expect($handset->whatItSaid())->toBe([Catalogue::words()->for('device.phone_key_reason')])
        ->and($handset->whatItSaid()[0])->not->toBe('device.phone_key_reason');
});

it('files each stack\'s key under an alias of its own', function (): void {
    $handset = APhoneKeyOnAHandset::onAnAndroidPhone();
    $phone = overAHandsetsKeyStore($handset);

    $phone->madeFor(aStackForAPhoneKey('the first'));
    $phone->madeFor(aStackForAPhoneKey('the second'));

    expect($handset->aliasesMade())->toBe(['lemonfiber.phone-key.the first', 'lemonfiber.phone-key.the second']);
});

it('throws away a key the phone says lives somewhere no stack accepts', function (): void {
    $handset = APhoneKeyOnAHandset::inAPlaceNoStackAccepts();
    $phone = overAHandsetsKeyStore($handset);

    expect(thePublicHalfMade($phone->madeFor(aStackForAPhoneKey())))->toBe('Failed')
        ->and($handset->holds('lemonfiber.phone-key.a stack'))->toBeFalse()
        ->and(whereAPhoneKeyCouldLive($phone->whereItCouldLive()))->toBe('Failed');
});

it('names a new key for what the phone is called, and four characters that tell two apart', function (): void {
    foreach (everyPhoneThatKeepsKeys() as $which => $make) {
        expect($make()->nameForANewKey()->shown())->toMatch('/\A\S.* · [0-9a-f]{4}\z/u', $which);
    }
});

it('draws the four characters afresh for every key', function (): void {
    $phone = overAHandsetsKeyStore(APhoneKeyOnAHandset::onAnAndroidPhone());
    $names = [];

    foreach (range(1, 8) as $ignored) {
        $names[] = $phone->nameForANewKey()->shown();
    }

    expect(array_unique($names))->not->toHaveCount(1);
});

it('names a key by its four characters alone where the phone says nothing of itself', function (): void {
    expect(overAHandsetsKeyStore(APhoneKeyOnAHandset::onAnAndroidPhone()->called(''))->nameForANewKey()->shown())
        ->toMatch('/\A[0-9a-f]{4}\z/');
});

it('throws a stack\'s key away with the stack, and says while it holds one', function (): void {
    foreach (everyPhoneThatKeepsKeys() as $which => $make) {
        $phone = $make();
        $phone->madeFor(aStackForAPhoneKey());

        expect($phone->keepsAnythingOf(aStackForAPhoneKey()))->toBeTrue($which)
            ->and($phone->keepsAnythingOf(aStackForAPhoneKey('another')))->toBeFalse($which)
            ->and($phone->forgetTheStack(aStackForAPhoneKey())->howMany())->toBe(1, $which)
            ->and($phone->keepsAnythingOf(aStackForAPhoneKey()))->toBeFalse($which)
            ->and($phone->forgetTheStack(aStackForAPhoneKey())->howMany())->toBe(0, $which);
    }
});
