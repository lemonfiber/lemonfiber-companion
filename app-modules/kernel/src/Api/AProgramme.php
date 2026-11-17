<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What is on a channel now: its title, when it ends where the guide says, and its certificate where it carries one.
 *
 * The core leaves out a channel whose programme now is outside the member's
 * limits, so nothing here is checked against an age limit again.
 */
final readonly class AProgramme
{
    private function __construct(private string $title, private WhenItSays $ends, private string $certificate) {}

    public static function of(string $title, WhenItSays $ends, string $certificate): self
    {
        return new self($title, $ends, $certificate);
    }

    public function title(): string
    {
        return $this->title;
    }

    /** When it ends, or unsaid where the guide does not say. */
    public function ends(): WhenItSays
    {
        return $this->ends;
    }

    /** The certificate it carries, or empty where it carries none. */
    public function certificate(): string
    {
        return $this->certificate;
    }
}
