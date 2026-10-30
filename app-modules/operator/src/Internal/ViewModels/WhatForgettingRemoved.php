<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** What the stack reported it removed of what it kept, and what it could not. */
final readonly class WhatForgettingRemoved
{
    /**
     * @param list<string>            $gone each place that went
     * @param list<APlaceLeftAsShown> $left each place it could not remove, and why
     */
    public function __construct(
        public array $gone,
        public array $left,
    ) {}
}
