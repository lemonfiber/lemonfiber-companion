<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** One new item, as a row: what it is, its kind and stack beside it, and what tapping it calls. */
final readonly class ANewsRowAsShown
{
    /**
     * @param string $kind  the catalogue key for its kind, one of it
     * @param string $stack the stack's name
     * @param string $tap   what tapping it calls
     */
    public function __construct(
        public WhatAnItemSays $says,
        public string $kind,
        public string $stack,
        public string $tap,
    ) {}
}
