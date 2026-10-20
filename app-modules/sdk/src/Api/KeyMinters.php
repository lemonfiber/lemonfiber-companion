<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Closure;
use Lemonfiber\Sdk\DeviceAttestation;
use Lemonfiber\Sdk\Exception\ApiVersionMismatch;
use Lemonfiber\Sdk\Exception\CertificateWasRefused;
use Lemonfiber\Sdk\Exception\ConfigurationProblem;
use Lemonfiber\Sdk\Exception\RequestFailed;
use Lemonfiber\Sdk\Exception\UnexpectedKind;
use Lemonfiber\Sdk\Exception\Unreachable;
use Lemonfiber\Sdk\Exception\UnreadableResponse;
use Lemonfiber\Sdk\Generated\RefusalCode;
use Lemonfiber\Sdk\KeyMint;
use Lemonfiber\Sdk\MintProof;
use Modules\Kernel\Api\AChallengeToSign;
use Modules\Kernel\Api\AKeyClaim;
use Modules\Kernel\Api\AKeyToMint;
use Modules\Kernel\Api\AnAttestation;
use Modules\Kernel\Api\APhoneKey;
use Modules\Kernel\Api\APhoneKeysName;
use Modules\Kernel\Api\APhoneKeyToRegister;
use Modules\Kernel\Api\AProofOfThisPhone;
use Modules\Kernel\Api\Credential;
use Modules\Kernel\Api\MintingKeysAsClaims;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatMintingCameTo;
use Modules\Sdk\Internal\AScopeAsWritten;
use Modules\Sdk\Internal\GatedClient;
use Modules\Sdk\Internal\WhatARefusalMeant;
use Override;

/**
 * The one place this application registers a phone's key on a stack and mints a key as a claim.
 *
 * Every mint is a claim: the answer is a link that collects the key, never its secret. A refusal
 * is said in the stack's words, and an answer that cannot be read is what stood in the way.
 */
final readonly class KeyMinters implements MintingKeysAsClaims
{
    public function __construct(private Clients $clients) {}

    #[Override]
    public function registeredOn(Stack $stack, Session $session, APhoneKeyToRegister $key, Credential $password): WhatMintingCameTo
    {
        return $this->asked($stack, fn(): APhoneKey => PhoneKeyRegistered::in(GatedClient::of($this->clients, $stack, $session)->keyring()->registerDevice(
            $key->name()->shown(),
            $key->publicKey(),
            $this->attestation($key->attestation()),
            $password->forTheExchange(),
        )));
    }

    #[Override]
    public function challengedOn(Stack $stack, Session $session, APhoneKeysName $phone, AKeyToMint $key): WhatMintingCameTo
    {
        return $this->asked($stack, function () use ($stack, $session, $phone, $key): AChallengeToSign {
            $mint = $this->mint($key);

            return ChallengeToSign::in(GatedClient::of($this->clients, $stack, $session)->keyring()->deviceChallenge($phone->shown()), $mint);
        });
    }

    #[Override]
    public function claimedOn(Stack $stack, Session $session, AKeyToMint $key, AProofOfThisPhone $proof): WhatMintingCameTo
    {
        return $this->asked($stack, fn(): AKeyClaim => KeyClaimed::in(GatedClient::of($this->clients, $stack, $session)->keyring()->mint(
            $this->mint($key),
            MintProof::device($proof->phone()->shown(), $proof->challenge()->challenge(), $proof->signature()),
        )));
    }

    /**
     * Ask the stack, and read its answer, that it knows no such phone key, its refusal or what stood in the way.
     *
     * @template TAnswer of object
     *
     * @param Closure(): TAnswer $ask
     *
     * @param-immediately-invoked-callable $ask
     *
     * @return WhatMintingCameTo<TAnswer>
     */
    private function asked(Stack $stack, Closure $ask): WhatMintingCameTo
    {
        try {
            return WhatMintingCameTo::answered($ask());
        } catch (CertificateWasRefused|RequestFailed $why) {
            return $this->refusal($why);
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse|UnexpectedKind|ConfigurationProblem|MintingIsUnreadable $why) {
            return WhatMintingCameTo::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    /**
     * A refusal: a phone key the stack does not know, or one in its words where it gave them.
     *
     * @return WhatMintingCameTo<never>
     */
    private function refusal(CertificateWasRefused|RequestFailed $why): WhatMintingCameTo
    {
        if ($why instanceof RequestFailed && $why->code() === RefusalCode::NotRegistered) {
            return WhatMintingCameTo::unregistered();
        }

        return WhatARefusalMeant::inItsWords(
            $why,
            refused: WhatMintingCameTo::refused(...),
            met: WhatMintingCameTo::met(...),
        );
    }

    /** The mint as the SDK sends it: always a claim. */
    private function mint(AKeyToMint $key): KeyMint
    {
        return KeyMint::of($key->name()->shown(), AScopeAsWritten::of($key->admits()), $key->for()->value, claim: true);
    }

    /** The attestation as the SDK sends it. */
    private function attestation(AnAttestation $attestation): DeviceAttestation
    {
        return $attestation->either(
            android: static fn(array $chain): DeviceAttestation => DeviceAttestation::android($chain),
            apple: static fn(string $object): DeviceAttestation => DeviceAttestation::ios($object),
        );
    }
}
