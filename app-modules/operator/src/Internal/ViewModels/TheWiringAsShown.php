<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What one wiring run came to, flattened for a template.
 *
 * A rehearsal says so before anything else, and whether drift could be judged
 * is said before the connections it is about.
 */
final readonly class TheWiringAsShown
{
    /**
     * @param bool                     $rehearsed   whether the run only said what it would do
     * @param string                   $judgedSaid  the catalogue key for whether drift could be judged
     * @param list<ALineOfTheSurvey>   $unsupported what the run could not wire, each with why
     * @param list<AConnectionAsShown> $connections every connection, in the stack's order
     */
    public function __construct(
        public bool $rehearsed,
        public string $judgedSaid,
        public array $unsupported,
        public array $connections,
    ) {}
}
