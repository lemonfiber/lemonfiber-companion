<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * A sign-in code as the stack issued it: the code, where it is approved, when it lapses, and the handle that finishes it.
 */
final readonly class ACodeToApprove
{
    private function __construct(
        public string $code,
        public AnAddressToHand $approveAt,
        public Instant $lapses,
        public AWaitingCode $waiting,
    ) {}

    /** The code the stack issued, refused where it said nothing to approve or nowhere to approve it. */
    public static function issued(string $code, string $approveAt, Instant $lapses, AWaitingCode $waiting): self
    {
        if (trim($code) === '') {
            throw TheDoorSaysNothing::about('code');
        }

        return new self($code, AnAddressToHand::at($approveAt, ''), $lapses, $waiting);
    }
}
