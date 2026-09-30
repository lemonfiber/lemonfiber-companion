<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Illuminate\View\View;
use Modules\Connection\Api\LockingAfter;
use Modules\Kernel\Api\LockAfter;
use Modules\Kernel\Api\SecureStorage;
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

    public function __construct(private readonly LockingAfter $locking, private readonly SecureStorage $storage) {}

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

    public function render(): View
    {
        return view('operator::how-this-phone-is-set');
    }
}
