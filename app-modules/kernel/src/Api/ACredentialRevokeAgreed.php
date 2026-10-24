<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Ending one grant, agreed to against the reading the operator was shown.
 *
 * The yes quotes the reading's name, so a grant whose reading has since moved
 * is refused by the stack rather than ended on the strength of an old one.
 */
final readonly class ACredentialRevokeAgreed
{
    private function __construct(private ACredentialGrant $grant, private string $agreement) {}

    /** Agreed against that reading of ending that grant; anything else is refused. */
    public static function after(ACredentialRevoke $reading, ACredentialGrant $grant): self
    {
        if (! $reading->readsARevokeOf($grant)) {
            throw ACredentialRevokeWasNotRead::before($grant);
        }

        return new self($grant, $reading->agreement());
    }

    /** The grant it ends. */
    public function grant(): ACredentialGrant
    {
        return $this->grant;
    }

    /** The name of the reading agreed to. */
    public function agreement(): string
    {
        return $this->agreement;
    }
}
