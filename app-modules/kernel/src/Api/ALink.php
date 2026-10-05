<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * One of the stack's links: the service it runs from, and what it reaches.
 *
 * The service is what asked, so a capability nothing fills is drawn with who
 * is left asking for it, and a link kept to a named service with whose link
 * it is.
 */
final readonly class ALink
{
    private function __construct(
        private ServiceId $by,
        private HowItReaches $reaches,
    ) {}

    /** The link from one service, and what it reaches. */
    public static function from(ServiceId $by, HowItReaches $reaches): self
    {
        return new self($by, $reaches);
    }

    /** The service the link runs from: what asked. */
    public function by(): ServiceId
    {
        return $this->by;
    }

    /** What the link reaches, and how. */
    public function reaches(): HowItReaches
    {
        return $this->reaches;
    }
}
