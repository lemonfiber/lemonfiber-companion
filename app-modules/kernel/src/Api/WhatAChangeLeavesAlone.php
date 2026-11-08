<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * One thing a change leaves exactly as it is, in the stack's words.
 */
final readonly class WhatAChangeLeavesAlone
{
    private function __construct(private string $said) {}

    public static function said(string $said): self
    {
        $trimmed = trim($said);

        if ($trimmed === '') {
            throw AFindingSaysNothing::about('keeps');
        }

        return new self($trimmed);
    }

    public function shown(): string
    {
        return $this->said;
    }
}
