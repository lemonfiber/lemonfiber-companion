<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What asking a stack what is already on its machine produced, flattened for a template.
 *
 * `$looked` is what the template branches on before drawing an empty list: a
 * survey that could not look says so, and only one that looked and found
 * nothing says the machine has nothing on it.
 */
final readonly class TheSurveyTurnedOutToBe
{
    /**
     * @param bool                   $looked      whether the engine answered at all
     * @param list<AProjectAsFound>  $projects    every project already here, with its services
     * @param list<ALineOfTheSurvey> $conflicts   every port wanted and already held, naming the holder
     * @param list<ALineOfTheSurvey> $beside      where each service would listen instead
     * @param list<ALineOfTheSurvey> $unsupported what cannot be taken over, and why
     * @param list<AModeAsShown>     $modes       what may be done about it, in the stack's order
     */
    public function __construct(
        public HowTheReadingWent $went,
        public bool $looked,
        public array $projects,
        public array $conflicts,
        public array $beside,
        public array $unsupported,
        public TheLinkingCostAsShown $linking,
        public array $modes,
    ) {}
}
