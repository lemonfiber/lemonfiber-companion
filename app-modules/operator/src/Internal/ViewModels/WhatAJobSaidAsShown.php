<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** What a job followed has said, as `<x-operator::following-a-job>` draws it. */
final readonly class WhatAJobSaidAsShown
{
    /** @param list<string> $lines every line kept, oldest first, where they are open; none where they are not */
    public function __construct(
        public ?string $newest,
        public array $lines,
        public bool $showingAll,
        public int $kept,
        public int $letGo,
    ) {}
}
