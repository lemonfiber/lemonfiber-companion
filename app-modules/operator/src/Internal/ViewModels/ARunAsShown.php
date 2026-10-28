<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** A run of checks the route names, weighed before the yes: its title, and every check it runs that disturbs something. */
final readonly class ARunAsShown
{
    /**
     * @param string                    $titleSaid  the catalogue key for what the run covers
     * @param string|null               $familySaid the catalogue key for the family a family's run covers, or null for every check
     * @param list<ADisturbanceAsShown> $disturbing every check the run would run that disturbs something, in the stack's order
     * @param bool                      $runnable   whether the route names a run this app can send
     */
    public function __construct(
        public string $titleSaid,
        public ?string $familySaid,
        public array $disturbing,
        public bool $runnable,
    ) {}
}
