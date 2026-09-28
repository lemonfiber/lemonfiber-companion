<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * How far taking somebody out of the household got, across the two services a member exists on.
 *
 * Three answers, and only one of them is done. The media server is taken
 * first and the request service second, so the middle answer is somebody who
 * can neither watch nor ask and still has an account on the request service,
 * which the next removal takes. *Nothing* is what a reading nobody agreed to
 * says: nobody was taken out.
 */
enum HowFarTheRemovalReached: string
{
    /** Gone from the media server and the request service, which is what removal means. */
    case Everywhere = 'everywhere';

    /** Gone from the media server, with an account still held on the request service. */
    case MediaServerOnly = 'media-server-only';

    /** Nobody was taken out, because nothing was agreed to. */
    case Nothing = 'nothing';

    /**
     * Whether this is somebody fully out of the household.
     *
     * Only *everywhere*. An account left on the request service is something
     * there that should not be, and a screen that drew it as done would tell
     * the operator the person is gone when they are not.
     */
    public function isDone(): bool
    {
        return $this === self::Everywhere;
    }

    /** The catalogue key for this, as an operator reads it. */
    public function saidOnTheScreen(): string
    {
        return sprintf('stacks.removal.revoked.%s', $this->value);
    }
}
