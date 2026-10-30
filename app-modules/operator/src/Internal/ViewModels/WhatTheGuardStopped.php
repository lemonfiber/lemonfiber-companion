<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** What a guard that saw the data location go did about it. */
final readonly class WhatTheGuardStopped
{
    /**
     * @param string       $said  the catalogue key for whether it stopped the forms
     * @param list<string> $forms the forms it named
     */
    public function __construct(public string $said, public array $forms) {}
}
