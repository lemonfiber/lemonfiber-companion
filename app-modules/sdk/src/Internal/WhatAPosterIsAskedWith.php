<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

/** The query a held title's poster is asked with: as one member, or as the household's defaults. */
final readonly class WhatAPosterIsAskedWith
{
    /** @param array<string, string> $query */
    public function __construct(public array $query) {}
}
