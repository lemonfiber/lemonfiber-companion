<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Replacing the internal certificate, agreed to against the reading the operator was shown.
 *
 * The yes quotes the reading's name, so a replacement whose reading has since
 * moved is refused by the stack rather than carried out on an old one.
 */
final readonly class ACertificateReplacementAgreed
{
    private function __construct(private string $agreement) {}

    /** Agreed against that reading; anything but a reading is refused. */
    public static function after(ACertificateReplacement $reading): self
    {
        if (! $reading->isAReading()) {
            throw ACertificateReplacementWasNotRead::before();
        }

        return new self($reading->agreement());
    }

    /** The name of the reading agreed to. */
    public function agreement(): string
    {
        return $this->agreement;
    }
}
