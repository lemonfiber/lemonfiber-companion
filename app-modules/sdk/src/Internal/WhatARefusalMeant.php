<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use Closure;

use function is_string;

use Lemonfiber\Sdk\Exception\CertificateWasRefused;
use Lemonfiber\Sdk\Exception\RequestFailed;
use Lemonfiber\Sdk\Refusal;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\WhatTheRefusalNamed;

/**
 * What the operator met, given what the far end refused with.
 *
 * One place rather than one per adapter. Every reader in this module makes the
 * same call — a stack that refused, and which obstacle that is
 * — and two adapters deciding it independently is how one screen comes to say
 * *sign in again* where another says *the machine is not answering*, for the
 * same response. The operator meets both screens in one session.
 *
 * **Two refusals are worth telling apart, and they are the two where the stack
 * answered.** A session it will not accept is answered by signing in again, on
 * a machine that is working perfectly. A session it accepts, asking for
 * something this account may not have, is answered by saying so — and by
 * leaving the session alone, which is the part that matters: reading it as the
 * first would sign a member out the first time they reached something that was
 * never theirs.
 *
 * **A third refusal is this app's own, and it is not silence either.** A
 * peer that presents a certificate the pairing did not name answered, and it
 * is not the machine paired with. It is refused before anything is sent, and
 * it reaches the operator as the stack not being the one paired, with
 * re-pairing as the remedy.
 *
 * Everything else — a stack asleep, a network that dropped, an endpoint
 * answering five hundred — is the same sentence, and it is the one an obstacle
 * gives for a stack that is not answering. A stack that could not check an
 * account with its media server lands there too, which is the right place for
 * it: nothing about that is the account's doing, the door stays open, and what
 * it wants is another go.
 *
 * **Where the stack refused the work itself, it says why.** A problem
 * document at any status but `401` and `403` is the stack's answer about what
 * was asked — a copy it will not restore, work that stopped on a problem — and
 * {@see inItsWords()} carries it as {@see ARefusalInItsWords}. Every port whose
 * work the stack refuses in its words follows that one rule. A sentence with
 * no document around it may have come from anything standing in front of the
 * stack, and a document with no sentence says nothing, so both stay the
 * obstacle.
 *
 * `Internal` because which status means what is a detail of how this module
 * talks to a stack; the {@see Obstacle} it answers with is the shared word.
 */
final readonly class WhatARefusalMeant
{
    /**
     * The status a stack answers with when the session it was handed is not one.
     *
     * Named rather than written as `401` at the comparison, which is `D6`: a
     * bare number at a call site says nothing about which of the several
     * statuses this application distinguishes it is.
     */
    private const int SESSION_IS_NOT_ACCEPTED = 401;

    /**
     * The status a stack answers with when the session is one and may not ask.
     *
     * Named for the same reason the one above is (`D6`), and told apart from it
     * for a reason that reaches the operator: a session that is not accepted is
     * answered by signing in again, and a session that may not ask for a thing
     * is answered by not offering it — never by ending the session. An app that
     * read them as one would sign a member out the first time they reached
     * something that was never theirs.
     */
    private const int ACCOUNT_MAY_NOT_ASK = 403;

    /** The first status that is a refusal at all. */
    private const int A_REFUSAL = 400;

    /** The first status a stack answers a request it could not carry out with, rather than one it refused. */
    private const int THE_STACK_ITSELF_FAILED = 500;

    /**
     * The stack's own sentence for turning a request down, or nothing where the refusal is an obstacle.
     *
     * A refused session and an account that may not ask are obstacles with
     * remedies of their own. Anything else turned down in the asking or the
     * naming, with a sentence, is the stack's refusal, and that sentence is the
     * answer. A fault on the stack's side has no sentence to hand on.
     */
    public static function inItsOwnWords(RequestFailed $why): ?string
    {
        if (self::obstacle($why) !== Obstacle::StackDidNotAnswer) {
            return null;
        }

        return $why->status() >= self::A_REFUSAL && $why->status() < self::THE_STACK_ITSELF_FAILED
            ? $why->said()
            : null;
    }

    /**
     * The stack's refusal in its own words where it sent a problem document
     * with a sentence in it, and the obstacle everywhere else.
     *
     * @template TRefused of object
     * @template TMet of object
     *
     * @param Closure(ARefusalInItsWords): TRefused $refused
     * @param Closure(Obstacle): TMet               $met
     *
     * @return TRefused|TMet
     */
    public static function inItsWords(CertificateWasRefused|RequestFailed $why, Closure $refused, Closure $met): object
    {
        $obstacle = self::obstacle($why);
        $said = $why instanceof RequestFailed ? $why->said() : null;
        $problem = $why instanceof RequestFailed ? $why->refusal() : null;

        return $obstacle !== Obstacle::StackDidNotAnswer || $said === null || ! $problem instanceof Refusal
            ? $met($obstacle)
            : $refused(ARefusalInItsWords::said($said, $problem->meaning(), self::named($problem)));
    }

    public static function obstacle(CertificateWasRefused|RequestFailed $why): Obstacle
    {
        if ($why instanceof CertificateWasRefused) {
            return Obstacle::StackIsNotTheOnePaired;
        }

        return match ($why->status()) {
            self::SESSION_IS_NOT_ACCEPTED => Obstacle::CredentialWasRefused,
            self::ACCOUNT_MAY_NOT_ASK => Obstacle::NotForThisAccount,
            default => Obstacle::StackDidNotAnswer,
        };
    }

    /** What a problem document named in `detail`, or nothing where it named nothing. */
    private static function named(Refusal $problem): WhatTheRefusalNamed
    {
        $detail = $problem->detail();

        return is_string($detail) ? WhatTheRefusalNamed::as($detail) : WhatTheRefusalNamed::nothing();
    }
}
