<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/** Why the phone came away with no endpoint to register. */
enum WhyNoEndpoint: string
{
    /** This phone cannot be woken through a distributor. */
    case NotHere = 'not_here';

    /** The keystore made no key pair. */
    case NoKey = 'no_key';

    /** The distributor refused to register. */
    case Refused = 'refused';

    /** The distributor did not answer in time. */
    case NoAnswer = 'no_answer';

    /** The distributor holds no registration for the stack any more. */
    case Dropped = 'dropped';

    /** The reason the bridge's word `$because` names, or `$otherwise` where it names none this app knows. */
    public static function said(string $because, self $otherwise): self
    {
        return self::tryFrom($because) ?? $otherwise;
    }

    /** The key for what is said on the screen. */
    public function saidOnTheScreen(): string
    {
        return sprintf('waking.no_endpoint.%s', $this->value);
    }
}
