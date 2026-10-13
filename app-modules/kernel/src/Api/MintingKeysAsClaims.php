<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Minting a key for an app on a stack, proved by this phone's key, and answered with a link that
 * collects it rather than with its secret.
 *
 * A phone's key is registered once per stack, with the operator's password; every mint after that
 * is proved by the phone's signature over a challenge the stack issued for it.
 */
interface MintingKeysAsClaims
{
    /**
     * Register this phone's key on the stack, with the operator's password given once.
     *
     * @return WhatMintingCameTo<APhoneKey>
     */
    public function registeredOn(Stack $stack, Session $session, APhoneKeyToRegister $key, Credential $password): WhatMintingCameTo;

    /**
     * A challenge for minting this key, and the text this phone's key signs to prove it.
     *
     * @return WhatMintingCameTo<AChallengeToSign>
     */
    public function challengedOn(Stack $stack, Session $session, APhoneKeysName $phone, AKeyToMint $key): WhatMintingCameTo;

    /**
     * Mint this key as a claim, proved by this phone's signature.
     *
     * @return WhatMintingCameTo<AKeyClaim>
     */
    public function claimedOn(Stack $stack, Session $session, AKeyToMint $key, AProofOfThisPhone $proof): WhatMintingCameTo;
}
