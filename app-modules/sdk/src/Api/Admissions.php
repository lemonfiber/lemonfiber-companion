<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Lemonfiber\Sdk\Exception\PasswordWasRefused;
use Lemonfiber\Sdk\Exception\RequestFailed;
use Lemonfiber\Sdk\Exception\TooManyAttempts;
use Lemonfiber\Sdk\Exception\UnreadableResponse;
use Modules\Kernel\Api\Admitted;
use Modules\Kernel\Api\Admitting;
use Modules\Kernel\Api\Credential;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Whose;

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
 * **Three refusals, because the operator meets three different things.** The
 * SDK goes out of its way to tell a wrong password from a door that has stopped
 * listening, and an obstacle is required to keep that kind of distinction. Flattening
 * them would have somebody typing carefully into a door that is not answering —
 * and each attempt extends the wait.
 *
 * | the SDK raises | the operator meets |
 * |---|---|
 * | {@see PasswordWasRefused} | `CredentialWasRefused` — offer another attempt |
 * | {@see TooManyAttempts} | `TooManyAttempts` — wait, and do not attempt |
 * | {@see RequestFailed}, {@see UnreadableResponse} | `StackDidNotAnswer` |
 *
 * **Nothing here reads a timestamp.** The SDK hands over the ending as a count
 * of seconds, which is what lets this file convert with a named constructor and
 * no date library — `B1` forbids one for a good reason, and the wire format is
 * the client library's business rather than this application's. That boundary
 * was moved deliberately: an app that parses the stack's stamps is an app that
 * has to be told when the stack changes how it writes one.
 *
 * **A `ConfigurationProblem` is deliberately not caught.** The SDK raises one
 * where a stack's stored address cannot be pinned, which means this app is
 * holding a stack it should never have written down — `Pairing::read()` refuses
 * that material at the front door. Catching it here would turn a fault in
 * retained state into an ordinary screen about an unreachable machine, which is
 * the one reading that stops anybody fixing it.
 */
final readonly class Admissions implements Admitting
{
    public function __construct(private Doors $doors) {}

    public function admit(Stack $stack, Credential $said): Admitted
    {
        $door = $this->doors->door($stack);

        try {
            $opened = $door->open($said->forTheExchange());
        } catch (PasswordWasRefused|TooManyAttempts|RequestFailed|UnreadableResponse $why) {
            return Admitted::refused($this->met($why));
        }

        return Admitted::opening(
            Session::of($opened->token),
            Instant::atEpochSeconds($opened->untilEpochSeconds),
            // Absent is the operator, which is the stack's own shape rather than a
            // reading of it: an admission with no member on it is one the stack
            // minted against its own password.
            $opened->member === null ? Whose::theOperator() : Whose::member($opened->member),
        );
    }

    /**
     * What the operator met, given what the door raised.
     *
     * Split from the exchange itself because the two are different questions —
     * whether the door opened, and what to say when it did not — and together
     * they left one method with four ways out (`H8`).
     *
     * A `match` rather than four `catch` blocks, so the mapping reads as one
     * table. `RequestFailed` and `UnreadableResponse` share an answer: both mean
     * the operator did not get in and nothing about their password is known,
     * which is *the stack did not answer* rather than *the credential was refused*
     * credential. The remedy on that screen — check the machine is on and on
     * this network — is the right one for either.
     */
    private function met(PasswordWasRefused|TooManyAttempts|RequestFailed|UnreadableResponse $why): Obstacle
    {
        return match (true) {
            $why instanceof PasswordWasRefused => Obstacle::CredentialWasRefused,
            $why instanceof TooManyAttempts => Obstacle::TooManyAttempts,
            default => Obstacle::StackDidNotAnswer,
        };
    }
}
