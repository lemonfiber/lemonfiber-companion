<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** What turning a way of downloading on opened, in the stack's order, and whether the stack has proven its login. */
final readonly class WhatAProtocolOpenedAsShown
{
    /** @param list<ADecisionAsShown> $steps */
    public function __construct(public string $key, public array $steps, public bool $proven) {}

    /** Whether this key is one of the steps. */
    public function among(string $key): bool
    {
        $among = false;

        foreach ($this->steps as $step) {
            $among = $among || $step->key === $key;
        }

        return $among;
    }
}
