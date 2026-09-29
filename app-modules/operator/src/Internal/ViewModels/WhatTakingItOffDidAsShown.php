<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What a removal agreed to did, as the rows that draw it: a rehearsal, complete, or partial.
 */
final readonly class WhatTakingItOffDidAsShown
{
    /**
     * @param string                        $saidAs      the catalogue key for how far it got
     * @param bool                          $isFinished  whether it removed anything, which a rehearsal did not
     * @param list<string>                  $gone        what went, by the name the reading gave it
     * @param list<string>                  $credentials the credentials it destroyed, by name
     * @param list<SomethingOutsideAsShown> $left        what is still there, with what the machine said and how to finish by hand
     */
    public function __construct(
        public string $saidAs,
        public bool $isFinished,
        public array $gone,
        public array $credentials,
        public array $left,
    ) {}
}
