<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Lemonfiber\Sdk\Admission;
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

/**
 * The one place a credential is offered to a stack.
 *
 * `N1-R16` says every call goes through the SDK, and `N1-R7` says the exchange
 * happens once — so this is the only file in the application that names
 * {@see Admission}, the SDK's door. It sits beside {@see PinnedClients} and for
 * the same reason: the module boundary is what keeps *reach a stack another
 * way* from having a spelling anywhere else.
 *
 * **Pinned, with no unpinned spelling.** The SDK offers `at()` and `onPort()`,
 * and only the first is named here. `onPort()` is the loopback door, correct
 * for a surface running on the machine and wrong for every connection this app
 * makes — a phone is never on the machine. This is also the one request
 * carrying the operator's password, which makes it the last one that should
 * ever reach a peer whose identity nothing established.
 *
 * **Three refusals, because the operator meets three different things.** The
 * SDK goes out of its way to tell a wrong password from a door that has stopped
 * listening, and `N1-R10` asks for exactly that kind of distinction. Flattening
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
    public function admit(Stack $stack, Credential $said): Admitted
    {
        $door = Admission::at(
            $stack->at()->forTheClient(),
            $stack->presents()->forComparingByEye(),
        );

        try {
            $opened = $door->open($said->forTheExchange());
        } catch (PasswordWasRefused|TooManyAttempts|RequestFailed|UnreadableResponse $why) {
            return Admitted::refused($this->met($why));
        }

        return Admitted::opening(
            Session::of($opened->token),
            Instant::atEpochSeconds($opened->untilEpochSeconds),
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
     * which is `N1-R10`'s stack-did-not-answer rather than its refused
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
