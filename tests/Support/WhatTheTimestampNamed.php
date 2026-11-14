<?php

declare(strict_types=1);

namespace Tests\Support;

use Modules\Kernel\Api\AMomentAsWritten;
use Modules\Kernel\Api\Instant;

/** What reading a timestamp came to, carried out of `read()`, which must hand back an object. */
final readonly class WhatTheTimestampNamed
{
    public function __construct(public ?int $seconds) {}

    /** The seconds since the epoch a written moment names, or none where it names none. */
    public static function in(AMomentAsWritten $moment): self
    {
        return $moment->read(
            read: static fn(Instant $at): self => new self($at->epochSeconds()),
            unreadable: static fn(): self => new self(null),
        );
    }
}
