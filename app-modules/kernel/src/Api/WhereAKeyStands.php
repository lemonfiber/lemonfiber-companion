<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * Where an integration key stands, each in its own words.
 *
 * A key waiting to be collected admits nothing yet, a revoked one never again,
 * and an orphaned one belongs to somebody no longer in the household: three
 * keys that do not work, for three different reasons.
 */
enum WhereAKeyStands: string
{
    /** Admitted within its scope. */
    case Active = 'active';

    /** Minted as a claim and not collected yet. */
    case Waiting = 'waiting';

    /** Refused, and kept in the listing with when it was revoked. */
    case Revoked = 'revoked';

    /** A member's key whose account has left the household. */
    case Orphaned = 'orphaned';

    /** A member's key whose account the media server could not be asked about just now. */
    case Unconfirmed = 'unconfirmed';

    /** The catalogue key for this, as an operator reads it. */
    public function saidOnTheScreen(): string
    {
        return sprintf('stacks.keys.state.%s', $this->value);
    }

    /** Whether a key standing here can still be revoked. */
    public function mayBeRevoked(): bool
    {
        return $this !== self::Revoked;
    }
}
