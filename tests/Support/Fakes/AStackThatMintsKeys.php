<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use function array_key_exists;

use Modules\Kernel\Api\AChallengeToSign;
use Modules\Kernel\Api\AKeyClaim;
use Modules\Kernel\Api\AKeyToMint;
use Modules\Kernel\Api\AMomentAsWritten;
use Modules\Kernel\Api\APhoneKey;
use Modules\Kernel\Api\APhoneKeysName;
use Modules\Kernel\Api\APhoneKeyToRegister;
use Modules\Kernel\Api\AProofOfThisPhone;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\ATextToSign;
use Modules\Kernel\Api\Credential;
use Modules\Kernel\Api\MintingKeysAsClaims;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatMintingCameTo;
use Modules\Kernel\Api\WhatTheRefusalNamed;
use Modules\Kernel\Api\WhereAPhoneKeyLives;

use function sprintf;

use Tests\Support\TheScopeAsWritten;

/**
 * A stack that registers phone keys and mints keys as claims, in memory.
 *
 * It holds every public half it registered and mints only over a signature that half verifies,
 * as the core does, so a suite proves a mint end to end with a real signature.
 */
final class AStackThatMintsKeys implements MintingKeysAsClaims
{
    /** The challenge it issues. */
    public const string CHALLENGE = 'a-challenge-for-one-mint';

    /** The link it answers a claim with. */
    public const string LINK = 'lemonfiber://key?claim=a-claim-collected-once';

    /** When that link lapses. */
    public const string LAPSES = '2026-10-13T21:10:00';

    /** When it registers a phone key. */
    public const string REGISTERED = '2026-10-13T21:00:00';

    /** What it says of a signature that does not verify. */
    public const string WRONG_SIGNATURE = 'The signature does not prove this mint.';

    /** @var array<string, string> the public halves registered, by name */
    private array $registered = [];

    /** @var list<string> */
    private array $claimed = [];

    private function __construct(private readonly ARefusalInItsWords|Obstacle|null $refusing) {}

    /** A stack that answers. */
    public static function answering(): self
    {
        return new self(null);
    }

    /** A stack that refuses everything, in these words. */
    public static function refusing(ARefusalInItsWords $why): self
    {
        return new self($why);
    }

    /** A stack that cannot be asked. */
    public static function met(Obstacle $why): self
    {
        return new self($why);
    }

    public function registeredOn(Stack $stack, Session $session, APhoneKeyToRegister $key, Credential $password): WhatMintingCameTo
    {
        $password->forTheExchange();

        if ($this->refusing !== null) {
            return $this->refused($this->refusing);
        }

        $this->registered[$key->name()->shown()] = $key->publicKey();

        return WhatMintingCameTo::answered(APhoneKey::standing($key->name(), WhereAPhoneKeyLives::TrustedEnvironment, AMomentAsWritten::byTheStack(self::REGISTERED), AMomentAsWritten::byTheStack('')));
    }

    public function challengedOn(Stack $stack, Session $session, APhoneKeysName $phone, AKeyToMint $key): WhatMintingCameTo
    {
        if ($this->refusing !== null) {
            return $this->refused($this->refusing);
        }

        if (! array_key_exists($phone->shown(), $this->registered)) {
            return WhatMintingCameTo::unregistered();
        }

        return WhatMintingCameTo::answered(AChallengeToSign::of(self::CHALLENGE, ATextToSign::saying($this->textFor($key))));
    }

    public function claimedOn(Stack $stack, Session $session, AKeyToMint $key, AProofOfThisPhone $proof): WhatMintingCameTo
    {
        if ($this->refusing !== null) {
            return $this->refused($this->refusing);
        }

        if (! array_key_exists($proof->phone()->shown(), $this->registered)) {
            return WhatMintingCameTo::unregistered();
        }

        $publicKey = $this->registered[$proof->phone()->shown()];

        if (! AKeyPairInSoftware::verifies($publicKey, $this->textFor($key), $proof->signature())) {
            return WhatMintingCameTo::refused(ARefusalInItsWords::said(self::WRONG_SIGNATURE, '', WhatTheRefusalNamed::nothing()));
        }

        $this->claimed[] = $key->name()->shown();

        return WhatMintingCameTo::answered(AKeyClaim::of($key->name(), self::LINK, AMomentAsWritten::byTheStack(self::LAPSES)));
    }

    /**
     * The keys it minted as claims, in order.
     *
     * @return list<string>
     */
    public function claimed(): array
    {
        return $this->claimed;
    }

    /** The stack revokes the phone key registered under this name. */
    public function revoke(string $name): void
    {
        unset($this->registered[$name]);
    }

    /** Whether it registered a phone key under this name. */
    public function hasRegistered(string $name): bool
    {
        return array_key_exists($name, $this->registered);
    }

    /** The text the core verifies a mint's signature over, spelled out here rather than borrowed from the SDK. */
    private function textFor(AKeyToMint $key): string
    {
        return sprintf("lemonfiber-mint/1\n%s\n%s\n%s\n%s\nclaim\n", self::CHALLENGE, $key->name()->shown(), TheScopeAsWritten::of($key->admits()), $key->for()->value);
    }

    /** @return WhatMintingCameTo<never> */
    private function refused(ARefusalInItsWords|Obstacle $why): WhatMintingCameTo
    {
        return $why instanceof Obstacle ? WhatMintingCameTo::met($why) : WhatMintingCameTo::refused($why);
    }
}
