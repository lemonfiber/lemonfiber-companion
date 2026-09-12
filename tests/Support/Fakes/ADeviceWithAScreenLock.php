<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Native\Mobile\Testing\FakeBridge;

/**
 * The bridge answers a handset would give about authentication.
 *
 * Scripted into `nativephp/mobile`'s own `FakeBridge`, so the adapter above runs
 * the real call — the name from the manifest, the JSON out, the JSON back —
 * rather than a path built to resemble it.
 *
 * A class rather than three closures, for the reason `AHandsetsWindow` is one:
 * closures capturing literals leave the analyser reading the first branch as
 * permanently dead.
 */
final readonly class ADeviceWithAScreenLock
{
    private function __construct(
        private bool $available,
        private bool $raises,
    ) {}

    /** A handset with a screen lock, where the prompt is raised. */
    public static function ready(): self
    {
        return new self(available: true, raises: true);
    }

    /** A handset with no screen lock, which can ask nobody. */
    public static function withNoScreenLock(): self
    {
        return new self(available: false, raises: false);
    }

    /** Bind this as the bridge's answers, replacing any already bound. */
    public function bind(): void
    {
        FakeBridge::disable();
        FakeBridge::enable()
            ->respondTo('Lemonfiber.CanAuthenticate', ['canAuthenticate' => $this->available])
            ->respondTo('Lemonfiber.Authenticate', ['acknowledged' => $this->raises]);
    }
}
