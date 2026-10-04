<?php

declare(strict_types=1);

namespace Tests\Support;

use Modules\Health\Api\KeepingTheLastReading;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Noted;
use Modules\Kernel\Api\SealedPayload;
use Modules\Kernel\Api\Shape;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Unsealed;
use Tests\Support\Fakes\ASealInMemory;
use Tests\Support\Fakes\ReadingsInMemory;

/** A seal, a store, and what decides between them, over the two stand-ins. */
final readonly class WhatIsKeptOfHealth
{
    public KeepingTheLastReading $keeping;

    public function __construct(public ASealInMemory $seal, public ReadingsInMemory $store)
    {
        $this->keeping = new KeepingTheLastReading($seal, $store);
    }

    public static function onAPhoneThatSeals(): self
    {
        return new self(ASealInMemory::working(), ReadingsInMemory::empty());
    }

    /** A value kept for a stack as though a summary had been, sealed by this phone. */
    public function holdsSealed(string $written, StackId $for, Instant $heardAt): self
    {
        $this->seal->seal(Unsealed::of($written))->either(
            sealed: fn(SealedPayload $payload): Noted => $this->store->keep(
                $this->seal->stack($for),
                $payload,
                Shape::One,
                $heardAt,
            ),
            refused: static fn(): Noted => Noted::notKept(),
        );

        return $this;
    }
}
