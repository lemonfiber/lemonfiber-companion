<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use InvalidArgumentException;

use function is_string;

use Lemonfiber\Native\Reorderable;
use Modules\Connection\Api\ClearingWhatThePhoneKeeps;
use Modules\Connection\Api\LettingGoOfOldReadings;
use Modules\Connection\Api\LockingAfter;
use Modules\Design\Api\TakesTheThemeItOpensOver;
use Modules\Kernel\Api\DaysAsked;
use Modules\Kernel\Api\HowLongReadingsAreKept;
use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\KeptFor;
use Modules\Kernel\Api\LockAfter;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Stacks;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\Operator\Internal\HasAWayBack;
use Modules\Operator\Internal\NotACountOfDays;
use Modules\Operator\Internal\Presenters\HowThisPhoneIsSetReads;
use Modules\Operator\Internal\ViewModels\ASettingAsShown;
use Modules\Wayfinding\Api\AScreenWithoutAStack;
use Modules\Wayfinding\Api\Screens\DrawsItsTemplate;
use Modules\Wayfinding\Api\WhoTheSettingsSpeakTo;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

/**
 * The phone's own settings: the lock, readings, the order of the stacks and
 * what the phone keeps.
 *
 * Each value is read once for the frame and held, and a choice is kept as it
 * is made, so what is drawn is always what is in force.
 */
#[Lazy]
#[ItsContent(WhatItShowsDoes::ChangesOnlyWhenAsked)]
final class HowThisPhoneIsSet extends NativeComponent implements TakesTheThemeItOpensOver
{
    use HasAWayBack;
    use DrawsItsTemplate;

    public const string TEMPLATE = 'operator::how-this-phone-is-set';
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

    /** Whose words this screen speaks in, which the screen it was opened from said. */
    public WhoTheSettingsSpeakTo $speaksTo = WhoTheSettingsSpeakTo::Anyone;

    /** Whether the operator is being asked whether to clear saved data. */
    public bool $confirmingTheClear = false;

    /**
     * The stacks as Stack order last drew them, read once rather than on every
     * draw: reading the pairings is a trip to the platform's secure store.
     *
     * @var list<array{key: string, name: string}>|null
     */
    public ?array $stacksShown = null;

    public function __construct(
        private readonly LockingAfter $locking,
        private readonly SecureStorage $storage,
        private readonly LettingGoOfOldReadings $readings,
        private readonly ClearingWhatThePhoneKeeps $clearing,
        private readonly Stacks $stacks,
    ) {}

    /** Speak in the words the screen it was opened from asked for: a member's Profile asks for household words. */
    public function mount(): void
    {
        $said = $this->data(AScreenWithoutAStack::SETTINGS_SPEAK_TO);

        $this->speaksTo = WhoTheSettingsSpeakTo::tryFrom(is_string($said) ? $said : '') ?? WhoTheSettingsSpeakTo::Anyone;
    }

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

    /** The operator asked to clear saved data; they are asked whether they mean it, on this screen. */
    public function askToClear(): void
    {
        $this->confirmingTheClear = true;
    }

    /** The operator kept their saved data. */
    public function keepSavedData(): void
    {
        $this->confirmingTheClear = false;
    }

    /**
     * The operator cleared saved data.
     *
     * The settings drawn were among it, so each is read again for the next frame.
     */
    public function clearSavedData(): void
    {
        $this->clearing->clear();
        $this->confirmingTheClear = false;
        $this->lockAfter = null;
        $this->readingsKept = null;
    }

    /**
     * The stacks in the operator's order, as the list draws them: each one's
     * key and its name.
     *
     * @return list<array{key: string, name: string}>
     */
    public function stacksInOrder(): array
    {
        if ($this->stacksShown !== null) {
            return $this->stacksShown;
        }

        $this->stacksShown = [];

        foreach ($this->stacks->configured() as $stack) {
            $this->stacksShown[] = ['key' => $stack->id()->stored(), 'name' => $stack->name()->shown()];
        }

        return $this->stacksShown;
    }

    /**
     * The operator put the stacks in a new order; every list follows it.
     *
     * An order that could not be kept leaves the one before in force, and the
     * list draws that one again.
     */
    public function putStacksInOrder(string $sent): void
    {
        $order = [];

        foreach (Reorderable::keysIn($sent) as $key) {
            $order = [...$order, ...$this->stackNamed($key)];
        }

        $this->stacks->putInOrder(...$order);
        $this->stacksShown = null;
    }

    /** Keep the operator's choice, which lets go of every reading older than it now. */
    /**
     * The stack a key names, or none where it is not one.
     *
     * @return list<StackId>
     */
    private function stackNamed(string $key): array
    {
        try {
            return [StackId::rememberedAs($key)];
        } catch (InvalidArgumentException) {
            return [];
        }
    }

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
