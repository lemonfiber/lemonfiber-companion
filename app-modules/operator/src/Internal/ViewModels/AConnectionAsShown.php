<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * One connection a wiring run attempted, flattened for a template.
 *
 * The stack's words are carried as they came: a service's rejection is what an
 * operator will search for.
 */
final readonly class AConnectionAsShown
{
    /**
     * @param string $connection  what it connects, in the stack's words
     * @param string $stateSaid   the catalogue key for how it turned out
     * @param string $said        the reason, or the service's own words for a failure, or empty
     * @param string $ours        what lemonfiber would write, or empty
     * @param string $yours       what the service holds, or empty
     * @param string $breakage    what it breaks, or empty where nothing is broken
     * @param string $remediation what would put it right, or empty where nothing is broken
     */
    public function __construct(
        public string $connection,
        public string $stateSaid,
        public string $said,
        public string $ours,
        public string $yours,
        public string $breakage,
        public string $remediation,
    ) {}
}
