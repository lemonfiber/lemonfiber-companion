<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What asking a stack for its front door produced, flattened for a template.
 */
final readonly class TheFrontDoorTurnedOutToBe
{
    /**
     * @param string                      $standingSaid the catalogue key for where the door stands, or empty where nothing came back
     * @param string                      $meaning      what this comes to, in the stack's words
     * @param string                      $chosenSaid   the catalogue key for how the door came to be
     * @param string                      $named        what the operator named as the door, or empty
     * @param string                      $refusal      why what they named is not the door, or empty
     * @param list<AServiceBesideAsShown> $beside       everything else they can reach
     */
    public function __construct(
        public HowTheReadingWent $went,
        public string $standingSaid,
        public string $meaning,
        public string $chosenSaid,
        public string $named,
        public string $refusal,
        public WhereTheyBeginAsShown $begins,
        public array $beside,
    ) {}
}
