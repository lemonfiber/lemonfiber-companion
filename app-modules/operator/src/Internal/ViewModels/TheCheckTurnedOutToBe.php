<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** One finding the stack leaves unverified because checking it disturbs something, with what running it would disturb, flattened for a template. */
final readonly class TheCheckTurnedOutToBe
{
    /**
     * @param HowTheReadingWent $went      whether the stack answered, or what stood in the way
     * @param string            $title     what the check is called, in the stack's words, or empty where the report holds no such check
     * @param string            $verdict   the catalogue key for how the check stands now
     * @param string            $meaning   what the stack says about it, or empty
     * @param string            $disturbs  what running it disturbs, in the stack's words, or empty where running it disturbs nothing
     * @param string            $boundSaid the catalogue key for how long the stack bounds the disturbance, or for its giving no bound
     * @param int|null          $seconds   the bound, where the stack gives one
     * @param string            $check     the check a yes runs, empty where nothing is offered to run
     * @param string            $only      the check or group a diagnosis is narrowed to, to run it, or empty
     */
    public function __construct(
        public HowTheReadingWent $went,
        public string $title,
        public string $verdict,
        public string $meaning,
        public string $disturbs,
        public string $boundSaid,
        public ?int $seconds,
        public string $check,
        public string $only,
    ) {}

    /** Whether the report holds a finding about this check at all. */
    public function isFound(): bool
    {
        return $this->title !== '';
    }
}
