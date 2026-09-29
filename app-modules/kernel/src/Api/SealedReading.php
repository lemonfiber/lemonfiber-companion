<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * A reading as a store hands it back: sealed, with the shape it was written in and when it was read.
 *
 * The shape and the moment stay readable beside the payload because a store
 * has to be able to find what is too old to keep without opening anything, an
 * owner has to know which layout to read before it opens one, and a screen has
 * to say how old a kept reading is.
 */
final readonly class SealedReading
{
    private function __construct(
        private SealedPayload $payload,
        private Shape $shape,
        private Instant $readAt,
    ) {}

    public static function of(SealedPayload $payload, Shape $shape, Instant $readAt): self
    {
        return new self($payload, $shape, $readAt);
    }

    public function payload(): SealedPayload
    {
        return $this->payload;
    }

    public function shape(): Shape
    {
        return $this->shape;
    }

    public function readAt(): Instant
    {
        return $this->readAt;
    }
}
