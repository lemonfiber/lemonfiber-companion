<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * What ending one grant would come to, as the stack read it, or what it came to once it was agreed to.
 *
 * Read first: the reading names the grant it ends and the plugin's services
 * that read the credential, which restart once it is withdrawn, and goes by
 * the name a yes quotes. Ended, it says the same, and why the services did
 * not restart where they did not. The plugin stays installed either way.
 */
final readonly class ACredentialRevoke
{
    private function __construct(
        private string $agreement,
        private bool $ended,
        private ACredentialGrant $grant,
        private Services $restarts,
        private string $unrestarted,
    ) {}

    /** The reading: nothing ended yet, under the name a yes quotes. */
    public static function read(string $agreement, ACredentialGrant $grant, Services $restarts): self
    {
        return new self(trim($agreement), ended: false, grant: $grant, restarts: $restarts, unrestarted: '');
    }

    /** The grant ended, with why its services did not restart, blank where they did. */
    public static function ended(string $agreement, ACredentialGrant $grant, Services $restarts, string $unrestarted): self
    {
        return new self(trim($agreement), ended: true, grant: $grant, restarts: $restarts, unrestarted: trim($unrestarted));
    }

    /** The name the reading goes by, which a yes quotes. */
    public function agreement(): string
    {
        return $this->agreement;
    }

    /** Whether the grant was ended, rather than only read. */
    public function wasEnded(): bool
    {
        return $this->ended;
    }

    /** The grant it ends. */
    public function grant(): ACredentialGrant
    {
        return $this->grant;
    }

    /** The plugin's services that read the credential, restarted once it is withdrawn. */
    public function restarts(): Services
    {
        return $this->restarts;
    }

    /** Why the services did not restart, blank where they did or nothing was ended. */
    public function unrestarted(): string
    {
        return $this->unrestarted;
    }

    /** Whether this is a reading of ending that grant, which a yes may quote. */
    public function readsARevokeOf(ACredentialGrant $grant): bool
    {
        return ! $this->ended && $this->agreement !== '' && $this->grant->isTheSameAs($grant);
    }
}
