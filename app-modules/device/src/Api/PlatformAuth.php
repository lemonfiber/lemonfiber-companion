<?php

declare(strict_types=1);

namespace Modules\Device\Api;

use Lemonfiber\Native\Screen as Native;
use Modules\Device\Internal\Words;
use Modules\Kernel\Api\Authenticated;
use Modules\Kernel\Api\DeviceAuth;
use Modules\Kernel\Api\Lock;

/**
 * The device's own authentication, reached through lemonfiber's native expansion.
 *
 * Thin on purpose. The decision that matters — *when* the app must be locked and
 * when it may ask — lives in `LockRule`, in Kotlin and in Swift, with the same
 * seven tests each. What is decided here is only how an answer becomes a
 * {@see Lock}.
 *
 * **Why our own plugin rather than the stock biometric call.** `N4-R8` says a
 * biometric failure falls back to the device passcode. On iOS that is the
 * difference between `.deviceOwnerAuthenticationWithBiometrics` and
 * `.deviceOwnerAuthentication`; on Android it is whether `DEVICE_CREDENTIAL` is
 * among the accepted authenticators. Both are constants chosen before the dialog
 * is shown, neither is visible from PHP, and the published examples reach for
 * the biometrics-only form — which locks an operator with a passcode and no
 * fingerprint out of their own app. A requirement this application owns is one
 * it has to be able to point at a line for.
 */
final readonly class PlatformAuth implements DeviceAuth
{
    public function __construct(private Native $device, private Words $words) {}

    public function isAvailable(): bool
    {
        return $this->device->canAuthenticate();
    }

    public function unlock(): Lock
    {
        // `Authenticated::byTheDevice()` is reached on exactly one condition and
        // from exactly one line in this application. That is the thin guarantee
        // `N4-R8`'s second clause rests on, and it is the right thin guarantee:
        // the mistake is now in one file that exists to be read carefully,
        // rather than available anywhere somebody holds a boolean.
        return $this->device->authenticate($this->reason())
            ? Lock::openedBy(Authenticated::byTheDevice())
            : Lock::held();
    }

    /**
     * The sentence the platform shows in its own dialog.
     *
     * From the catalogue rather than from a caller, which is what `L1` asks for
     * and what `D2` pushed this design towards: with no parameter to pass, there
     * is nowhere for a literal to get in.
     */
    private function reason(): string
    {
        return $this->words->for('device.unlock_reason');
    }
}
