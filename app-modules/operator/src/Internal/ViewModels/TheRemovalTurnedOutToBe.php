<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * Where taking somebody out has got to, flattened for the template.
 *
 * One model for every state the screen can be in, each saying only its own:
 * the stack still working, what taking them out costs, what it did, the
 * stack refusing with its reason, the work ended with no outcome, or a route
 * that names nobody.
 */
final readonly class TheRemovalTurnedOutToBe
{
    /**
     * @param HowTheReadingWent    $went         whether the stack was reached, and what stood in the way where it was not
     * @param string               $name         the name that was asked about, which every state is drawn with
     * @param bool                 $namesNobody  whether the screen was opened on nobody at all
     * @param bool                 $wasAgreed    whether a yes was sent, so what could not be read is whether they were taken out
     * @param bool                 $isWorking    whether the stack is still carrying it out
     * @param bool                 $hasEnded     whether the stack has no outcome for it any more
     * @param string               $refusal      the stack's reason for refusing, or empty
     * @param ARemovalAsShown|null $removal      what the stack answered, or nothing where none has come back
     */
    public function __construct(
        public HowTheReadingWent $went,
        public string $name,
        public bool $namesNobody,
        public bool $wasAgreed,
        public bool $isWorking,
        public bool $hasEnded,
        public string $refusal,
        public ?ARemovalAsShown $removal,
    ) {}
}
