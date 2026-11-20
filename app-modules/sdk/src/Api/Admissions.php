<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Closure;
use Lemonfiber\Sdk\Admitted as Opened;
use Lemonfiber\Sdk\CodeIssued;
use Lemonfiber\Sdk\Exception\AnswerUnusable;
use Lemonfiber\Sdk\Exception\CertificateWasRefused;
use Lemonfiber\Sdk\Exception\PasswordWasRefused;
use Lemonfiber\Sdk\Exception\RequestFailed;
use Lemonfiber\Sdk\Exception\TooManyAttempts;
use Lemonfiber\Sdk\Exception\Unreachable;
use Lemonfiber\Sdk\Generated\RefusalCode;
use Lemonfiber\Sdk\WaitingCode;
use Modules\Kernel\Api\AClaim;
use Modules\Kernel\Api\ACodeToApprove;
use Modules\Kernel\Api\Admitted;
use Modules\Kernel\Api\Admitting;
use Modules\Kernel\Api\AdmittingByACode;
use Modules\Kernel\Api\AMembersName;
use Modules\Kernel\Api\AWaitingCode;
use Modules\Kernel\Api\CodeAsked;
use Modules\Kernel\Api\Credential;
use Modules\Kernel\Api\HowLong;
use Modules\Kernel\Api\HowTheCodeStands;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheDoorSaysNothing;
use Modules\Kernel\Api\WhatTheHouseholdWasTold;
use Modules\Kernel\Api\WhenToTryAgain;
use Modules\Kernel\Api\Whose;
use Modules\Sdk\Internal\WhatARefusalMeant;

/**
 * The one place a credential is offered to a stack.
 *
 * Every call to a stack goes through the SDK, and the credential exchange
 * happens once, and this is where that exchange is written. The door itself
 * comes from {@see PinnedDoors} — this file does not build one.
 *
 * **It used to.** `Admission::at(...)` sat in the middle of the method below,
 * which made two things impossible at once. The pinning rule could not read it: the
 * rule lists the client's transport and not the door's, so an unpinned door
 * carrying somebody's password would have raised nothing. And nothing could be
 * put in front of it: every screen of this application can be drawn against a
 * stand-in and the sign-in screen could not, because the one flow carrying the
 * password was the one flow that reached the network whatever the switch said.
 *
 * **Four refusals, because the operator meets four different things.** The
 * SDK goes out of its way to tell a wrong password from a door that has stopped
 * listening, and an obstacle is required to keep that kind of distinction. Flattening
 * them would have somebody typing carefully into a door that is not answering —
 * and each attempt extends the wait.
 *
 * | the SDK raises | the operator meets |
 * |---|---|
 * | {@see PasswordWasRefused} | `CredentialWasRefused` — offer another attempt |
 * | {@see TooManyAttempts} | `TooManyAttempts` — wait, and do not attempt |
 * | {@see CertificateWasRefused} | `StackIsNotTheOnePaired` — this is not the machine paired with; nothing was sent, the password included |
 * | {@see RequestFailed} carrying a code | what {@see WhatARefusalMeant} reads that code as, with the house's own words: a claim no longer open, a choice too short, a media server that could not confirm it |
 * | {@see RequestFailed}, {@see Unreachable}, {@see AnswerUnusable} | what {@see Clients::whatStoodInTheWay()} says stood in the way |
 *
 * **Nothing here reads a timestamp.** The SDK hands over the ending as a count
 * of seconds, which is what lets this file convert with a named constructor and
 * no date library — the wire format is the client library's business rather
 * than this application's. That boundary was moved deliberately: an app that
 * parses the stack's stamps is an app that has to be told when the stack
 * changes how it writes one.
 *
 * **A `ConfigurationProblem` is deliberately not caught.** The SDK raises one
 * where a stack's stored address cannot be pinned, which means this app is
 * holding a stack it should never have written down — `Pairing::read()` refuses
 * that material at the front door. Catching it here would turn a fault in
 * retained state into an ordinary screen about an unreachable machine, which is
 * the one reading that stops anybody fixing it.
 */
