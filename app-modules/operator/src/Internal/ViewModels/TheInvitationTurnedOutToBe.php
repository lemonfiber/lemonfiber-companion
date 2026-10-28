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
     * @param WhereTheWorkStands        $stands       where the work stands, and whether the stack could be asked at all
     * @param string                   $refusal    the stack's reason for refusing, or empty
     * @param string                   $askedFor   the name that was asked for, which a refusal is drawn with
     * @param string                   $notAskable the catalogue key for why what was typed was not sent, or empty
     * @param AnInvitationAsShown|null $invitation the invitation the stack answered, or nothing where none has come back
     */
    public function __construct(
        public WhereTheWorkStands $stands,
        public string $refusal,
        public string $askedFor,
        public string $notAskable,
        public ?AnInvitationAsShown $invitation,
    ) {}
}
