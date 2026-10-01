<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What became of the verb sent from this screen, flattened for a template.
 *
 * Every field the stack's report fills is empty until there is a report, so a
 * template cannot name what did not come back off a verb still running.
 */
final readonly class HowTheVerbWent
{
    /**
     * @param HowTheReadingWent            $went                    whether asking after it came back, and what stood in the way where it did not
     * @param bool                         $wasAsked                whether a verb was sent here, so there is anything to follow
     * @param bool                         $isWorking               whether the stack is still carrying it out
     * @param bool                         $hasEnded                whether the stack has no outcome for it any more
     * @param bool                         $wasRehearsed            whether the report is of a rehearsal, which changed nothing
     * @param ?string                      $cameToSaid              the catalogue key for what it came to, in the tense the report allows
     * @param string                       $because                 the stack's reason for declining, or empty where it ran
     * @param ?string                      $amountsToSaid           the catalogue key for what the stack says those services amount to, or empty where it did not say
     * @param bool                         $namesWhatDidNotComeBack whether a verb that brings services up ran and did not bring everything back, so what did not is named
     * @param list<AServiceNotBackAsShown> $notBack                 every service short of running, with where it stood
     * @param ?string                      $leftOutSaid             the catalogue key for a service left out, in the tense the report allows
     * @param list<AServiceLeftOutAsShown> $leftOut                 the services the plan left out, each with what it needed
     * @param list<APortHeldAsShown>       $portsHeld               the ports it wanted that something else holds, each naming what holds it
     * @param list<AnEditAsShown>          $editsKept               the stack files the operator edited, which it left as they set them
     */
    public function __construct(
        public HowTheReadingWent $went,
        public bool $wasAsked,
        public bool $isWorking,
        public bool $hasEnded,
        public bool $wasRehearsed,
        public ?string $cameToSaid,
        public string $because,
        public ?string $amountsToSaid,
        public bool $namesWhatDidNotComeBack,
        public array $notBack,
        public ?string $leftOutSaid,
        public array $leftOut,
        public array $portsHeld,
        public array $editsKept,
    ) {}
}
