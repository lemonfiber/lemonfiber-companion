<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Modules\Kernel\Api\Assembled;
use Modules\Kernel\Api\Handed;
use Modules\Kernel\Api\Sharing;
use Modules\Kernel\Api\WhyNothingWasShared;

/**
 * A share sheet that remembers what it was handed.
 *
 * The stand-in every screen offering a report gets, so that no test needs a
 * phone. What it remembers is what `N4-R13` is about: the *text* that would
 * have left the device. A fake that only counted calls would let an adapter
 * hand over a session token and stay green, and that is the one thing this
 * whole path exists to prevent.
 *
 * Not `readonly`: what was handed over is written when the handing happens.
 */
final class AShareSheetThatWasOffered implements Sharing
{
    /** What it was handed, or nothing where it never was. */
    private ?Assembled $handed = null;

    private function __construct(private readonly ?WhyNothingWasShared $refusing) {}

    /** A sheet that opens. */
    public static function working(): self
    {
        return new self(null);
    }

    /** One that does not, for the reason given. */
    public static function refusing(WhyNothingWasShared $why): self
    {
        return new self($why);
    }

    /** What it was handed, for a test to read every word of. */
    public function handed(): ?Assembled
    {
        return $this->handed;
    }

    public function hand(Assembled $assembled): Handed
    {
        // Recorded even where the sheet refuses, which is what lets a test ask
        // the question that matters — what *would* have left the device — about
        // both arms rather than only the happy one.
        $this->handed = $assembled;

        return $this->refusing instanceof WhyNothingWasShared
            ? Handed::refused($this->refusing)
            : Handed::over();
    }
}
