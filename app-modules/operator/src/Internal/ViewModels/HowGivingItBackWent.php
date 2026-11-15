<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What became of giving back one category, drawn only from the stack's report of what it reclaimed.
 *
 * `$returned` is null wherever there is no report. `$left` holds the stack's
 * reason for each item left behind, and never where the item is.
 */
final readonly class HowGivingItBackWent
{
    /**
     * @param int<0, max>  $went how many items went
     * @param list<string> $left the stack's reason for each item left behind, in its order
     */
    public function __construct(
        public WhereTheWorkStands $stands,
        public ?ARefusalAsShown $refused,
        public ?ASizeAsShown $returned,
        public int $went,
        public array $left,
    ) {}
}
