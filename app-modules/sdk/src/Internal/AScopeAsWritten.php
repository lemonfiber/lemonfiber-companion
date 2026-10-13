<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use Modules\Kernel\Api\HowAKeyIsScoped;
use Modules\Kernel\Api\WhatAKeyAdmits;

use function sprintf;

/** What a key admits, as the wire spells a scope: `read`, `act` or `member:<account>`. */
final readonly class AScopeAsWritten
{
    private function __construct(private string $said) {}

    /** The scope a mint asks for. */
    public static function of(WhatAKeyAdmits $admits): string
    {
        return $admits->either(
            reading: static fn(): self => new self(HowAKeyIsScoped::Reading->value),
            acting: static fn(): self => new self(HowAKeyIsScoped::Acting->value),
            member: static fn(string $member): self => new self(sprintf('%s:%s', HowAKeyIsScoped::AMembersOwn->value, $member)),
        )->said;
    }
}
