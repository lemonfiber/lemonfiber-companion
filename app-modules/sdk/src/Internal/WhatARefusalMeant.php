<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use Lemonfiber\Sdk\Exception\RequestFailed;
use Modules\Kernel\Api\Obstacle;

/**
 * What the operator met, given what the far end refused with.
 *
 * One place rather than one per adapter. Every reader in this module makes the
 * same call — a stack that refused, and which of `N1-R10`'s conditions that is
 * — and two adapters deciding it independently is how one screen comes to say
 * *sign in again* where another says *the machine is not answering*, for the
 * same response. The operator meets both screens in one session.
 *
 * **A session the stack will not accept is the one refusal worth telling
 * apart.** It is answered by signing in again, on a machine that is working
 * perfectly. Everything else — a stack asleep, a network that dropped, an
 * endpoint answering five hundred — is the same sentence, and it is the one
 * `N1-R10` gives for a stack that is not answering.
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

    public static function obstacle(RequestFailed $why): Obstacle
    {
        return $why->status() === self::SESSION_IS_NOT_ACCEPTED
            ? Obstacle::CredentialWasRefused
            : Obstacle::StackDidNotAnswer;
    }
}
