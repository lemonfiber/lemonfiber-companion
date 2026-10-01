<?php

declare(strict_types=1);

namespace Tests\Support;

/** What reading a timestamp came to, carried out of `read()`, which must hand back an object. */
final readonly class WhatTheTimestampNamed
{
    public function __construct(public ?int $seconds) {}
}
