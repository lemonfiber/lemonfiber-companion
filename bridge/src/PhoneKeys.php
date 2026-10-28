<?php

declare(strict_types=1);

namespace Lemonfiber\Native;

use function in_array;
use function is_string;
use function sodium_base642bin;

use SodiumException;

use function trim;

/**
 * The key that proves this phone's operator to a stack, as this application is willing to use one.
 *
 * Made in the phone's hardware, usable only after face or fingerprint, and never handed over: what
 * comes back is its public half, the platform's attestation of where it lives, and a signature. Each
 * key is filed under an alias the caller names, one per stack.
 *
 * **Off a handset every call reports the least it can.** There is nowhere to keep a key, none was
 * made, nothing was signed. A stand-in claiming a key would let a test about proving a mint pass on
 * a machine with no hardware to prove anything.
 */
final readonly class PhoneKeys
{
    /** Where this phone could keep a key, or why it could not. */
    public function where(): PhoneKeyPlace
    {
        $said = WhatTheBridgeAnswered::toNothing(Call::PhoneKeyWhere);
        $lives = $said->word(WhatAnAnswerHolds::Lives);

        if (HowThePhoneKeyAnswered::in($said) === HowThePhoneKeyAnswered::Can && is_string($lives)) {
            return PhoneKeyPlace::lives($lives);
        }

        return PhoneKeyPlace::not(WhyNoPhoneKey::or(WhyNoPhoneKey::NoHardware, $said->word(WhatAnAnswerHolds::Because)));
    }

    /** Make the key under this alias, replacing any there, or say why not. */
    public function make(string $alias): PhoneKeyMade
    {
        $said = WhatTheBridgeAnswered::to(Call::PhoneKeyMake, ['alias' => $alias]);
        $lives = $said->word(WhatAnAnswerHolds::Lives);
        $publicKey = $this->bytes($said->word(WhatAnAnswerHolds::PublicKey));

        if (HowThePhoneKeyAnswered::in($said) !== HowThePhoneKeyAnswered::Made || ! is_string($lives) || $publicKey === '') {
            return PhoneKeyMade::not(WhyNoPhoneKey::or(WhyNoPhoneKey::Failed, $said->word(WhatAnAnswerHolds::Because)));
        }

        $chain = [];

        foreach ($said->listed(WhatAnAnswerHolds::Chain) ?? [] as $certificate) {
            $chain[] = $this->bytes($certificate);
        }

        if ($chain !== [] && ! in_array('', $chain, strict: true)) {
            return PhoneKeyMade::onAndroid($lives, $publicKey, $chain);
        }

        $attestation = $this->bytes($said->word(WhatAnAnswerHolds::Attestation));

        return $attestation === ''
            ? PhoneKeyMade::not(WhyNoPhoneKey::Unattested)
            : PhoneKeyMade::onIos($lives, $publicKey, $attestation);
    }

    /**
     * Sign this text with the key under this alias, once the operator shows their face or fingerprint.
     *
     * @param string $reason what the prompt says it is for, in the operator's language
     */
    public function sign(string $alias, string $text, string $reason): PhoneKeySigned
    {
        $said = WhatTheBridgeAnswered::to(Call::PhoneKeySign, ['alias' => $alias, 'text' => $text, 'reason' => $reason]);
        $signature = $this->bytes($said->word(WhatAnAnswerHolds::Signature));

        if (HowThePhoneKeyAnswered::in($said) === HowThePhoneKeyAnswered::Signed && $signature !== '') {
            return PhoneKeySigned::signed($signature);
        }

        return PhoneKeySigned::not(WhyNothingWasSigned::orFailed($said->word(WhatAnAnswerHolds::Because)));
    }

    /**
     * Throw the key under this alias away, whether or not it was there, and say what that came to.
     *
     * Off a handset nothing answers and nothing was held.
     */
    public function forget(string $alias): WhatForgettingAKeyFound
    {
        $said = WhatTheBridgeAnswered::to(Call::PhoneKeyForget, ['alias' => $alias]);

        return match (true) {
            $said->outcome() === null => WhatForgettingAKeyFound::NoneHeld,
            HowThePhoneKeyAnswered::in($said) === HowThePhoneKeyAnswered::Forgotten => WhatForgettingAKeyFound::Forgotten,
            HowThePhoneKeyAnswered::in($said) === HowThePhoneKeyAnswered::NoneHeld => WhatForgettingAKeyFound::NoneHeld,
            default => WhatForgettingAKeyFound::Failed,
        };
    }

    /**
     * Whether a key is held under this alias, asking nothing of the operator.
     *
     * Only a phone that says it holds none holds none: one that could not tell is read as
     * holding one. Off a handset nothing is held.
     */
    public function holds(string $alias): bool
    {
        $said = WhatTheBridgeAnswered::to(Call::PhoneKeyHeld, ['alias' => $alias]);

        return $said->outcome() !== null && HowThePhoneKeyAnswered::in($said) !== HowThePhoneKeyAnswered::NoneHeld;
    }

    /** What this phone is called, for naming its key on a stack; empty where it says nothing. */
    public function model(): string
    {
        $said = WhatTheBridgeAnswered::toNothing(Call::PhoneKeyModel);
        $model = $said->word(WhatAnAnswerHolds::Model);

        return HowThePhoneKeyAnswered::in($said) === HowThePhoneKeyAnswered::Named && is_string($model) ? trim($model) : '';
    }

    /** Base64 the device wrote, as bytes; empty where it wrote nothing that decodes. */
    private function bytes(mixed $written): string
    {
        if (! is_string($written)) {
            return '';
        }

        try {
            return sodium_base642bin($written, SODIUM_BASE64_VARIANT_ORIGINAL);
        } catch (SodiumException) {
            return '';
        }
    }
}
