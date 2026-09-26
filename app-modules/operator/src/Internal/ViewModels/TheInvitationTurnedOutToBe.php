<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * Where asking somebody in has got to, flattened for the template.
 *
 * One model for every state the screen can be in, each saying only its own:
 * nothing asked yet, the stack still working, an invitation answered, the
 * stack refusing with its reason, the work ended with no outcome, or what was
 * typed not being something to ask with.
 */
final readonly class TheInvitationTurnedOutToBe
{
    /**
     * @param HowTheReadingWent        $went       whether the stack was reached, and what stood in the way where it was not
     * @param bool                     $isWorking  whether the stack is still carrying it out
     * @param bool                     $hasEnded   whether the stack has no outcome for it any more
     * @param string                   $refusal    the stack's reason for refusing, or empty
     * @param string                   $askedFor   the name that was asked for, which a refusal is drawn with
     * @param string                   $notAskable the catalogue key for why what was typed was not sent, or empty
     * @param AnInvitationAsShown|null $invitation the invitation the stack answered, or nothing where none has come back
     */
    public function __construct(
        public HowTheReadingWent $went,
        public bool $isWorking,
        public bool $hasEnded,
        public string $refusal,
        public string $askedFor,
        public string $notAskable,
        public ?AnInvitationAsShown $invitation,
    ) {}
}
