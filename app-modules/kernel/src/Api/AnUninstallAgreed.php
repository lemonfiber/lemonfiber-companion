<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Taking lemonfiber off, agreed to against the reading the operator was shown.
 *
 * The only way to make one is against a reading, and it carries that
 * reading's name, so the stack carries out exactly what was listed or refuses
 * a reading that has moved on. Where the reading said the data location is on
 * a network share or a drive that unplugs, it is refused unless that was
 * acknowledged apart. Whether what is still coming down is let land is part
 * of the agreement, and neither answer is assumed.
 */
final readonly class AnUninstallAgreed
{
    private function __construct(private WhichRemoval $tier, private string $agreement, private WhetherToWait $waiting) {}

    /** Agreed against that reading; an answer that was not a reading, or a volume nobody acknowledged, is refused. */
    public static function after(AnUninstall $surveyed, WhetherToWait $waiting, bool $acknowledgedTheVolume): self
    {
        if (! $surveyed->removal()->isAReading()) {
            throw UninstallWasNotSurveyed::becauseItWasNotAReading();
        }

        $manifest = $surveyed->manifest();

        if ($manifest->volume() !== '' && ! $acknowledgedTheVolume) {
            throw UninstallWasNotSurveyed::becauseTheVolumeWasNotAcknowledged();
        }

        return new self($manifest->tier(), $manifest->agreement(), $waiting);
    }

    /** Which removal was agreed to. */
    public function tier(): WhichRemoval
    {
        return $this->tier;
    }

    /** The name of the reading agreed to. */
    public function agreement(): string
    {
        return $this->agreement;
    }

    /** Whether what is still coming down is let land first. */
    public function waiting(): WhetherToWait
    {
        return $this->waiting;
    }
}
