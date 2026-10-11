<?php

declare(strict_types=1);

namespace Modules\Dx\Internal;

use function array_any;

use Lemonfiber\Sdk\Contract\Api;
use Modules\Kernel\Api\WhatToDoWithTheDownloads;
use Saloon\Http\PendingRequest;

/**
 * Which request pauses or resumes the downloads, and which path follows that work, for the stand-in.
 *
 * Read off {@see WhatToDoWithTheDownloads}' own cases, so a third way of asking
 * the clients is answered as work without anybody remembering to add it here.
 */
final readonly class TheDownloadsWork
{
    /** lemonfiber's word for what this request asks of the downloads, which names the work the stand-in starts for it, or empty. */
    public static function askedBy(PendingRequest $asked): string
    {
        foreach (WhatToDoWithTheDownloads::cases() as $downloads) {
            if ($asked->getRequest()->resolveEndpoint() === Api::action($downloads->asked())) {
                return $downloads->asked();
            }
        }

        return '';
    }

    /** Whether this path follows the work of pausing or resuming the downloads. */
    public static function isFollowedAt(string $endpoint): bool
    {
        return array_any(WhatToDoWithTheDownloads::cases(), fn(WhatToDoWithTheDownloads $downloads): bool => $endpoint === Api::job($downloads->asked()));
    }
}
