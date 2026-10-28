<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Closure;
use Modules\Kernel\Api\AChallengeToSign;
use Modules\Kernel\Api\AKeyClaim;
use Modules\Kernel\Api\AKeyToMint;
use Modules\Kernel\Api\AnAttestation;
use Modules\Kernel\Api\APhoneKey;
use Modules\Kernel\Api\APhoneKeysName;
use Modules\Kernel\Api\APhoneKeyToRegister;
use Modules\Kernel\Api\AProofOfThisPhone;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\Credential;
use Modules\Kernel\Api\Encoding;
use Modules\Kernel\Api\HoldingAPhoneKey;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Inviting;
use Modules\Kernel\Api\KeepingThePhoneKeysName;
use Modules\Kernel\Api\MintingKeysAsClaims;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\WhatMintingCameTo;
use Modules\Kernel\Api\WhereAPhoneKeyLives;
use Modules\Kernel\Api\WhyTheKeySignedNothing;
use Modules\Kernel\Api\WhyThisPhoneHoldsNoKey;
use Modules\Operator\Internal\Presenters\HowACodeReads;
use Modules\Operator\Internal\Presenters\HowARefusalReads;
use Modules\Operator\Internal\ViewModels\AKeyClaimAsShown;
use Modules\Operator\Internal\ViewModels\WhatMintingShows;

/**
 * Making a key for an app from this phone, as a claim proved by the phone's own key.
 *
 * The first mint from this phone for a stack registers the phone's key, with the password given
 * once; every mint signs the stack's challenge behind face or fingerprint. A phone key the stack
 * no longer knows, or one a new face or fingerprint made unusable, is thrown away here, so the
 * next mint registers a new one.
 */
