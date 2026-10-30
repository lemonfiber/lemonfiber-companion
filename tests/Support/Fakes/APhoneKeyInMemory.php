<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use function array_key_exists;

use Modules\Kernel\Api\AnAttestation;
use Modules\Kernel\Api\APhoneKeyMade;
use Modules\Kernel\Api\APhoneKeysName;
use Modules\Kernel\Api\ATextToSign;
use Modules\Kernel\Api\Forgotten;
use Modules\Kernel\Api\HoldingAPhoneKey;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\WhatForgettingAPhoneKeyFound;
use Modules\Kernel\Api\WhatSigningCameTo;
use Modules\Kernel\Api\WhereAPhoneKeyLives;
use Modules\Kernel\Api\WhereThisPhoneCanKeepAKey;
use Modules\Kernel\Api\WhyTheKeySignedNothing;
use Modules\Kernel\Api\WhyThisPhoneHoldsNoKey;

/**
 * A phone's key store in memory, holding real P-256 keys made by OpenSSL.
 *
 * Signatures it answers with verify against the public half it made, so a suite can prove a claim
 * end to end without a handset.
 */
final class APhoneKeyInMemory implements HoldingAPhoneKey
{
    /** @var array<string, AKeyPairInSoftware> */
    private array $held = [];

    private ?WhyTheKeySignedNothing $declined = null;

    private bool $stuck = false;

    private int $looked = 0;

    private function __construct(
        private readonly WhereAPhoneKeyLives|WhyThisPhoneHoldsNoKey $place,
        private readonly bool $android,
    ) {}

    /** An Android phone with a StrongBox, attesting with a certificate chain. */
    public static function onAnAndroidPhone(): self
    {
        return new self(WhereAPhoneKeyLives::StrongBox, android: true);
    }

    /** An iPhone with a Secure Enclave, attesting through App Attest. */
    public static function onAnIphone(): self
    {
        return new self(WhereAPhoneKeyLives::Enclave, android: false);
    }

    /** A phone that can keep no key, for this reason. */
    public static function keepingNone(WhyThisPhoneHoldsNoKey $why): self
    {
        return new self($why, android: true);
    }

    /** From now on, every prompt to sign ends this way. */
    public function declining(WhyTheKeySignedNothing $why): self
    {
        $this->declined = $why;

        return $this;
    }

    /** From now on, no key can be thrown away. */
    public function holdingOnToItsKeys(): self
    {
        $this->stuck = true;

        return $this;
    }

    /** How many times it was asked where a key could live. */
    public function timesAskedWhere(): int
    {
        return $this->looked;
    }

    public function whereItCouldLive(): WhereThisPhoneCanKeepAKey
    {
        $this->looked++;

        return $this->place instanceof WhereAPhoneKeyLives
            ? WhereThisPhoneCanKeepAKey::in($this->place)
            : WhereThisPhoneCanKeepAKey::nowhere($this->place);
    }

    public function madeFor(StackId $stack): APhoneKeyMade
    {
        if ($this->place instanceof WhyThisPhoneHoldsNoKey) {
            return APhoneKeyMade::not($this->place);
        }

        $pair = AKeyPairInSoftware::made();
        $this->held[$stack->stored()] = $pair;

        return APhoneKeyMade::made(
            $this->place,
            $pair->publicKey(),
            $this->android ? AnAttestation::fromAndroid($pair->publicKey()) : AnAttestation::fromApple($pair->publicKey()),
        );
    }

    public function signedFor(StackId $stack, ATextToSign $text): WhatSigningCameTo
    {
        $pair = $this->held[$stack->stored()] ?? null;

        if ($this->declined instanceof WhyTheKeySignedNothing) {
            return WhatSigningCameTo::not($this->declined);
        }

        return $pair instanceof AKeyPairInSoftware
            ? WhatSigningCameTo::signed($pair->signed($text->said()))
            : WhatSigningCameTo::not(WhyTheKeySignedNothing::Gone);
    }

    public function nameForANewKey(): APhoneKeysName
    {
        return APhoneKeysName::of($this->android ? 'Galaxy A51 · 7f3a' : 'iPhone 12 mini · 01bc');
    }

    public function forgetTheStack(StackId $stack): Forgotten
    {
        return $this->forgetFor($stack) === WhatForgettingAPhoneKeyFound::ThrownAway ? Forgotten::rows(1) : Forgotten::nothing();
    }

    public function keepsAnythingOf(StackId $stack): bool
    {
        return array_key_exists($stack->stored(), $this->held);
    }

    public function forgetFor(StackId $stack): WhatForgettingAPhoneKeyFound
    {
        if ($this->stuck) {
            return WhatForgettingAPhoneKeyFound::StillHeld;
        }

        $held = array_key_exists($stack->stored(), $this->held);
        unset($this->held[$stack->stored()]);

        return $held ? WhatForgettingAPhoneKeyFound::ThrownAway : WhatForgettingAPhoneKeyFound::NoneHeld;
    }
}
