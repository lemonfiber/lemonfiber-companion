<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * One thing the health summary counts as wrong, flattened for a template to read.
 *
 * Every field is the core's own words except the severity, which is a key: the
 * sentences are the machine's about the machine, and putting them through the
 * catalogue would mean this app writing lines for checks it has never heard
 * of.
 */
final readonly class AnAffectedItemAsShown
{
    /**
     * @param string       $check      which check raised it, as the core names it
     * @param string       $severity   the key for how bad it is
     * @param string       $summary    what is wrong, in one line
     * @param string       $meaning    what it costs the operator
     * @param list<string> $remedies   what to try, most likely first
     * @param list<string> $downstream what else is wrong because of it
     */
    public function __construct(
        public string $check,
        public string $severity,
        public string $summary,
        public string $meaning,
        public array $remedies,
        public array $downstream,
    ) {}
}
