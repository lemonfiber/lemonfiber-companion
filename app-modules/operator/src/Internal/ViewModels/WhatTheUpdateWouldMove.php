<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** What the pending update would move, as its confirmation draws it. */
final readonly class WhatTheUpdateWouldMove
{
    /**
     * @param list<AMoveInPlaceAsShown> $moves  the moves in place it would take
     * @param list<AMoveInPlaceAsShown> $guards each of lemonfiber's own guards it would move, for good
     */
    public function __construct(public array $moves = [], public array $guards = []) {}
}
