<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** Where removing everything lemonfiber keeps has got to, flattened for a template. */
final readonly class TheForgettingTurnedOutToBe
{
    /**
     * @param HowTheReadingWent             $went         whether the stack answered, or what stood in the way
     * @param bool                          $isWorking    whether the stack is still at it
     * @param bool                          $hasEnded     whether the stack has no outcome for it any more
     * @param ARefusalAsShown|null          $refused      the stack's refusal, where it refused
     * @param WhatIsKeptTurnedOutToBe|null  $wouldGo      the stack's list of what would go, where it answered the asking without a yes
     * @param bool                          $theWayInWent whether the yes was given and the answer could not be read, which is this app's way in having gone with the rest
     * @param bool                          $isDone       whether the stack reported it removed
     * @param list<string>                  $gone         each place that went, where it was removed
     * @param list<APlaceLeftAsShown>       $left         each place it could not remove, and why
     */
    public function __construct(
        public HowTheReadingWent $went,
        public bool $isWorking,
        public bool $hasEnded,
        public ?ARefusalAsShown $refused,
        public ?WhatIsKeptTurnedOutToBe $wouldGo,
        public bool $theWayInWent,
        public bool $isDone,
        public array $gone,
        public array $left,
    ) {}
}
