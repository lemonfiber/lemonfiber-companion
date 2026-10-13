<?php

declare(strict_types=1);

namespace Modules\Device\Api;

use Lemonfiber\Native\PhoneKeys;
use Lemonfiber\Native\WhatForgettingAKeyFound;
use Lemonfiber\Native\WhyNoPhoneKey;
use Lemonfiber\Native\WhyNothingWasSigned;
use Modules\Device\Internal\Words;
use Modules\Kernel\Api\AnAttestation;
use Modules\Kernel\Api\APhoneKeyMade;
use Modules\Kernel\Api\APhoneKeysName;
use Modules\Kernel\Api\ATextToSign;
use Modules\Kernel\Api\Entropy;
use Modules\Kernel\Api\Forgotten;
use Modules\Kernel\Api\HoldingAPhoneKey;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\WhatForgettingAPhoneKeyFound;
use Modules\Kernel\Api\WhatSigningCameTo;
use Modules\Kernel\Api\WhereAPhoneKeyLives;
use Modules\Kernel\Api\WhereThisPhoneCanKeepAKey;
use Modules\Kernel\Api\WhyTheKeySignedNothing;
use Modules\Kernel\Api\WhyThisPhoneHoldsNoKey;

use function sprintf;

/**
 * The key this phone holds for each stack, kept in its hardware through lemonfiber's native expansion.
 *
 * Each stack's key is filed under an alias built from the stack's id, so removing one stack's key
 * leaves every other stack's alone. A place the bridge names that is not one a stack accepts is
 * read as a failure: the stack would refuse the key anyway.
 */
final readonly class PlatformPhoneKeys implements HoldingAPhoneKey
{
    private const string ALIAS = 'lemonfiber.phone-key.%s';

    /** What a key's name is made of: what the phone is called, then four hexadecimal characters that tell two apart. */
    private const string NAME = '%s · %.4s';

    /** A key's name where the phone says nothing of itself: the four characters alone. */
    private const string APART = '%.4s';

    public function __construct(private PhoneKeys $keys, private Words $words, private Entropy $entropy) {}

    public function whereItCouldLive(): WhereThisPhoneCanKeepAKey
    {
        return $this->keys->where()->either(
            lives: $this->place(...),
            not: static fn(WhyNoPhoneKey $why): WhereThisPhoneCanKeepAKey
                => WhereThisPhoneCanKeepAKey::nowhere(self::meaning($why)),
        );
    }

    public function madeFor(StackId $stack): APhoneKeyMade
    {
        $alias = $this->alias($stack);

        return $this->keys->make($alias)->either(
            android: fn(string $lives, string $publicKey, array $chain): APhoneKeyMade
                => $this->made($alias, $lives, $publicKey, AnAttestation::fromAndroid(...$chain)),
            ios: fn(string $lives, string $publicKey, string $object): APhoneKeyMade
                => $this->made($alias, $lives, $publicKey, AnAttestation::fromApple($object)),
            not: static fn(WhyNoPhoneKey $why): APhoneKeyMade => APhoneKeyMade::not(self::meaning($why)),
        );
    }

    public function signedFor(StackId $stack, ATextToSign $text): WhatSigningCameTo
    {
        $signed = $this->keys->sign($this->alias($stack), $text->said(), $this->words->for('device.phone_key_reason'));

        return $signed->either(
            signed: WhatSigningCameTo::signed(...),
            not: static fn(WhyNothingWasSigned $why): WhatSigningCameTo => WhatSigningCameTo::not(match ($why) {
                WhyNothingWasSigned::Cancelled => WhyTheKeySignedNothing::Cancelled,
                WhyNothingWasSigned::Gone => WhyTheKeySignedNothing::Gone,
                WhyNothingWasSigned::Failed => WhyTheKeySignedNothing::Failed,
            }),
        );
    }

    public function nameForANewKey(): APhoneKeysName
    {
        $drawn = $this->entropy->nonce()->shown();
        $model = $this->keys->model();

        return APhoneKeysName::of($model === '' ? sprintf(self::APART, $drawn) : sprintf(self::NAME, $model, $drawn));
    }

    public function forgetTheStack(StackId $stack): Forgotten
    {
        return $this->forgetFor($stack) === WhatForgettingAPhoneKeyFound::ThrownAway ? Forgotten::rows(1) : Forgotten::nothing();
    }

    public function keepsAnythingOf(StackId $stack): bool
    {
        return $this->keys->holds($this->alias($stack));
    }

    public function forgetFor(StackId $stack): WhatForgettingAPhoneKeyFound
    {
        return match ($this->keys->forget($this->alias($stack))) {
            WhatForgettingAKeyFound::Forgotten => WhatForgettingAPhoneKeyFound::ThrownAway,
            WhatForgettingAKeyFound::NoneHeld => WhatForgettingAPhoneKeyFound::NoneHeld,
            WhatForgettingAKeyFound::Failed => WhatForgettingAPhoneKeyFound::StillHeld,
        };
    }

    /** Where the device said a key could live, or a failure where it named a place no stack accepts. */
    private function place(string $lives): WhereThisPhoneCanKeepAKey
    {
        $where = WhereAPhoneKeyLives::tryFrom($lives);

        return $where === null
            ? WhereThisPhoneCanKeepAKey::nowhere(WhyThisPhoneHoldsNoKey::Failed)
            : WhereThisPhoneCanKeepAKey::in($where);
    }

    /** The alias this stack's key is filed under on the phone. */
    private function alias(StackId $stack): string
    {
        return sprintf(self::ALIAS, $stack->stored());
    }

    /**
     * A key the phone made, unless it lives somewhere no stack accepts, when it is thrown away.
     *
     * @param string $lives the device's word for where the key lives
     */
    private function made(string $alias, string $lives, string $publicKey, AnAttestation $attestation): APhoneKeyMade
    {
        $where = WhereAPhoneKeyLives::tryFrom($lives);

        if ($where === null) {
            $this->keys->forget($alias);

            return APhoneKeyMade::not(WhyThisPhoneHoldsNoKey::Failed);
        }

        return APhoneKeyMade::made($where, $publicKey, $attestation);
    }

    /** What one of the bridge's words means in the terms the app reasons in. */
    private static function meaning(WhyNoPhoneKey $why): WhyThisPhoneHoldsNoKey
    {
        return match ($why) {
            WhyNoPhoneKey::NoHardware => WhyThisPhoneHoldsNoKey::NoHardware,
            WhyNoPhoneKey::NoBiometric => WhyThisPhoneHoldsNoKey::NoBiometric,
            WhyNoPhoneKey::InSoftware => WhyThisPhoneHoldsNoKey::InSoftware,
            WhyNoPhoneKey::Unattested => WhyThisPhoneHoldsNoKey::Unattested,
            WhyNoPhoneKey::Failed => WhyThisPhoneHoldsNoKey::Failed,
        };
    }
}
