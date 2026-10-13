<?php

declare(strict_types=1);

namespace Tests\Support;

use Modules\Kernel\Api\WhatAKeyAdmits;

use function sprintf;

/** What a key admits, spelled the way the wire spells a scope, written out for the suites. */
final readonly class TheScopeAsWritten
{
    private function __construct(private string $said) {}

    /** `read`, `act` or `member:<account>`. */
    public static function of(WhatAKeyAdmits $admits): string
    {
        return $admits->either(
            reading: static fn(): self => new self('read'),
            acting: static fn(): self => new self('act'),
            member: static fn(string $member): self => new self(sprintf('member:%s', $member)),
        )->said;
    }
}
