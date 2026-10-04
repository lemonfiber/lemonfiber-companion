<?php

declare(strict_types=1);

namespace Tests\Support;

use Modules\Kernel\Api\HowARequestStands;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Noted;
use Modules\Kernel\Api\Requested;
use Modules\Kernel\Api\SealedPayload;
use Modules\Kernel\Api\Shape;
use Modules\Kernel\Api\Size;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\TurnedDown;
use Modules\Kernel\Api\Unsealed;
use Modules\Kernel\Api\Waiting;
use Modules\Kernel\Api\Wanted;
use Modules\Requests\Api\KeepingWhatWasAsked;
use Tests\Support\Fakes\ASealInMemory;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\ReadingsInMemory;

/** A seal, a store, and what decides between them, over the two stand-ins, and the readings of what a household asked for that a test keeps. */
final readonly class WhatIsKeptOfRequests
{
    public KeepingWhatWasAsked $keeping;

    public function __construct(public ASealInMemory $seal, public ReadingsInMemory $store, public FrozenClock $clock)
    {
        $this->keeping = new KeepingWhatWasAsked($seal, $store, $clock);
    }

    /** A phone that seals, its clock reading this. */
    public static function onAPhoneThatSealsAt(Instant $now): self
    {
        return new self(ASealInMemory::working(), ReadingsInMemory::empty(), FrozenClock::at($now));
    }

    /**
     * A household that asked for every kind of thing a reading can hold: one
     * request measured and waiting on a yes, one guessed at and being fetched,
     * one turned down with a reason and when, one with no size at all, and one
     * standing in words the stack did not give.
     */
    public static function aReadingWithEveryPart(): Requested
    {
        return Requested::of(
            Wanted::of(1, 'Mira', 'Dune', Size::measured(4_200_000_000), HowARequestStands::said(Waiting::ForApproval)),
            Wanted::of(2, 'Joost', 'Severance', Size::guessedAt(30_000_000_000), HowARequestStands::said(Waiting::Getting)),
            Wanted::turnedDown(3, 'Mira', 'Cats', Size::unknown(), TurnedDown::at('2026-09-30 21:04', 'We have it already')),
            Wanted::turnedDown(4, 'Joost', 'The Room', Size::measured(700_000_000), TurnedDown::because('Not in this house')),
            Wanted::of(5, 'Joost', 'Twin Peaks', Size::unknown(), HowARequestStands::unnamed()),
        );
    }

    /** A household that has asked for nothing. */
    public static function aReadingOfNothing(): Requested
    {
        return Requested::none();
    }

    /** A value kept for a stack as though a reading had been, sealed by this phone. */
    public function holdsSealed(string $written, StackId $for, Instant $readAt): self
    {
        $this->seal->seal(Unsealed::of($written))->either(
            sealed: fn(SealedPayload $payload): Noted => $this->store->keep($this->seal->stack($for), $payload, Shape::One, $readAt),
            refused: static fn(): Noted => Noted::notKept(),
        );

        return $this;
    }
}