final readonly class Admissions implements Admitting, AdmittingByACode
{
    public function __construct(private Doors $doors, private Clients $clients) {}

    public function admit(Stack $stack, Credential $said): Admitted
    {
        return $this->exchanged($stack, null, $said, null);
    }

    public function admitAs(Stack $stack, AMembersName $named, Credential $said): Admitted
    {
        return $this->exchanged($stack, $named, $said, null);
    }

    public function claimAs(Stack $stack, AMembersName $named, Credential $chosen, AClaim $claim): Admitted
    {
        return $this->exchanged($stack, $named, $chosen, $claim);
    }

    public function askForACode(Stack $stack): CodeAsked
    {
        try {
            $issued = $this->doors->door($stack)->askForACode();
        } catch (TooManyAttempts $why) {
            return CodeAsked::refused(Obstacle::of(KindOfObstacle::TooManyAttempts), $this->waitOf($why));
        } catch (CertificateWasRefused|RequestFailed|Unreachable|AnswerUnusable $why) {
            $told = $this->saidOf($why, RefusalCode::SignsInByPassword);

            return $told instanceof WhatTheHouseholdWasTold ? CodeAsked::byPassword($told) : CodeAsked::refused($this->met($stack, $why), WhenToTryAgain::unsaid());
        }

        return $this->issued($issued);
    }

    public function finishByCode(Stack $stack, AWaitingCode $waiting): HowTheCodeStands
    {
        try {
            $finished = $this->doors->door($stack)->finishByCode(WaitingCode::of($waiting->forTheExchange()));
        } catch (TooManyAttempts $why) {
            return HowTheCodeStands::refused(Obstacle::of(KindOfObstacle::TooManyAttempts), $this->waitOf($why));
        } catch (CertificateWasRefused|RequestFailed|Unreachable|AnswerUnusable $why) {
            $told = $this->saidOf($why, RefusalCode::CodeNotWaiting);

            return $told instanceof WhatTheHouseholdWasTold ? HowTheCodeStands::lapsed($told) : HowTheCodeStands::refused($this->met($stack, $why), WhenToTryAgain::unsaid());
        }

        return $finished instanceof Opened
            ? $this->opened($finished, HowTheCodeStands::opened(...))
            : HowTheCodeStands::waiting(Instant::atEpochSeconds($finished->lapsesAtEpochSeconds));
    }

    /** What came of offering at this stack's door: the credential alone, with a member's name, or chosen with the claim an invitation carried. */
    private function exchanged(Stack $stack, ?AMembersName $named, Credential $said, ?AClaim $claim): Admitted
    {
        $door = $this->doors->door($stack);

        try {
            $opened = match (true) {
                $named instanceof AMembersName && $claim instanceof AClaim => $door->claimAs($named->forTheExchange(), $said->forTheExchange(), $claim->forTheExchange()),
                $named instanceof AMembersName => $door->openAs($named->forTheExchange(), $said->forTheExchange()),
                default => $door->open($said->forTheExchange()),
            };
        } catch (TooManyAttempts $why) {
            return Admitted::refusedFor(Obstacle::of(KindOfObstacle::TooManyAttempts), $this->waitOf($why));
        } catch (CertificateWasRefused|PasswordWasRefused|RequestFailed|Unreachable|AnswerUnusable $why) {
            return Admitted::refused($this->met($stack, $why));
        }

        return $this->opened($opened, Admitted::opening(...));
    }

    /**
     * The session the door opened, as the outcome the caller reads.
     *
     * @template T of object
     *
     * @param Closure(Session, Instant, Whose): T $as
     *
     * @return T
     */
    private function opened(Opened $opened, Closure $as): object
    {
        return $as(
            Session::of($opened->token),
            Instant::atEpochSeconds($opened->untilEpochSeconds),
            $opened->member === null ? Whose::theOperator() : Whose::member($opened->member),
        );
    }

    /** The code the door issued, or what stood in the way where it answered one this app cannot show. */
    private function issued(CodeIssued $issued): CodeAsked
    {
        try {
            return CodeAsked::issued(ACodeToApprove::issued(
                $issued->code,
                $issued->approveAt,
                Instant::atEpochSeconds($issued->lapsesAtEpochSeconds),
                AWaitingCode::answered($issued->waiting->reveal()),
            ));
        } catch (TheDoorSaysNothing) {
            return CodeAsked::refused(Obstacle::of(KindOfObstacle::AnswerCouldNotBeRead), WhenToTryAgain::unsaid());
        }
    }

    /** How long the door said to wait before asking again, where it said. */
    private function waitOf(TooManyAttempts $why): WhenToTryAgain
    {
        $seconds = $why->seconds();

        return $seconds === null || $seconds < 0 ? WhenToTryAgain::unsaid() : WhenToTryAgain::after(HowLong::ofSeconds($seconds));
    }

    /** What the house said, where it refused under this code, whether or not it said anything. */
    private function saidOf(CertificateWasRefused|RequestFailed|Unreachable|AnswerUnusable $why, RefusalCode $code): ?WhatTheHouseholdWasTold
    {
        return $why instanceof RequestFailed && $why->code() === $code ? WhatARefusalMeant::obstacle($why)->whatTheHouseholdWasTold() : null;
    }

    /**
     * What the operator met, given what the door raised.
     *
     * Split from the exchange itself because the two are different questions —
     * whether the door opened, and what to say when it did not — and together
     * they left one method with four ways out.
     *
     * A `match` rather than six `catch` blocks, so the mapping reads as one
     * table. `RequestFailed`, `Unreachable` and `AnswerUnusable` go to
     * one place: each means the operator did not get in and nothing about
     * their password is known, so none of them is *the credential was
     * refused*. {@see Clients::whatStoodInTheWay()} decides which obstacle it
     * is, the same way every other reach of a stack does.
     */
    private function met(Stack $stack, CertificateWasRefused|PasswordWasRefused|RequestFailed|Unreachable|AnswerUnusable $why): Obstacle
    {
        return match (true) {
            $why instanceof PasswordWasRefused => Obstacle::of(KindOfObstacle::CredentialWasRefused),
            $why instanceof CertificateWasRefused => Obstacle::of(KindOfObstacle::StackIsNotTheOnePaired),
            $why instanceof RequestFailed && $why->code() instanceof RefusalCode => WhatARefusalMeant::obstacle($why),
            default => $this->clients->whatStoodInTheWay($stack, $why),
        };
    }
}
