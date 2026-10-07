<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function in_array;

/**
 * Which thing stood between the app and a stack, told apart rather than summarised.
 *
 * The kinds. {@see Obstacle} is one met, which is a kind and, where its kind
 * carries any, the facts it was met with.
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
 * Two more are the stack answering about the request rather than about the
 * credential: a media server that could not vouch for the account, and an
 * address the stack does not say it listens on. Each has a remedy of its own —
 * another go, and pairing again — so neither is folded into a refused
 * credential or into an account that may not ask.
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
 * from the translator against a key; a sentence written in this file would be
 * English on a Dutch phone. The catalogue carries one summary and one remedy
 * per case under `connection.`, and `ObstacleTest` is what requires them to
 * exist and to differ — the guarantee actually asked for.
 */
enum KindOfObstacle: string
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
     * enough that it is modelled rather than thrown.
     */
    case StackDidNotAnswer = 'no_answer';

    /**
     * The machine's name turned into no address from this phone.
     *
     * Nothing was sent: there was nowhere to send it. The machine may be fine;
     * what failed is finding it by the name it was paired with.
     */
    case NameWasNotFound = 'name_not_found';

    /**
     * Nothing on the network answers at the address this phone was paired with.
     *
     * The shape a machine that moved to another address leaves behind, which
     * is why pairing again is its remedy rather than checking the machine:
     * the machine may be on and well somewhere else.
     */
    case NothingAtThePairedAddress = 'nothing_at_the_address';

    /**
     * Something at the address turned the connection away.
     *
     * The machine is there and lemonfiber is not answering on it, which sends
     * the operator to the machine rather than to the network.
     */
    case ConnectionWasTurnedAway = 'connection_refused';

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
     * The stack answered, and could not check this account with its media server.
     *
     * The connection works and the session may well be good; nobody was
     * identified, so nothing was answered. It is not the account being turned
     * away and not the session ending, and it must not be said as either: a
     * member told their account is gone on the day the media server restarts
     * has been told something false, and one signed out has lost a session that
     * another go would have used.
     */
    case MediaServerDidNotAnswer = 'media_server_unconfirmed';

    /**
     * The stack answered, and could not read who is in the household.
     *
     * The stack is there and said why its list of people is empty, which is
     * neither silence nor an empty household: its media server or its request
     * service did not say. Nothing was changed, and asking again later is the
     * whole remedy.
     */
    case HouseholdCouldNotBeRead = 'household_unread';

    /**
     * The stack answered, and the address this app reached it at is not one it
     * says it is listening on.
     *
     * The machine is there and it said no, so it is not silence; but no session
     * was refused either, and signing in again would be refused the same way.
     * What fixes it is the address this app holds, which only pairing again
     * from the stack itself can change.
     */
    case AddressIsNotTheStacks = 'address_not_the_stacks';

    /**
     * The stack answered in a version of the API this app does not read.
     *
     * Nothing was read from the answer: a version this app does not read is
     * refused whole rather than drawn in part. Met with the two versions, which
     * {@see Obstacle::versionsDisagree()} carries, so the sentence can name
     * both and the remedy can say which side to update.
     */
    case VersionsDisagree = 'version_mismatch';

    /**
     * The stack answered that other work holds it.
     *
     * Not a fault and not silence: the stack is there, working on something
     * else, and refused this rather than run it on top. Nothing was changed, and
     * asking again once that work is done is the whole remedy.
     */
    case StackIsBusy = 'busy';

    /**
     * The stack does not have this: the lemonfiber on it is older than what it
     * was asked for.
     *
     * Met before anything is sent. The stack declares what it can do, and a
     * request it does not declare is not attempted, so this is never a failure
     * of what the operator asked: it is the stack being too old to be asked.
     * The remedy names what would provide it, a newer lemonfiber on that
     * machine, and never a version number, because which release brought what
     * is the stack's to say and not a table this app keeps.
     */
    case NotOnThisStack = 'not_on_this_stack';

    /** The kinds met on the way to the stack's address, beside which the address tried is shown. */
    private const array MET_ON_THE_WAY_TO_THE_STACK = [
        self::StackDidNotAnswer,
        self::NameWasNotFound,
        self::NothingAtThePairedAddress,
        self::ConnectionWasTurnedAway,
    ];

    /**
     * The key for what stood in the way.
     *
     * The value *is* the stem, so a case added here has a sentence by existing
     * and `EveryKeyTheAppNamesResolvesTest` is what catches one with no line.
     * Derived rather than spelled: a key written out at a call site is a key
     * that survives its case being renamed, and it goes on resolving to a line
     * about something else.
     *
     * Here rather than in the folds that render it, because two of them were
     * spelling this themselves and a third would have spelled it again. An
     * obstacle knows its own sentence; a screen that has to know it as well is
     * a screen that can disagree with another screen.
     */
    public function said(): string
    {
        return InTheConnectionCatalogue::under($this->value)->said();
    }

    /**
     * The key for what to do about it.
     *
     * Separate from {@see said()} because both are owed and they are
     * not the same sentence: what happened is a fact about the world, and what
     * to do about it is advice. The advice is what differs most between these —
     * a router and a cupboard are not the same errand — which is why a screen
     * showing one summary for all of them would be useless even with one each.
     *
     * `_action` is the suffix every remedy in this catalogue carries, which is
     * why one stem serves both: {@see \Modules\Connection\Api\HowTheSignInWent}
     * spells its pair the same way, and a screen reading one reads the other.
     */
    public function remedy(): string
    {
        return InTheConnectionCatalogue::under($this->value)->remedy();
    }

    /**
     * The key for what stood in the way, on a member's screen.
     *
     * A member is told what an operator is, in the household's words, where
     * the obstacle was met on the way to the stack: those sentences name a
     * machine, an address and software, and a member has none of them to look
     * at. Everything else is the same sentence on both sides.
     */
    public function saidToTheHousehold(): string
    {
        return $this->isMetOnTheWayToTheStack() ? InTheConnectionCatalogue::forTheHouseholdUnder($this->value)->said() : $this->said();
    }

    /** The key for what a member can do about it, in the household's words where {@see saidToTheHousehold()} is. */
    public function remedyForTheHousehold(): string
    {
        return $this->isMetOnTheWayToTheStack() ? InTheConnectionCatalogue::forTheHouseholdUnder($this->value)->remedy() : $this->remedy();
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
     * Whether its remedy is a switch on this app's page in the phone's settings.
     *
     * Decided here, once, for the reason {@see meansWeAreSignedOut()} is: a
     * screen deciding which obstacles get a way to the settings would be a
     * screen that comes to disagree with the one beside it. Only a refused
     * local-network permission is put right there, and only a platform that
     * asks that permission produces one.
     */
    public function isPutRightInTheAppsSettings(): bool
    {
        return $this === self::LocalNetworkIsNotPermitted;
    }

    /**
     * Whether this was met on the way to the stack's address: no answer, a
     * name not found, nothing at the address, or a connection turned away.
     *
     * The ones where the address that was tried is what the operator needs
     * to see beside the sentence, since a stale or wrong address is what
     * several of them are.
     */
    public function isMetOnTheWayToTheStack(): bool
    {
        return in_array($this, self::MET_ON_THE_WAY_TO_THE_STACK, strict: true);
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
            self::NameWasNotFound => 'COMPANION-NAME-NOT-FOUND',
            self::NothingAtThePairedAddress => 'COMPANION-NOTHING-AT-THE-ADDRESS',
            self::ConnectionWasTurnedAway => 'COMPANION-CONNECTION-REFUSED',
            self::StackIsNotTheOnePaired => 'COMPANION-CERTIFICATE-CHANGED',
            self::CredentialWasRefused => 'COMPANION-CREDENTIAL-REFUSED',
            self::NotForThisAccount => 'COMPANION-NOT-FOR-THIS-ACCOUNT',
            self::TooManyAttempts => 'COMPANION-TOO-MANY-ATTEMPTS',
            self::MediaServerDidNotAnswer => 'COMPANION-MEDIA-SERVER-UNCONFIRMED',
            self::HouseholdCouldNotBeRead => 'COMPANION-HOUSEHOLD-UNREAD',
            self::AddressIsNotTheStacks => 'COMPANION-ADDRESS-NOT-THE-STACKS',
            self::VersionsDisagree => 'COMPANION-VERSIONS-DISAGREE',
            self::StackIsBusy => 'COMPANION-STACK-BUSY',
            self::NotOnThisStack => 'COMPANION-NOT-ON-THIS-STACK',
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
            self::NotForThisAccount,
            // A media server that is restarting clears itself, and nothing about
            // the account is wrong; an alarm here would send a member looking
            // for a fault in the one place there is none.
            self::MediaServerDidNotAnswer,
            self::HouseholdCouldNotBeRead,
            // Other work holding the stack is the stack working; the refusal
            // clears itself once that work is done.
            self::StackIsBusy,
            // Nothing is broken: the stack works, and is older than this.
            self::NotOnThisStack => Severity::Warning,
            self::LocalNetworkIsNotPermitted,
            self::StackDidNotAnswer,
            self::NameWasNotFound,
            self::NothingAtThePairedAddress,
            self::ConnectionWasTurnedAway,
            self::AddressIsNotTheStacks,
            self::VersionsDisagree,
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
            // Starting lemonfiber happens on the machine, where this app
            // cannot reach.
            self::ConnectionWasTurnedAway,
            // Waiting is the whole remedy, and it is not a thing the app can
            // offer to do: a button here would either do nothing or make the
            // wait longer, which is the one outcome worse than no button.
            self::TooManyAttempts,
            // Entitlement is the household operator's to give, somewhere
            // this application cannot reach. A button would either do
            // nothing or promise a member something the app cannot deliver.
            self::NotForThisAccount,
            // The media server is the household's, on a machine this app does not
            // reach; the remedy is waiting for it, not a button.
            self::MediaServerDidNotAnswer,
            self::HouseholdCouldNotBeRead,
            // Updating the app or the machine, and waiting for other work,
            // both happen where this app cannot act.
            self::VersionsDisagree,
            self::StackIsBusy => Standing::Guided,
            self::LocalNetworkIsNotPermitted,
            self::CredentialWasRefused,
            self::AddressIsNotTheStacks,
            // Pairing again gives the phone the address, or the name, the
            // machine answers at now, and pairing is a thing this app does.
            self::NameWasNotFound,
            self::NothingAtThePairedAddress,
            self::StackIsNotTheOnePaired,
            // Updating the machine is a thing the app offers, on its updates
            // screen, so the remedy is a road there.
            self::NotOnThisStack => Standing::Actionable,
        };
    }
}
