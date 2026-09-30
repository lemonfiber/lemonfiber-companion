<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Native\Mobile\Testing\FakeBridge;

/**
 * The bridge answers a handset would give about its lock.
 *
 * Scripted into `nativephp/mobile`'s own `FakeBridge`, so the adapter runs the
 * real call — the name from the manifest, the JSON out, the JSON back — rather
 * than a path built to resemble it. It answers as `LockRule` does: the lock
 * stands from a cold start, only the prompt's success or a waiver opens it, and
 * a handset with no screen lock never stands.
 */
final class ADeviceWithAScreenLock
{
    private bool $open;

    private function __construct(
        private readonly bool $available,
        private readonly bool $succeeds,
    ) {
        $this->open = ! $available;
    }

    /** A handset with a screen lock, whose operator authenticates. */
    public static function ready(): self
    {
        return new self(available: true, succeeds: true);
    }

    /** A handset with a screen lock, whose operator does not. */
    public static function refusing(): self
    {
        return new self(available: true, succeeds: false);
    }

    /** A handset with no screen lock, which can ask nobody. */
    public static function withNoScreenLock(): self
    {
        return new self(available: false, succeeds: false);
    }

    /** Bind this as the bridge's answers, replacing any already bound. */
    public function bind(): self
    {
        FakeBridge::disable();
        FakeBridge::enable()
            ->respondTo('Lemonfiber.CanAuthenticate', ['canAuthenticate' => $this->available])
            ->respondTo('Lemonfiber.Authenticate', $this->authenticate(...))
            ->respondTo('Lemonfiber.Lock.Standing', $this->standing(...))
            ->respondTo('Lemonfiber.Lock.Waive', $this->waive(...))
            ->respondTo('Lemonfiber.Lock.Drawn', $this->standing(...));

        return $this;
    }

    /** The app came back after longer than it may be away. */
    public function standsAgain(): void
    {
        $this->open = ! $this->available;
    }

    /** @return array{authenticated: bool} */
    private function authenticate(): array
    {
        $this->open = $this->open || $this->succeeds;

        return ['authenticated' => $this->succeeds];
    }

    /** @return array{open: bool} */
    private function standing(): array
    {
        return ['open' => $this->open];
    }

    /** @return array{open: bool} */
    private function waive(): array
    {
        $this->open = true;

        return $this->standing();
    }
}
