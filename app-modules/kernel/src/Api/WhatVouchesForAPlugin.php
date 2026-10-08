<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * Where a plugin came from and what vouches for it, as the stack recorded it.
 *
 * **Reviewed or not is always said.** A plugin installed by name through a
 * catalogue whose signature verified was reviewed; one from a source the
 * operator named was not, and stays so for as long as it is installed. Every
 * other word may be empty where the stack has nothing to say: a directory has
 * no revision, and nothing signed a source the operator named.
 */
final readonly class WhatVouchesForAPlugin
{
    private function __construct(
        private string $source,
        private string $revision,
        private string $signed,
        private bool $reviewed,
        private string $upstream,
        private string $licence,
    ) {}

    /** What the stack recorded; empty words are what it left out. */
    public static function recorded(string $source, string $revision, string $signed, bool $reviewed, string $upstream, string $licence): self
    {
        return new self(trim($source), trim($revision), trim($signed), $reviewed, trim($upstream), trim($licence));
    }

    /** The source it was installed from, as the operator named it, or empty. */
    public function source(): string
    {
        return $this->source;
    }

    /** The commit it was installed at, where it came from a git source, or empty. */
    public function revision(): string
    {
        return $this->revision;
    }

    /** The key that signed it, named with its fingerprint, or empty where nothing did. */
    public function signed(): string
    {
        return $this->signed;
    }

    /** Whether anybody reviewed it before it was installed. */
    public function wasReviewed(): bool
    {
        return $this->reviewed;
    }

    /** Where its own source is published, as it names it, or empty. */
    public function upstream(): string
    {
        return $this->upstream;
    }

    /** The licence it is distributed under, or empty. */
    public function licence(): string
    {
        return $this->licence;
    }
}
