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
 *
 * **The exit code is not drawn on the card.** It rides along for the road to
 * the service's logs, which say it above the lines, where somebody finding out
 * what happened is already reading.
 */
final readonly class AnAffectedItemAsShown
{
    /**
     * @param string       $severity   the key for how bad it is
     * @param string       $summary    what is wrong, in one line
     * @param string       $meaning    what it costs the operator
     * @param list<string> $remedies   what to try, most likely first
     * @param list<string> $downstream what else is wrong because of it
     * @param string       $check      which check raised it, to find it again by
     * @param string       $exited     the code a service it names exited on, or empty
     */
    public function __construct(
        public string $severity,
        public string $summary,
        public string $meaning,
        public array $remedies,
        public array $downstream,
        public string $check = '',
        public string $exited = '',
    ) {}

    /** Whether the card says the stack suggested nothing to try, which is where somebody to ask is owed. */
    public function saysNothingToTry(): bool
    {
        return $this->remedies === [];
    }
}
