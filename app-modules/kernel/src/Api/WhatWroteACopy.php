<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * Which version of lemonfiber wrote a copy, and when.
 *
 * Both halves are required: a copy that will not say what wrote it is one
 * nobody can judge.
 */
final readonly class WhatWroteACopy
{
    private function __construct(
        private string $version,
        private AMomentAsWritten $at,
    ) {}

    /** The version that wrote it and when, as the stack stamped them. */
    public static function of(string $version, AMomentAsWritten $at): self
    {
        if (trim($version) === '') {
            throw KeepingSaysNothing::about('product_version');
        }

        return new self($version, $at);
    }

    /** The version of lemonfiber that wrote it. */
    public function version(): string
    {
        return $this->version;
    }

    /** When it was taken, as the stack counted it. */
    public function at(): AMomentAsWritten
    {
        return $this->at;
    }
}
