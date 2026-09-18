<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Modules\Kernel\Api\Capture;

/**
 * A window that remembers what it was asked, on a machine that has none.
 *
 * What every test needing "this screen must not be photographed" hands its
 * subject. The contract test is what keeps it honest: it is held to the same
 * assertions as {@see \Modules\Device\Api\PlatformScreen}, so a fake that is
 * easier to satisfy than the platform fails there rather than quietly making the
 * suite green (`G2`).
 *
 * It models the rule the native halves implement, including the part that is
 * easy to forget: a backgrounded app is protected whatever it is showing, so
 * revealing while away leaves the window protected. A fake that returned `false`
 * from `reveal()` unconditionally would look right and would disagree with both
 * platforms.
 */
final class ACaptureInMemory implements Capture
{
    private bool $concealed = false;

    private function __construct(private bool $foreground) {}

    /** A device somebody is looking at. */
    public static function inFront(): self
    {
        return new self(foreground: true);
    }

    /** A device that has been put away, or taken a call. */
    public static function away(): self
    {
        return new self(foreground: false);
    }

    public function conceal(): bool
    {
        $this->concealed = true;

        return $this->isProtected();
    }

    public function reveal(): bool
    {
        $this->concealed = false;

        return $this->isProtected();
    }

    public function isProtected(): bool
    {
        return $this->concealed || ! $this->foreground;
    }

    /** The app moves out of the foreground, which protects the window on its own. */
    public function backgrounded(): void
    {
        $this->foreground = false;
    }
}
