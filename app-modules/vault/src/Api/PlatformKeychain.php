<?php

declare(strict_types=1);

namespace Modules\Vault\Api;

use Modules\Kernel\Api\Kept;
use Modules\Kernel\Api\Resumed;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\WhySessionCannotBeKept;
use Native\Mobile\SecureStorage as Platform;
use Native\Mobile\SecureStorageStatus;

use function sprintf;

/**
 * The platform's own secure store — Keychain on iOS, Keystore on Android.
 *
 * The one place `N4-R5`'s "the platform's secure storage" becomes a call. Every
 * alternative that requirement names — preferences, an app-readable file, an
 * unencrypted backup — is absent from this class rather than guarded against,
 * which is the only way to be sure: a fallback written for the device that has
 * no store is the line that writes a token to a file.
 *
 * **A key per stack.** `N1-R11` keeps each stack's session separate, and one
 * key holding "the session" is how two stacks come to share one — the second
 * pairing overwrites the first, and the first stack starts answering with
 * somebody else's credential.
 */
final readonly class PlatformKeychain implements SecureStorage
{
    /** What a stored key is prefixed with, so nothing else in the store collides. */
    private const string UNDER = 'lemonfiber.session';

    public function __construct(private Platform $store) {}

    public function isAvailable(): bool
    {
        // Asked by writing nothing and reading a name that is never set: the
        // platform answers `Unavailable` for a device with no store and
        // `NotFound` for a store that is present and empty, which is exactly
        // the distinction being asked about.
        return $this->store->read($this->keyFor(StackId::rememberedAs('probe')))->status
            !== SecureStorageStatus::Unavailable;
    }

    public function keep(StackId $stack, Session $session): Kept
    {
        return $this->store->set($this->keyFor($stack), $session->forTheHeader())
            ? Kept::safely()
            : Kept::refused($this->whyItRefused());
    }

    public function resume(StackId $stack): Resumed
    {
        $found = $this->store->read($this->keyFor($stack));

        // `Found` and nothing else. The platform answers `NotFound` for a store
        // that is present and empty and `Unavailable` for a device with none,
        // and both are the same answer to this question — asking only about
        // `Found` means a status added to the vendor's enum tomorrow is read as
        // "no session" rather than as whichever case happened to be last.
        if ($found->status !== SecureStorageStatus::Found || $found->value === null) {
            return Resumed::notHeld();
        }

        // `Session::of()` refuses a blank, and a store that answered `Found`
        // with an empty string is a store that lost the value rather than one
        // holding a session — so it is read as no session rather than allowed
        // to raise on a launch screen.
        return $found->value === ''
            ? Resumed::notHeld()
            : Resumed::with(Session::of($found->value));
    }

    public function forget(StackId $stack): Kept
    {
        // The return is deliberately not checked. Forgetting a session that was
        // never kept is the ordinary case after a refusal, and `N4-R6` leaves
        // the app holding a session it could not store — the one thing that
        // must always work is getting rid of it.
        $this->store->delete($this->keyFor($stack));

        return Kept::safely();
    }

    /** Which of the two refusals this was, read from the store rather than guessed. */
    private function whyItRefused(): WhySessionCannotBeKept
    {
        return $this->store->read($this->keyFor(StackId::rememberedAs('probe')))->status
            === SecureStorageStatus::Unavailable
                ? WhySessionCannotBeKept::DeviceHasNoSecureStorage
                : WhySessionCannotBeKept::StoreWouldNotOpen;
    }

    /** One key per stack, so two paired stacks never share a session. */
    private function keyFor(StackId $stack): string
    {
        return sprintf('%s.%s', self::UNDER, $stack->stored());
    }
}
