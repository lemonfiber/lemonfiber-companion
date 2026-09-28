<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * The forms a guard can be asked for, and which of them are named, flattened for a template.
 */
final readonly class WhatTheGuardWouldGuard
{
    /**
     * @param list<AFormToGuardAsShown> $forms    every form the stack declares, each saying whether it is named
     * @param string                    $named    the forms named, joined, or empty where none is
     * @param bool                      $canStart whether any is named, so a guard can be asked about
     */
    public function __construct(
        public array $forms,
        public string $named,
        public bool $canStart,
    ) {}
}
