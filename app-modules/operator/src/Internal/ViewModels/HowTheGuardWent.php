<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * Where the guard started from this screen stands, flattened for a template.
 *
 * A guard that ended says which of four ways it ended in its own sentence,
 * and only one that saw the data location go says whether it stopped the
 * forms — each in words that cannot be mistaken for the other.
 */
final readonly class HowTheGuardWent
{
    /**
     * @param WhereTheWorkStands  $stands   where the guard stands: not asked before one was started here, running while it guards, answered once it ended
     * @param string|null         $guarding the forms it guards, joined, while it guards
     * @param HowAGuardEnded|null $ended    how it ended, once it did
     */
    public function __construct(
        public WhereTheWorkStands $stands,
        public ?string $guarding = null,
        public ?HowAGuardEnded $ended = null,
    ) {}
}
