<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use Lemonfiber\Sdk\Exception\CertificateWasRefused;
use Lemonfiber\Sdk\Exception\RequestFailed;
use Modules\Kernel\Api\Obstacle;

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
}
