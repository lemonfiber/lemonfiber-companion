<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * A credential of another service an install or update of a third-party plugin would hand it, asked for apart from the offer.
 *
 * Approved as itself, by the line the stack names it with: the yes to the
 * install approves none, and without its approval the stack refuses the act.
 */
final readonly class AGrantAsked
{
    private function __construct(private ACredentialGrant $grant, private string $approval) {}

    /** That grant, asked for by that line; a blank line is refused. */
    public static function of(ACredentialGrant $grant, string $approval): self
    {
        if (trim($approval) === '') {
            throw CredentialGrantSaysNothing::about('approval');
        }

        return new self($grant, $approval);
    }

    /** The grant it would make. */
    public function grant(): ACredentialGrant
    {
        return $this->grant;
    }

    /** The line its approval is given by, as the stack spells it. */
    public function approval(): string
    {
        return $this->approval;
    }
}
