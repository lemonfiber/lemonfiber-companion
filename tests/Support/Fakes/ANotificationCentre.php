<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use NativePHP\LocalNotifications\LocalNotifications as Platform;

/**
 * A stand-in for the plugin's notification centre, written out by hand.
 *
 * `G1` forbids mocking a type we do not own, and this is not one: it is a
 * subclass with the two methods written out, the same thing `APlatformStore` is
 * to the keychain. A mock asserts on calls and drifts silently when the real
 * class changes; this fails to compile.
 *
 * It exists because {@see \Modules\Device\Api\PlatformNotifier} cannot otherwise
 * be run at all. `LocalNotifications::requestPermission()` returns `null` where
 * `nativephp_call()` does not exist, which is every machine that is not a
 * handset — so off a device the real centre always reports "not permitted" and
 * the adapter's whole delivery path is a branch no test could reach.
 */
final class ANotificationCentre extends Platform
{
    /** @var list<ASentNotification> */
    private array $sent = [];

    private function __construct(private readonly bool $permitted) {}

    /** A handset whose operator has allowed notifications. */
    public static function allowing(): self
    {
        return new self(permitted: true);
    }

    /** A handset whose operator has not. */
    public static function refusing(): self
    {
        return new self(permitted: false);
    }

    public function requestPermission(): mixed
    {
        return $this->permitted;
    }

    public function send(string $id): ASentNotification
    {
        $sending = new ASentNotification($id);

        // Held so the test can read what was composed. Safe only because
        // `ASentNotification` is built with dispatching off — holding a live
        // builder would stop it ever sending, which is the trap the adapter's
        // own comment is about.
        $this->sent[] = $sending;

        return $sending;
    }

    /**
     * What this centre was handed, in order.
     *
     * @return list<ASentNotification>
     */
    public function sent(): array
    {
        return $this->sent;
    }
}
