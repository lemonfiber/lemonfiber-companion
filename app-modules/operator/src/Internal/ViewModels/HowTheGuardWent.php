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
     * @param HowTheReadingWent $went        whether asking after it came back, and what stood in the way where it did not
     * @param bool              $wasAsked    whether a guard was started here, so there is anything to follow
     * @param bool              $isGuarding  whether it is still guarding
     * @param string            $endedSaid   the catalogue key for how it ended, or empty while it guards or before one was asked for
     * @param list<string>      $forms       the forms it guards, or the forms it named once it saw the data location go
     * @param string            $named       those forms, joined
     * @param string            $stoppedSaid the catalogue key for whether it stopped them, or empty where it did not see the data location go
     * @param string            $said        the stack's own words for why it ended or why it did not start, or empty
     */
    public function __construct(
        public HowTheReadingWent $went,
        public bool $wasAsked,
        public bool $isGuarding,
        public string $endedSaid,
        public array $forms,
        public string $named,
        public string $stoppedSaid,
        public string $said,
    ) {}
}
