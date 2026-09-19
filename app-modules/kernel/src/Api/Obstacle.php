<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * What stood between the app and a stack, told apart rather than summarised.
 *
 * The requirement and the reason: a stack that cannot be reached,
 * one that refuses the credential, and a device with no network are three
 * different things, each with its own remedy. They are also the three an
 * application is most tempted to collapse, because the code path that produces
 * them is one `try` and the screen that renders them is one empty state — and
 * the operator who is shown "cannot connect" for all three is told to check the
 * machine when their phone is in flight mode.
 *
 * A fourth is added, and why it is not one of those: where the
 * platform asks permission before an app may reach the local network, a refusal
 * is "a distinct condition from an unreachable stack", and the app must offer
 * the way to grant it. It looks exactly like a stack that is off — the request
 * does not leave the device and nothing comes back — and it is the one of the
 * four where the machine is fine, the network is fine, and the fix is two taps
 * away in a settings app.
 *
 * A closed set rather than a message, because the difference has to survive
 * every layer between the socket and the screen. A sentence can be reworded by
 * accident; a case cannot, and a `match` over it stops compiling the moment
 * another is added.
 *
 * `ADR-0018` adds the fifth, and it is the only one of them that may not be an
 * accident: a connection presenting a certificate that does not match the
 * fingerprint pairing material carried is refused rather than warned about, and
 * the operator is told that this is not the machine the app was introduced to.
 * It arrives looking like a stack that is simply there, which is what makes
 * collapsing it into the others dangerous rather than merely unhelpful.
 *
 * **Where each one happened** is what separates them, and it is worth naming
 * because it is what makes each remedy different. `DeviceHasNoNetwork` is
 * decided without leaving the device. `LocalNetworkIsNotPermitted` is decided
 * without leaving it either, but by the platform rather than by the radio.
 * `StackDidNotAnswer` means the device had somewhere to send the request and
 * nothing came back. `CredentialWasRefused` means the stack answered — it is
 * reachable, it is the right machine, and it said no. Only the last one tells
 * us the connection works.
 *
 * **The app is locked is still not here.** A launch lists it beside these, and
 * it belongs to the lock `N4` defines rather than to a reach: nothing was
 * attempted, so nothing stood in the way. A refused permission is the opposite
 * — an attempt that the platform stopped — which is why one of them is a case
 * here and the other is not.
 *
 * **No sentence here either.** What the operator reads is text, so it comes
 * from the translator against a key (L1); a sentence written in this file would
 * be English on a Dutch phone. The catalogue carries one summary and one
 * remedy per case under `connection.`, and `ObstacleTest` is what requires them
 * to exist and to differ — the guarantee actually asked for.
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
     * The platform will not let this app onto the local network.
     *
     * Nothing was sent, and the stack is very probably fine. What is required is
     * this to be told apart from a stack that is off precisely because the two
     * are indistinguishable from inside the request: both are silence.
     */
    case LocalNetworkIsNotPermitted = 'local_network_refused';

    /**
     * The request went out and nothing came back.
     *
     * The machine is off, asleep, on another network, or mid-update. Normal
     * enough that it is modelled rather than thrown (C1).
     */
    case StackDidNotAnswer = 'no_answer';

    /**
     * Something answered, and it is not the machine this app was paired with.
     *
     * The certificate does not match the fingerprint pairing material carried,
     * so the connection is refused rather than warned about (`ADR-0018`).
     * Critical rather than an error: every other case here is a machine that is
     * off, a network that is down, or a credential that expired, and this one
     * is the only one that may mean somebody else is answering.
     */
    case StackIsNotTheOnePaired = 'fingerprint_changed';

    /**
     * The stack answered, and refused the credential.
     *
     * The connection works. Pairing is what does not, which is why this is one
     * of the two the app can offer to fix rather than explain.
     */
    case CredentialWasRefused = 'credential_refused';

    /**
     * The stack answered, and this account may not ask for that.
     *
     * The connection works, the session is good, and the answer is *no*. It is
     * the one obstacle here that is not a fault: nothing is broken, nothing is
     * off, and the core refusing a control a member is not entitled to is the
     * system working rather than failing.
     *
     * **Told apart from a refused credential because it must not sign anybody
     * out.** Both are a stack answering *no* to a request that carried a
     * session, and collapsing them would end a member's session the first time
     * they reached something that was never theirs.
     *
     * **Told apart from a stack that did not answer because it is an answer.**
     * Rendering it as silence would have a member told the machine is down
     * while it is sitting there declining them, and the remedy offered would be
     * to wait for something that will never change on its own.
     *
     * What a screen shows for this is the core's own sentence where the refusal
     * carried one. This case is what a screen falls back to, and what it reads
     * to know that an empty list is a refusal rather than an absence — which is
     * the whole of what the app owes here. The core decides entitlement; the
     * app's only job is not to pass the refusal off as nothing being there.
     */
    case NotForThisAccount = 'not_for_this_account';

    /**
     * The stack has stopped answering password attempts for a while.
     *
     * Told apart from {@see self::CredentialWasRefused} because the remedy is
     * the opposite one: a refused credential is answered by offering another
     * attempt, and this is answered by waiting — and by *not* attempting again,
     * since another attempt is what extends the wait. An app that reported both
     * as "wrong password" would have the operator typing carefully into a door
     * that is not listening, and lengthening the wait each time.
     *
     * The rule is about three conditions and this is a fourth of the same kind:
     * a thing the operator meets, which needs its own sentence because its
     * remedy is its own.
     */
    case TooManyAttempts = 'too_many_attempts';

    /**
     * The key for what stood in the way.
     *
     * The value *is* the stem, so a case added here has a sentence by existing
     * and `EveryKeyTheAppNamesResolvesTest` is what catches one with no line.
     * Derived rather than spelled for the reason `L1` gives: a key written out
     * at a call site is a key that survives its case being renamed, and it goes
     * on resolving to a line about something else.
     *
     * Here rather than in the folds that render it, because two of them were
     * spelling this themselves and a third would have spelled it again. An
     * obstacle knows its own sentence; a screen that has to know it as well is
     * a screen that can disagree with another screen.
     */
    public function said(): string
    {
        return sprintf('connection.%s', $this->value);
    }

    /**
     * The key for what to do about it.
     *
     * Separate from {@see said()} because both are owed and they are
     * not the same sentence: what happened is a fact about the world, and what
     * to do about it is advice. The advice is what differs most between these —
     * a router and a cupboard are not the same errand — which is why a screen
     * showing one summary for all six would be useless even with six summaries.
     *
     * `_action` is the suffix every remedy in this catalogue carries, which is
     * why one stem serves both: {@see \Modules\Connection\Api\HowTheSignInWent}
     * spells its pair the same way, and a screen reading one reads the other.
     */
    public function remedy(): string
    {
        return sprintf('connection.%s_action', $this->value);
    }

    /**
     * Whether meeting this means the session this device holds is no longer one.
     *
     * An identity removed from the household results in a
     * signed-out app at the next refused call, and that the app must not go on
     * rendering what was already loaded. The five other obstacles say nothing
     * about the session — a phone off a network, a stack asleep, a certificate
     * that changed — and a screen that signed somebody out on any of them would
     * make a walk out of wifi look like being thrown out of the house.
     *
     * `CredentialWasRefused` is the one where the stack answered and said no.
     * Whatever this device is holding, it is not a session any more: the
     * identity was removed, the password was changed, or the stack was rebuilt.
     * Keeping it leaves an app that reports *this stack refused the pairing of
     * this app* on every screen, for ever, with a button that asks again and is
     * refused again.
     *
     * **The line is drawn once, here.** Five screens fold an obstacle into
     * something a template reads, and a screen deciding this for itself is how
     * two of them come to disagree about whether somebody is signed in — which
     * an operator meets as one screen offering a password and the next
     * pretending nothing happened.
     *
     * {@see TooManyAttempts} is deliberately not included. The stack is
     * refusing to *look* at the credential rather than refusing the credential,
     * and signing somebody out for waiting too long would throw away a session
     * that is still good — and then ask them to sign in, which is another
     * attempt, which is what lengthens the wait.
     */
    public function meansWeAreSignedOut(): bool
    {
        return $this === self::CredentialWasRefused;
    }

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
            self::LocalNetworkIsNotPermitted => 'COMPANION-LOCAL-NETWORK-REFUSED',
            self::StackDidNotAnswer => 'COMPANION-NO-ANSWER',
            self::StackIsNotTheOnePaired => 'COMPANION-CERTIFICATE-CHANGED',
            self::CredentialWasRefused => 'COMPANION-CREDENTIAL-REFUSED',
            self::NotForThisAccount => 'COMPANION-NOT-FOR-THIS-ACCOUNT',
            self::TooManyAttempts => 'COMPANION-TOO-MANY-ATTEMPTS',
        });
    }

    /**
     * How much it matters.
     *
     * Having no network is a `Warning` rather than an `Error` because nothing
     * is broken — the device is somewhere without a signal, which it will leave.
     * A door that has stopped listening is a `Warning` for the same reason and
     * it is the sharper case: nothing is wrong with the stack, the app or the
     * password, and the condition clears itself. Reporting it as an error would
     * have the operator looking for a fault that is not there.
     */
    public function severity(): Severity
    {
        return match ($this) {
            // Nothing is broken here either, and this is the sharper case:
            // the refusal is correct. A member who may not ask for a thing
            // is not looking at a fault, and an app that demanded attention
            // for it would be raising an alarm about the rules working.
            self::DeviceHasNoNetwork,
            self::TooManyAttempts,
            self::NotForThisAccount => Severity::Warning,
            self::LocalNetworkIsNotPermitted,
            self::StackDidNotAnswer,
            self::CredentialWasRefused => Severity::Error,
            self::StackIsNotTheOnePaired => Severity::Critical,
        };
    }

    /**
     * Whether the app can offer to do something about it.
     *
     * This is "each with its own remedy" at the level a capability
     * can decide it. Two of the three are `Guided`: turning on Wi-Fi and waking
     * a machine both happen somewhere this application cannot reach, and a
     * button that claims otherwise fails in front of somebody. A refused
     * credential is `Actionable` because pairing again is a thing the app does
     * — which is the remedy named for the same situation.
     */
    public function standing(): Standing
    {
        return match ($this) {
            self::DeviceHasNoNetwork,
            self::StackDidNotAnswer,
            // Waiting is the whole remedy, and it is not a thing the app can
            // offer to do: a button here would either do nothing or make the
            // wait longer, which is the one outcome worse than no button.
            self::TooManyAttempts,
            // Entitlement is the household operator's to give, somewhere
            // this application cannot reach. A button would either do
            // nothing or promise a member something the app cannot deliver.
            self::NotForThisAccount => Standing::Guided,
            self::LocalNetworkIsNotPermitted,
            self::CredentialWasRefused,
            self::StackIsNotTheOnePaired => Standing::Actionable,
        };
    }
}
