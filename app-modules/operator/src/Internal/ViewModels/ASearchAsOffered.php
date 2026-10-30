<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** A search of the indexers that would settle a trace, as the stack offers it. */
final readonly class ASearchAsOffered
{
    /**
     * @param string $spends what it spends, in the stack's words
     * @param string $covers what it covers, in the stack's words
     */
    public function __construct(public string $spends, public string $covers) {}
}
