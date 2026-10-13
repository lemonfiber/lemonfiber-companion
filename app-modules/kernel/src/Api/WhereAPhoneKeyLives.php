<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * Where a phone key lives, as its attestation said when the stack registered it.
 *
 * The wire's three words, held to the contract by `EveryWireValueIsACaseTest`. Software is not
 * among them: the stack refuses a key that lives there.
 */
enum WhereAPhoneKeyLives: string
{
    /** A separate secure chip on an Android phone, as the hardware attested. */
    case StrongBox = 'strongbox';

    /** The Secure Enclave of an iPhone: App Attest vouched that the genuine app placed it there. */
    case Enclave = 'enclave';

    /** The processor's trusted environment on an Android phone, as the hardware attested. */
    case TrustedEnvironment = 'tee';

    /** The catalogue key for this, as an operator reads it. */
    public function saidOnTheScreen(): string
    {
        return sprintf('stacks.phone_keys.lives.%s', $this->value);
    }
}
