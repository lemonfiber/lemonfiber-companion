<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** Why waking a stack stayed off, as the switch says it: the stack's own words, or a line of this app's. */
final readonly class WhatWakingSaid
{
    /**
     * @param string                $words the stack's own sentence, or empty where this app says it
     * @param string                $line  the key for this app's line, or empty where the stack said it
     * @param array<string, int> $with  what the line names
     */
    public function __construct(public string $words, public string $line, public array $with) {}
}
