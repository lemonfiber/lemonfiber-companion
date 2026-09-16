<?php

declare(strict_types=1);

namespace Modules\Dx\Api;

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;

use function sprintf;
use function str_repeat;

/**
 * The machines this module stands in for, and what each one does.
 *
 * Three rather than one, and the reason is `N1-R57`'s word *operate*. A single
 * stand-in that answers everything makes every screen reachable and every
 * screen identical: the happy one. `N1-R10`'s screens — a stack that is not
 * answering, a session a machine will not accept — are the ones worth looking
 * at hardest, because they are the ones an operator meets on a bad evening, and
 * a build where they cannot be reached at all is a build where they are never
 * looked at.
 *
 * **The app's own navigation is the switch.** No mode, no second setting,
 * nothing to remember to turn off. A device standing in for a paired one holds
 * three machines, they are listed on the first screen as any three would be,
 * and tapping one is how somebody chooses which behaviour to look at. That is
 * also a situation a real device is in constantly — several stacks, one of them
 * down — so what is being looked at is the app doing its job rather than a
 * test harness with a dial on it.
 *
 * **Every address is unresolvable and no fingerprint is a certificate.** RFC
 * 2606 reserves `.invalid`, so were the stand-in client ever to miss a request
 * the failure would be a name that does not exist rather than a connection to
 * somebody's actual machine. `N1-R60` asks for exactly that: nothing here can
 * reach a real stack.
 */
enum AStandInStack: string
{
    /** The machine everything works against. */
    case Answering = 'answering';

    /**
     * A machine that is not answering.
     *
     * `503` rather than a refused connection, because the two arrive at the
     * same place — {@see \Modules\Sdk\Internal\WhatARefusalMeant} reads
     * anything that is not `401` as `StackDidNotAnswer` — and a status is a
     * thing a stand-in can produce exactly, where a dropped socket is a thing
     * it can only approximate.
     */
    case NotAnswering = 'not_answering';

    /**
     * A machine that will not accept the session it was handed.
     *
     * The one refusal worth telling apart, and the reason `WhatARefusalMeant`
     * exists: it is answered by signing in again, on a machine that is working
     * perfectly. Every screen behind it has a different remedy from the case
     * above, which is what makes having both here worth the third row.
     */
    case RefusingTheSession = 'refusing_the_session';

    /** What a stack answers with when it is working. */
    private const int IS_ANSWERING = 200;

    /** What a machine that cannot serve the request answers with. */
    private const int CANNOT_SERVE_IT = 503;

    /** What a machine answers with when the session it was handed is not one. */
    private const int WILL_NOT_TAKE_THE_SESSION = 401;

    /** Sixty-four hex characters, which is the written form `N1-R18` fixes. */
    private const int A_SHA256 = 32;

    /**
     * The stack as this device holds it.
     *
     * Assembled here rather than by the affordance that seeds them, so that
     * adding a fourth behaviour is a case in this file and nothing else — which
     * is the half of `Q-R72` about admitting one without editing anything
     * outside the module.
     */
    public function asAStack(): Stack
    {
        return Stack::of(
            StackId::of(Nonce::of(str_repeat($this->seed(), Nonce::SHORTEST))),
            StackName::of($this->called()),
            Address::of(sprintf('https://%s.invalid:8443', $this->value)),
            Fingerprint::of(str_repeat('ab', self::A_SHA256)),
        );
    }

    /**
     * How a stack of this identity behaves, and how an unknown one does.
     *
     * By identity rather than by address or name, because identity is what
     * `N1-R11` keeps stacks apart by — an address can be re-typed and a name is
     * whatever its owner felt like, and `N1-R22` says a machine that comes back
     * on another address is the same machine.
     *
     * **A machine this module has never heard of behaves as the working one,
     * and that is stated here rather than decided by the caller.** `C2` refuses
     * an `Api` method that answers with null, and it is right about this one
     * for a reason beyond the rule: *not one of mine* and *broken* are
     * different facts, and a caller handed `null` has to know which of them to
     * turn it into. A device holding a real pairing beside the stand-ins is a
     * state somebody can get into, and the conservative answer there is that
     * nothing pretends it is down.
     */
    public static function howAStackOfThisIdentityBehaves(StackId $id): self
    {
        foreach (self::cases() as $case) {
            if ($case->asAStack()->id()->is($id)) {
                return $case;
            }
        }

        return self::Answering;
    }

    /**
     * What every request to this machine answers with.
     *
     * One status for the whole machine rather than one per endpoint. A stack
     * that is down is down for everything, and a stand-in where `/api/status`
     * failed and `/api/checks` did not would be a machine no operator has ever
     * met — and a screen drawn from it proves nothing about a real evening.
     */
    public function answersWith(): int
    {
        return match ($this) {
            self::Answering => self::IS_ANSWERING,
            self::NotAnswering => self::CANNOT_SERVE_IT,
            self::RefusingTheSession => self::WILL_NOT_TAKE_THE_SESSION,
        };
    }

    /**
     * The name this machine is listed under.
     *
     * Written here rather than taken from the translator, and this is the one
     * place in the application where that is right: a stack's name is what its
     * owner typed, so it is data rather than the app's own words. `L1` reads
     * only the directories a screen is drawn from, and this is not one.
     *
     * Obvious on purpose, for {@see \Modules\Dx\Internal\WhatAStackWouldSay}'s
     * reason: anybody looking at the list should be able to tell in a second
     * that none of these is a machine of theirs.
     */
    public function called(): string
    {
        return match ($this) {
            self::Answering => 'Stand-in',
            self::NotAnswering => 'Stand-in, not answering',
            self::RefusingTheSession => 'Stand-in, session refused',
        };
    }

    /**
     * The character its identity is built from.
     *
     * A repeated character rather than anything resembling a real nonce, so
     * that an id in a log or a database is recognisable as this module's at a
     * glance — and distinct per case, because `N1-R11` keeps stacks apart by
     * identity and two sharing one would be one machine wearing two names.
     */
    private function seed(): string
    {
        return match ($this) {
            self::Answering => 's',
            self::NotAnswering => 'n',
            self::RefusingTheSession => 'r',
        };
    }
}
