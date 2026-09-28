<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * What one removal takes and what it leaves alone, in the operator's words.
 *
 * Neither half may be blank: a removal that will not say what it takes, or
 * what it spares, is not one an operator can agree to.
 */
final readonly class WhatGoesAndWhatStays
{
    private function __construct(private string $removes, private string $keeps) {}

    /** The stack's two sentences; a blank one is refused. */
    public static function said(string $removes, string $keeps): self
    {
        if (trim($removes) === '') {
            throw UninstallSaysNothing::about('removes');
        }

        if (trim($keeps) === '') {
            throw UninstallSaysNothing::about('keeps');
        }

        return new self($removes, $keeps);
    }

    /** What it takes, in the operator's words. */
    public function removes(): string
    {
        return $this->removes;
    }

    /** What it leaves alone, in the operator's words. */
    public function keeps(): string
    {
        return $this->keeps;
    }
}
