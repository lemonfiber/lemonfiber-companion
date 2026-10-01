<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Illuminate\View\View;
use Modules\Connection\Api\LockingAfter;
use Modules\Health\Api\KeepingTheLastReading;
use Modules\Kernel\Api\DaysAsked;
use Modules\Kernel\Api\HowLongReadingsAreKept;
use Modules\Kernel\Api\KeptFor;
use Modules\Kernel\Api\LockAfter;
use Modules\Kernel\Api\SecureStorage;
use Modules\Operator\Internal\NotACountOfDays;
use Modules\Operator\Internal\Presenters\HowThisPhoneIsSetReads;
use Modules\Operator\Internal\ViewModels\ASettingAsShown;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

use function view;

/**
 * The phone's own settings: the lock, readings, the order of the stacks and
 * what the phone keeps.
 *
 * Each value is read once for the frame and held, and a choice is kept as it
 * is made, so what is drawn is always what is in force.
 */
#[Lazy]
final class HowThisPhoneIsSet extends NativeComponent
{
    /** How long the app may be away before the lock asks again, once read. */
    public ?LockAfter $lockAfter = null;

    /** Whether the phone has secure storage, once read. */
    public ?bool $hasSecureStorage = null;

    /** How long readings are kept, once read. */
    public ?HowLongReadingsAreKept $readingsKept = null;

    /** Whether the operator is typing a count of days to keep readings for. */
    public bool $typingDays = false;

    /** The count of days as typed. */
    public string $days = '';

    /** Whether the count typed was not one readings can be kept for. */
    public bool $daysRefused = false;

    public function __construct(
        private readonly LockingAfter $locking,
        private readonly SecureStorage $storage,
        private readonly KeepingTheLastReading $readings,
    ) {}

    /** Whether the phone keeps nothing between launches, having nowhere safe to. */
    public function keepsNothing(): bool
    {
        $this->hasSecureStorage ??= $this->storage->isAvailable();

        return ! $this->hasSecureStorage;
    }

    /** How long the app may be away, and every choice. */
    public function lockAfter(): ASettingAsShown
    {
        $this->lockAfter ??= $this->locking->current();

        return new HowThisPhoneIsSetReads()->lockAfter($this->lockAfter);
    }

    /** The operator chose how long the app may be away. */
    public function lockAfterIs(string $word): void
    {
        foreach (LockAfter::cases() as $offered) {
            if ($offered->name === $word) {
                $this->lockAfter = $this->locking->choose($offered);
            }
        }
    }

    /** How long readings are kept, and every choice. */
    public function keepReadings(): ASettingAsShown
    {
        return new HowThisPhoneIsSetReads()->keepReadings($this->readingsInForce(), $this->typingDays);
    }

    /** The operator tapped a choice of how long readings are kept. */
    public function keepReadingsFor(string $word): void
    {
        if ($word === NotACountOfDays::Other->value) {
            $this->typingDays = true;

            return;
        }

        if ($word === NotACountOfDays::UntilRemoved->value) {
            $this->keptFor(HowLongReadingsAreKept::untilRemoved());

            return;
        }

        foreach (KeptFor::cases() as $offered) {
            if ($offered->name === $word) {
                $this->keptFor(HowLongReadingsAreKept::for($offered));
            }
        }
    }

    /**
     * The fewest and the most days readings can be kept for, as the field's
     * supporting line says them.
     *
     * @return array{fewest: int, most: int}
     */
    public function daysAllowed(): array
    {
        return ['fewest' => HowLongReadingsAreKept::FEWEST_DAYS, 'most' => HowLongReadingsAreKept::MOST_DAYS];
    }

    /** The operator saved the count of days typed, which is kept only where it is one readings can be kept for. */
    public function saveDays(): void
    {
        DaysAsked::typed($this->days)->either(
            allowed: fn(HowLongReadingsAreKept $kept): HowLongReadingsAreKept => $this->keptFor($kept),
            refused: fn(): HowLongReadingsAreKept => $this->refuseTheDays(),
        );
    }

    public function render(): View
    {
        return view('operator::how-this-phone-is-set');
    }

    /** Keep the operator's choice, which lets go of every reading older than it now. */
    private function keptFor(HowLongReadingsAreKept $kept): HowLongReadingsAreKept
    {
        $this->readingsKept = $this->readings->keepFor($kept);
        $this->typingDays = false;
        $this->daysRefused = false;
        $this->days = '';

        return $this->readingsKept;
    }

    /** Say the count typed was refused, and keep the field open; what is kept stays as it was. */
    private function refuseTheDays(): HowLongReadingsAreKept
    {
        $this->daysRefused = true;

        return $this->readingsInForce();
    }

    /** How long readings are kept, read once for the frame. */
    private function readingsInForce(): HowLongReadingsAreKept
    {
        return $this->readingsKept ??= $this->readings->keptFor();
    }
}
