<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** What became of the forwarded port across a start, flattened for the restart section. */
final readonly class APortAfterAStartAsShown
{
    /**
     * @param string $said the key for what became of it, or empty where the stack forwards none
     * @param int    $was  the port before the start, where the key names it
     * @param int    $now  the port after it, where the key names it
     */
    public function __construct(
        public string $said,
        public int $was,
        public int $now,
    ) {}
}
