<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What the operator lets reach the phone from one stack: the kinds switched off, and the lowest severity of problem told.
 *
 * Kept on the phone and never sent to the stack, which still raises every
 * alert. A kind nobody has chosen for is on, and until a floor is set every
 * problem is told, which is the same as a floor at the slightest severity. A
 * kind switched off stays switched off when the stack stops raising it, so it
 * comes back as the operator left it.
 */
final readonly class WhatMayReachThePhone
{
    private function __construct(private KindsOfNotification $off, private Severity $floor) {}

    /** Everything the stack raises, as it is until the operator chooses. */
    public static function everything(): self
    {
        return new self(KindsOfNotification::these(), Severity::Advisory);
    }

    /** Problems from this severity up, and every kind but these. */
    public static function from(Severity $floor, KindOfNotification ...$off): self
    {
        return new self(KindsOfNotification::these(...$off), $floor);
    }

    /** Whether an alert of this sort may be notified. */
    public function lets(AnAlertsSort $sort): bool
    {
        return $this->isOn($sort->kind())
            && ($sort->kind() !== KindOfNotification::Problem || ! $this->floor->isWorseThan($sort->severity()));
    }

    /** Whether this kind is switched on. */
    public function isOn(KindOfNotification $kind): bool
    {
        return ! $this->off->include($kind);
    }

    /** The lowest severity of problem told. */
    public function floor(): Severity
    {
        return $this->floor;
    }

    /** Every kind switched off, in the order the kinds are declared. */
    public function switchedOff(): KindsOfNotification
    {
        return $this->off;
    }

    /** The same, with this kind switched off. */
    public function withOff(KindOfNotification $kind): self
    {
        return new self($this->off->with($kind), $this->floor);
    }

    /** The same, with this kind switched on. */
    public function withOn(KindOfNotification $kind): self
    {
        return new self($this->off->without($kind), $this->floor);
    }

    /** The same, telling problems from this severity up. */
    public function withFloor(Severity $floor): self
    {
        return new self($this->off, $floor);
    }
}
