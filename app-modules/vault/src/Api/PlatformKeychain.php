<?php

declare(strict_types=1);

namespace Modules\Vault\Api;

use Modules\Kernel\Api\Kept;
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
        return $this->store->read(self::keyFor(StackId::rememberedAs('probe')))->status
            !== SecureStorageStatus::Unavailable;
    }

    public function keep(StackId $stack, Session $session): Kept
    {
        return $this->store->set(self::keyFor($stack), $session->forTheHeader())
            ? Kept::safely()
            : Kept::refused($this->whyItRefused());
    }

    public function forget(StackId $stack): Kept
    {
        // The return is deliberately not checked. Forgetting a session that was
        // never kept is the ordinary case after a refusal, and `N4-R6` leaves
        // the app holding a session it could not store — the one thing that
        // must always work is getting rid of it.
        $this->store->delete(self::keyFor($stack));

        return Kept::safely();
    }

    /** Which of the two refusals this was, read from the store rather than guessed. */
    private function whyItRefused(): WhySessionCannotBeKept
    {
        return $this->store->read(self::keyFor(StackId::rememberedAs('probe')))->status
            === SecureStorageStatus::Unavailable
                ? WhySessionCannotBeKept::DeviceHasNoSecureStorage
                : WhySessionCannotBeKept::StoreWouldNotOpen;
    }

    /** One key per stack, so two paired stacks never share a session (`N1-R11`). */
    private static function keyFor(StackId $stack): string
    {
        return sprintf('%s.%s', self::UNDER, $stack->stored());
    }
}
