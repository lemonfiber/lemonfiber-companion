<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * What moving the running copy forward would bring and leave behind, in the stack's words.
 *
 * Both are said before anything is agreed to: what a release brings besides
 * the program and when it is fetched, and what updating leaves alone and needs
 * afterwards.
 */
final readonly class WhatAnUpdateWouldBring
{
    private function __construct(private string $carries, private string $afterwards) {}

    /** The stack's two sentences, each required. */
    public static function said(string $carries, string $afterwards): self
    {
        if (trim($carries) === '') {
            throw ItselfSaysNothing::about('carries');
        }

        if (trim($afterwards) === '') {
            throw ItselfSaysNothing::about('afterwards');
        }

        return new self($carries, $afterwards);
    }

    /** What a release brings besides the program, and when any of it is fetched. */
    public function carries(): string
    {
        return $this->carries;
    }

    /** What updating leaves alone, and what it needs afterwards. */
    public function afterwards(): string
    {
        return $this->afterwards;
    }
}
