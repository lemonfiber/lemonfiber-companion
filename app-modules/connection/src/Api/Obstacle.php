<?php

declare(strict_types=1);

namespace Modules\Connection\Api;

use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Severity;
use Modules\Kernel\Api\Standing;

/**
 * What stood between the app and a stack, told apart rather than summarised.
 *
 * `N1-R10` is the requirement and the reason: a stack that cannot be reached,
 * one that refuses the credential, and a device with no network are three
 * different things, each with its own remedy. They are also the three an
 * application is most tempted to collapse, because the code path that produces
 * them is one `try` and the screen that renders them is one empty state — and
 * the operator who is shown "cannot connect" for all three is told to check the
 * machine when their phone is in flight mode.
 *
 * A closed set rather than a message, because the difference has to survive
 * every layer between the socket and the screen. A sentence can be reworded by
 * accident; a case cannot, and a `match` over it stops compiling when a fourth
 * appears.
 *
 * **Where each one happened** is what separates them, and it is worth naming
 * because it is what makes each remedy different. `DeviceHasNoNetwork` is
 * decided without leaving the device. `StackDidNotAnswer` means the device had
 * somewhere to send the request and nothing came back. `CredentialWasRefused`
 * means the stack answered — it is reachable, it is the right machine, and it
 * said no. Only the third one tells us the connection works.
 *
 * **The app is locked is not here.** `N1-R37` lists it beside these three, and
 * it belongs to the lock `N4` defines rather than to a reach: nothing was
 * attempted, so nothing stood in the way. Putting it here would make a fourth
 * case that every `match` has to answer for in a module that cannot see the
 * lock.
 *
 * **No sentence here either.** What the operator reads is text, so it comes
 * from the translator against a key (L1); a sentence written in this file would
 * be English on a Dutch phone. The catalogue carries one summary and one
 * remedy per case under `connection.`, and `ObstacleTest` is what requires them
 * to exist and to differ — the guarantee `N1-R10` actually asks for.
 */
enum Obstacle: string
{
    /**
     * The device has no network at all.
     *
     * Nothing was sent. This is the only one of the three that says nothing
     * about the stack, and reporting it as the stack being down sends somebody
     * to the wrong room.
     */
    case DeviceHasNoNetwork = 'no_network';

    /**
     * The request went out and nothing came back.
     *
     * The machine is off, asleep, on another network, or mid-update. Normal
     * enough that it is modelled rather than thrown (C1).
     */
    case StackDidNotAnswer = 'no_answer';

    /**
     * The stack answered, and refused the credential.
     *
     * The connection works. Pairing is what does not, which is why this is the
     * one case the app can offer to fix itself.
     */
    case CredentialWasRefused = 'credential_refused';

    /**
     * The identifier an operator can search for.
     *
     * The app's own, not the server's. `Code` says codes are declared beside
     * whatever raises them and never recycled — and the thing that raises these
     * is this application, on the far side of a server that did not answer.
     * The `COMPANION-` prefix is what keeps them from ever being read as a
     * stack's: a code with no prefix in a support thread is ambiguous the day
     * the server declares one that collides.
     */
    public function code(): Code
    {
        return Code::of(match ($this) {
            self::DeviceHasNoNetwork => 'COMPANION-NO-NETWORK',
            self::StackDidNotAnswer => 'COMPANION-NO-ANSWER',
            self::CredentialWasRefused => 'COMPANION-CREDENTIAL-REFUSED',
        });
    }

    /**
     * How much it matters.
     *
     * Having no network is a `Warning` rather than an `Error` because nothing
     * is broken — the device is somewhere without a signal, which it will leave.
     * The other two mean a thing that is supposed to work does not.
     */
    public function severity(): Severity
    {
        return match ($this) {
            self::DeviceHasNoNetwork => Severity::Warning,
            self::StackDidNotAnswer, self::CredentialWasRefused => Severity::Error,
        };
    }

    /**
     * Whether the app can offer to do something about it.
     *
     * This is `N1-R10`'s "each with its own remedy" at the level a capability
     * can decide it. Two of the three are `Guided`: turning on Wi-Fi and waking
     * a machine both happen somewhere this application cannot reach, and a
     * button that claims otherwise fails in front of somebody. A refused
     * credential is `Actionable` because pairing again is a thing the app does
     * — which is the remedy `N1-R20` names for the same situation.
     */
    public function standing(): Standing
    {
        return match ($this) {
            self::DeviceHasNoNetwork, self::StackDidNotAnswer => Standing::Guided,
            self::CredentialWasRefused => Standing::Actionable,
        };
    }
}
