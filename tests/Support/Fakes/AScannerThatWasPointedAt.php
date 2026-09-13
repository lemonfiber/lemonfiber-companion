<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Closure;

use function is_string;

use Modules\Kernel\Api\WhyNothingWasScanned;
use Native\Mobile\Events\Scanner\CodeScanned;
use Native\Mobile\Events\Scanner\ScannerCancelled;
use Native\Mobile\PendingScanner;
use Native\Mobile\Support\NativeCallbacks;

/**
 * The plugin's scanner, written out by hand, answering what a test told it to.
 *
 * `G1` forbids mocking a type we do not own, and this is not one: it is a
 * subclass with the fluent methods written out, the same thing
 * {@see ANotificationCentre} is to the notification plugin. A mock asserts on
 * calls and drifts silently when the real class changes; this fails to compile.
 *
 * It exists because {@see \Modules\Device\Api\PlatformScanner} cannot otherwise
 * be run at all. The real `PendingScanner` reaches `nativephp_call()` from
 * `scan()`, which is a no-op off a handset — so the adapter would register two
 * callbacks and neither would ever fire, and every assertion about what it does
 * with an answer would be about a callback nobody called.
 *
 * **It answers when `scan()` is called, not when the callback is registered.**
 * That is the order the platform uses and the order that matters: the adapter
 * registers both arms and *then* starts the scanner, and a stand-in that
 * answered on registration would let an adapter pass that had registered only
 * one of them. `PendingScanner::__destruct()` starts a scan nobody started
 * explicitly, which is the trap the adapter documents from its side — so this
 * answers there too, and counts, and the contract test asserts the count is one.
 */
final class AScannerThatWasPointedAt extends PendingScanner
{
    private bool $answered = false;

    private function __construct(
        private readonly ?string $payload,
        private readonly ?WhyNothingWasScanned $why,
    ) {
        parent::__construct();
    }

    /**
     * A scanner that reads the payload named, or comes back with the reason named.
     *
     * One constructor for both because the adapter does not choose between
     * them — the platform does, and a test says which platform it is standing
     * in for.
     */
    public static function it(?string $payload, ?WhyNothingWasScanned $why = null): self
    {
        return new self($payload, $why);
    }

    /**
     * How the composition root hands a scanner over: as the act of opening one.
     *
     * The adapter takes a `Closure(): PendingScanner` rather than a scanner,
     * because `Scanner::scan()` is static and there is no seam in a static
     * call. This is the closure's other end.
     */
    public function opened(): self
    {
        return $this;
    }

    public function scan(): void
    {
        if ($this->answered) {
            return;
        }

        $this->answered = true;

        if (is_string($this->payload)) {
            $this->fire(new CodeScanned($this->payload, 'qr', $this->getId()));

            return;
        }

        $this->fire(new ScannerCancelled(
            cancelled: true,
            reason: $this->said(),
            id: $this->getId(),
        ));
    }

    /**
     * Whatever the platform would have said, for the reason a test named.
     *
     * The inverse of {@see \Modules\Device\Api\WhatTheScannerSaid}, and written
     * out rather than derived from it: deriving it would mean the stand-in and
     * the adapter sharing a translation, so a translation that was wrong in
     * both directions would agree with itself and pass.
     */
    private function said(): ?string
    {
        return match ($this->why) {
            WhyNothingWasScanned::TheCameraIsNotPermitted => 'permission_denied',
            WhyNothingWasScanned::ThereIsNoCamera => 'unavailable',
            // The ordinary dismissal, which the plugin leaves unset rather than
            // naming — so this is `null`, and the adapter reading `null` as a
            // dismissal is a thing the contract test proves rather than assumes.
            default => null,
        };
    }

    /** Hand the event to whichever arm the adapter registered for it. */
    private function fire(object $event): void
    {
        $said = NativeCallbacks::resolve($this->getId(), $event::class);

        if ($said instanceof Closure) {
            $said($event);
        }
    }
}
