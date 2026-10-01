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
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\HealthReadingsInMemory;
use Tests\Support\Fakes\ReadingsKeptForInMemory;

/** A seal, a store, and what decides between them, over the two stand-ins. */
final readonly class WhatIsKeptOfHealth
{
    public KeepingTheLastReading $keeping;

    public ReadingsKeptForInMemory $settings;

    public function __construct(public ASealInMemory $seal, public HealthReadingsInMemory $store, ?Instant $now = null)
    {
        $this->settings = ReadingsKeptForInMemory::standard();
        $this->keeping = new KeepingTheLastReading($seal, $store, $this->settings, FrozenClock::at($now ?? Instant::atEpochSeconds(0)));
    }

    /** The same, with the clock reading this. */
    public static function onAPhoneThatSealsAt(Instant $now): self
    {
        return new self(ASealInMemory::working(), HealthReadingsInMemory::empty(), $now);
    }

    public static function onAPhoneThatSeals(): self
    {
        return new self(ASealInMemory::working(), HealthReadingsInMemory::empty());
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
