<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What following one item produced, flattened for a template.
 *
 * Nothing asked for is its own answer, and a trace that could not be read is
 * an obstacle: neither is drawn as an item that got nowhere.
 */
final readonly class TheTraceTurnedOutToBe
{
    /**
     * @param string               $item          what was followed
     * @param bool                 $followed      whether a monitored item matched it
     * @param string               $sureSaid      the catalogue key for how sure the trace is
     * @param bool                 $isUncertain   whether it may not be the item asked for
     * @param AStageAsShown|null   $furthest      the furthest stage, with no service or time
     * @param string               $stall         why it stopped, or empty
     * @param list<AStageAsShown>  $stages        the stages it passed through
     * @param list<AMomentAsShown> $history       what was tried, oldest first
     * @param list<string>         $disagreements where two services' views of it contradict
     * @param HowMuchIsHereAsShown $here         how much of a series is here, or nothing to count for a whole item
     */
    public function __construct(
        public HowTheReadingWent $went,
        public string $item,
        public bool $followed,
        public string $sureSaid,
        public bool $isUncertain,
        public ?AStageAsShown $furthest,
        public string $stall,
        public array $stages,
        public array $history,
        public array $disagreements,
        public HowMuchIsHereAsShown $here,
    ) {}
}
