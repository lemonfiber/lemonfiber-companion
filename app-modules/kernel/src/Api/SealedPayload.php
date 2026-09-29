<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * A value sealed under the phone's data key, as a store keeps it.
 *
 * What {@see Sealed::seal()} answers with and what a store port takes, so that
 * a store never holds anything it could read. It carries no plaintext, which is
 * why it has none of {@see Unsealed}'s redaction: printed, it is noise to
 * anybody without the key.
 *
 * The one accessor is named for where the value goes.
 */
final readonly class SealedPayload
{
    private function __construct(private string $payload) {}

    /**
     * The one place a string becomes a sealed payload.
     *
     * Taken as given, including a string that is not a payload at all: whether
     * it opens is {@see Sealed::open()}'s answer, and a store reading back a
     * damaged row is the case that answer exists for.
     */
    public static function of(string $payload): self
    {
        return new self($payload);
    }

    /** What a store writes. */
    public function forTheStore(): string
    {
        return $this->payload;
    }
}