final readonly class MintsFromThisPhone
{
    public function __construct(
        private HoldingAPhoneKey $phone,
        private KeepingThePhoneKeysName $names,
        private MintingKeysAsClaims $minting,
        private Inviting $inviting,
        private Encoding $encoding,
    ) {}

    /** Why this phone may not make a key, as a catalogue key, or nothing where it may. */
    public function whyNot(): string
    {
        return $this->phone->whereItCouldLive()->either(
            in: static fn(): WhatTheSheetSaid => new WhatTheSheetSaid(''),
            nowhere: static fn(WhyThisPhoneHoldsNoKey $why): WhatTheSheetSaid => new WhatTheSheetSaid(self::cannot($why)),
        )->said;
    }

    /** The name this phone's key is registered under on the stack, or null where it has none it still holds. */
    public function thisPhonesKey(StackId $stack): ?APhoneKeysName
    {
        $named = $this->names->nameOn($stack)->either(
            named: fn(APhoneKeysName $name): APhoneKeysName|AsText => $this->phone->keepsAnythingOf($stack) ? $name : AsText::nothing(),
            none: static fn(): AsText => AsText::nothing(),
        );

        return $named instanceof APhoneKeysName ? $named : null;
    }

    /**
     * The names of the household's members a key may be made for.
     *
     * @param Closure(Obstacle): MembersNamed $met what stood in the way of reading them comes to
     */
    public function membersOn(Stack $stack, Session $session, Closure $met): MembersNamed
    {
        return $this->inviting->whoIsIn($stack, $session)->either(
            found: MembersNamed::joined(...),
            met: $met,
        );
    }

    /**
     * Make the key as a claim, registering this phone's key first where it has none on the stack.
     *
     * @param ?Credential                         $password the operator's password, where this phone's key is to be registered
     * @param Closure(Obstacle): WhatMintingShows $met      what stood in the way of asking the stack comes to
     */
    public function mint(Stack $stack, Session $session, AKeyToMint $key, ?Credential $password, Instant $now, Closure $met): WhatMintingShows
    {
        $asked = new AMintAsked($stack, $session, $key, $now, $met);
        $name = $this->thisPhonesKey($stack->id());

        if ($name instanceof APhoneKeysName) {
            return $this->proved($asked, $name);
        }

        return $password instanceof Credential
            ? $this->registered($asked, $password)
            : self::said('stacks.mint.password_needed');
    }

    /** Register a new key for this phone, and make the key with it. */
    private function registered(AMintAsked $asked, Credential $password): WhatMintingShows
    {
        $name = $this->phone->nameForANewKey();
        $stack = $asked->stack;

        return $this->phone->madeFor($stack->id())->either(
            made: fn(WhereAPhoneKeyLives $where, string $publicKey, AnAttestation $attestation): WhatMintingShows => $this->minting->registeredOn($stack, $asked->session, APhoneKeyToRegister::of($name, $publicKey, $attestation), $password)->either(
                answered: fn(APhoneKey $registered): WhatMintingShows => $this->kept($stack, $registered->name())
                    ? $this->proved($asked, $registered->name())
                    : $this->forgotten($stack, self::said('stacks.mint.failed')),
                unregistered: fn(): WhatMintingShows => $this->forgotten($stack, self::said('stacks.mint.failed')),
                refused: fn(ARefusalInItsWords $why): WhatMintingShows => $this->forgotten($stack, $this->refused($why)),
                met: fn(Obstacle $why): WhatMintingShows => $this->forgotten($stack, ($asked->met)($why)),
            ),
            not: static fn(WhyThisPhoneHoldsNoKey $why): WhatMintingShows => self::said(self::cannot($why)),
        );
    }

    /** Ask for a challenge, sign it behind face or fingerprint, and make the key as a claim. */
    private function proved(AMintAsked $asked, APhoneKeysName $phone): WhatMintingShows
    {
        return $this->minting->challengedOn($asked->stack, $asked->session, $phone, $asked->key)->either(
            answered: fn(AChallengeToSign $challenge): WhatMintingShows => $this->signed($asked, $phone, $challenge),
            unregistered: fn(): WhatMintingShows => $this->forgotten($asked->stack, self::said('stacks.mint.unregistered')),
            refused: $this->refused(...),
            met: $asked->met,
        );
    }

    /** Sign the challenge, and make the key as a claim over the signature. */
    private function signed(AMintAsked $asked, APhoneKeysName $phone, AChallengeToSign $challenge): WhatMintingShows
    {
        return $this->phone->signedFor($asked->stack->id(), $challenge->text())->either(
            signed: fn(string $signature): WhatMintingShows => $this->claimed($asked, $this->minting->claimedOn($asked->stack, $asked->session, $asked->key, AProofOfThisPhone::of($phone, $challenge, $signature))),
            not: fn(WhyTheKeySignedNothing $why): WhatMintingShows => match ($why) {
                WhyTheKeySignedNothing::Gone => $this->forgotten($asked->stack, self::said('stacks.mint.gone')),
                WhyTheKeySignedNothing::Cancelled => self::said('stacks.mint.cancelled'),
                WhyTheKeySignedNothing::Failed => self::said('stacks.mint.failed'),
            },
        );
    }

    /**
     * What the mint came to.
     *
     * @param WhatMintingCameTo<AKeyClaim> $came
     */
    private function claimed(AMintAsked $asked, WhatMintingCameTo $came): WhatMintingShows
    {
        return $came->either(
            answered: fn(AKeyClaim $claim): WhatMintingShows => new WhatMintingShows(claim: $this->shown($claim, $asked->now), refused: null, met: null, said: null),
            unregistered: fn(): WhatMintingShows => $this->forgotten($asked->stack, self::said('stacks.mint.unregistered')),
            refused: $this->refused(...),
            met: $asked->met,
        );
    }

    /** The claim as the screen hands it over. */
    private function shown(AKeyClaim $claim, Instant $now): AKeyClaimAsShown
    {
        return new AKeyClaimAsShown(
            name: $claim->name()->shown(),
            link: $claim->carried(),
            squares: new HowACodeReads()->squares($this->encoding->codeFor($claim)),
            lapsesAt: $claim->lapses()->read(read: static fn(Instant $at): Instant => $at, unreadable: static fn(): Instant => $now)->epochSeconds(),
        );
    }

    /** Whether the name this phone's key was registered under is kept. */
    private function kept(Stack $stack, APhoneKeysName $name): bool
    {
        return $this->names->keepTheName($stack->id(), $name)->either(
            kept: static fn(): WhetherItIsHeld => WhetherItIsHeld::itIs(),
            refused: static fn(): WhetherItIsHeld => WhetherItIsHeld::itIsNot(),
        )->held;
    }

    /** Throw this phone's key for the stack away, and let its name go, so the next mint registers a new one. */
    private function forgotten(Stack $stack, WhatMintingShows $shown): WhatMintingShows
    {
        $this->phone->forgetFor($stack->id());
        $this->names->letTheNameGo($stack->id());

        return $shown;
    }

    /** The sentence for why this phone holds no key. */
    private static function cannot(WhyThisPhoneHoldsNoKey $why): string
    {
        return match ($why) {
            WhyThisPhoneHoldsNoKey::NoHardware => 'stacks.mint.no_hardware',
            WhyThisPhoneHoldsNoKey::NoBiometric => 'stacks.mint.no_biometric',
            WhyThisPhoneHoldsNoKey::InSoftware => 'stacks.mint.in_software',
            WhyThisPhoneHoldsNoKey::Unattested => 'stacks.mint.unattested',
            WhyThisPhoneHoldsNoKey::Failed => 'stacks.mint.could_not_make',
        };
    }

    /** What happened on the phone, in its own sentence. */
    private static function said(string $what): WhatMintingShows
    {
        return new WhatMintingShows(claim: null, refused: null, met: null, said: $what);
    }

    /** A refusal, in the stack's words. */
    private function refused(ARefusalInItsWords $why): WhatMintingShows
    {
        return new WhatMintingShows(claim: null, refused: new HowARefusalReads()->inItsWords($why), met: null, said: null);
    }
}
