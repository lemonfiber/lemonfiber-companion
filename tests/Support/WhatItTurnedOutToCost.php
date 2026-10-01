<?php

declare(strict_types=1);

namespace Tests\Support;

/** One answer carried out of an `either()` arm, which hands back objects. */
final readonly class WhatItTurnedOutToCost
{
    public function __construct(public int $seconds, public string $said) {}
}
