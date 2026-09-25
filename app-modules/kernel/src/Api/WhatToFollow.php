<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/** What a trace is asked to follow: an item, by the name somebody knows it by. */
final readonly class WhatToFollow
{
    private function __construct(private string $term) {}

    /** The term to follow; a blank one is refused, since it follows nothing. */
    public static function called(string $term): self
    {
        $trimmed = trim($term);

        if ($trimmed === '') {
            throw TheTraceSaysNothing::about('term');
        }

        return new self($trimmed);
    }

    public function term(): string
    {
        return $this->term;
    }
}
