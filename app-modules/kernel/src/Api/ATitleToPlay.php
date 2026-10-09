<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * One title as the player is handed it: where the core said it streams from,
 * the door's certificate, the member's grant, where to start, what to call it,
 * and the languages the member chose to hear and read it in.
 *
 * Every part that reaches the door is the core's. The location and the
 * certificate are what the core stated for this title, and the grant is what
 * it answered for this member on this device, so nothing here can point the
 * player anywhere the core did not.
 *
 * It carries the grant, so it goes nowhere the grant may not: {@see AGrant}
 * refuses to be written down, and so does anything holding one.
 */
final readonly class ATitleToPlay
{
    private function __construct(
        private Location $location,
        private Fingerprint $door,
        private AGrant $grant,
        private HowFarIn $startAt,
        private string $named,
        private TheirLanguages $languages,
    ) {}

    /** A title, at the location and door the core stated, under the grant it answered. */
    public static function of(Location $location, Fingerprint $door, AGrant $grant, HowFarIn $startAt, string $named, TheirLanguages $languages): self
    {
        return new self($location, $door, $grant, $startAt, $named, $languages);
    }

    public function location(): Location
    {
        return $this->location;
    }

    public function door(): Fingerprint
    {
        return $this->door;
    }

    public function grant(): AGrant
    {
        return $this->grant;
    }

    public function startAt(): HowFarIn
    {
        return $this->startAt;
    }

    /** What the lock screen and picture-in-picture call it. */
    public function named(): string
    {
        return $this->named;
    }

    /** What the member chose to hear and read it in, which is handed to the player every time. */
    public function languages(): TheirLanguages
    {
        return $this->languages;
    }
}
