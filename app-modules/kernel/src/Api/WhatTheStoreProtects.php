<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * What keeping credentials in files protects against, and what it does not.
 *
 * Two lists and a sentence, as the stack words them.
 */
final readonly class WhatTheStoreProtects
{
    private function __construct(private string $summary, private Remarks $against, private Remarks $notAgainst) {}

    /** What the stack said of its store; a blank summary is refused. */
    public static function said(string $summary, Remarks $against, Remarks $notAgainst): self
    {
        if (trim($summary) === '') {
            throw CredentialSaysNothing::about('summary');
        }

        return new self($summary, $against, $notAgainst);
    }

    /** What the storage is, before any claim about it. */
    public function summary(): string
    {
        return $this->summary;
    }

    /** What it protects against. */
    public function against(): Remarks
    {
        return $this->against;
    }

    /** What it does not protect against. */
    public function notAgainst(): Remarks
    {
        return $this->notAgainst;
    }
}
