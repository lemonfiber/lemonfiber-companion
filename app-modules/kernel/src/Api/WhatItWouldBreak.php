<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * How serious one connection's outcome is: information, or a warning that says what breaks and what puts it right.
 *
 * A warning cannot be built without both halves. A severity alone is a colour,
 * and a warning the operator cannot act on is noise.
 */
final readonly class WhatItWouldBreak
{
    private function __construct(private string $breakage, private string $remediation) {}

    /** Nothing is broken. */
    public static function nothing(): self
    {
        return new self('', '');
    }

    /** The connection breaks the stack: what breaks, and what would put it right. */
    public static function warning(string $breakage, string $remediation): self
    {
        foreach (['breakage' => $breakage, 'remediation' => $remediation] as $field => $said) {
            if (trim($said) === '') {
                throw TheWiringSaysNothing::about($field);
            }
        }

        return new self($breakage, $remediation);
    }

    /** Whether this is a warning rather than information. */
    public function isAWarning(): bool
    {
        return $this->breakage !== '';
    }

    /** What breaks, or `''` where nothing does. */
    public function breakage(): string
    {
        return $this->breakage;
    }

    /** What would put it right, or `''` where nothing is broken. */
    public function remediation(): string
    {
        return $this->remediation;
    }
}
