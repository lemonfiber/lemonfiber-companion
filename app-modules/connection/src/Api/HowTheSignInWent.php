<?php

declare(strict_types=1);

namespace Modules\Connection\Api;

use Modules\Kernel\Api\InTheConnectionCatalogue;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Standing;
use Modules\Kernel\Api\WhySessionCannotBeKept;

/**
 * What became of a credential the operator offered to a stack.
 *
 * A screen's state after the tap, and an enum rather than a boolean for the
 * same reason {@see HowThePairingWent} is one: the interesting cases are in the
 * middle. A door that refused the password, a door that is not answering, and a
 * door that opened onto a session this phone could not keep are three different
 * situations with three different remedies, and a screen that knew only
 * *signed in or not* would have to say the same unhelpful thing about all of
 * them.
 *
 * **A session that could not be kept is not a sign-in.** It is the exact shape
 * `HowThePairingWent` guards against, one boundary further in: the stack opened
 * a session and this device has nowhere to put it, so the next launch will find
 * nothing and ask for the password again. Telling somebody they are signed in
 * and asking them again a minute later is worse than saying so now, while the
 * password they just typed is still in their head.
 *
 * **Mapped from the kernel's two enums rather than repeating them.**
 * {@see Obstacle} is what the world did and {@see WhySessionCannotBeKept} is
 * what this device did; both convert here through a `match` with no default
 * arm, so a case added to either fails by name rather than falling silently
 * into whichever was written last.
 *
 * **Every one of `Obstacle`'s cases is named, and some share an answer.** No
 * network, a stack that did not answer and an account-level refusal the door
 * cannot give all read as a stack that did not answer: the operator did not
 * get in, nothing about their password is known, and *check the machine is on
 * and on this network* is the right remedy for each. They are written out
 * rather than swept into a `default` arm, because a default is what lets a new
 * case arrive and be answered by the one written last.
 *
 * **A changed certificate is not one of them, nor an address the stack does
 * not answer on.** The machine answered, and either it is not the one this
 * device was introduced to or it was reached somewhere it is not listening.
 * Checking that it is switched on sends the operator to a machine that is
 * working; the remedy is to pair it again, which replaces what this device
 * holds for the stack — the pinned certificate, or the address.
 */
enum HowTheSignInWent: string
{
    /** Nothing has been offered yet. The state a screen opens in. */
    case NotYet = 'sign_in_to';

    /** The door opened and the session is where the next launch will find it. */
    case SignedIn = 'signed_in';

    /** The stack said no to the password. Another attempt is the remedy. */
    case CredentialWasRefused = 'password_was_refused';

    /** Too many wrong attempts; the door has stopped listening for a while. */
    case TooManyAttempts = 'too_many_attempts';

    /** Nothing came back, so nothing is known about the password. */
    case StackDidNotAnswer = 'no_answer';

    /** There is nowhere on this device this app may keep a session. */
    case NoStoreOnThisDevice = 'no_store_for_a_session';

    /** There is a store and it would not open, which is often temporary. */
    case TheStoreWouldNotOpen = 'session_would_not_keep';

    /**
     * This device will not let the app onto the network the stack is on.
     *
     * A refused local-network permission is reported as a
     * condition **distinct** from an unreachable stack. It used to arrive here
     * as `StackDidNotAnswer`, which is the collapse the requirement names: the
     * two are indistinguishable at the socket — both are a request that goes
     * nowhere — and telling them apart is the only thing standing between an
     * operator and an afternoon spent on a machine that is working perfectly.
     *
     * The remedy is different in kind, not only in wording. Every other
     * obstacle here sends somebody to look at their stack; this one sends them
     * to Settings on the phone in their hand.
     */
    case TheNetworkIsNotPermitted = 'local_network_refused';

    /**
     * Something answered with a certificate other than the one pinned for this stack.
     *
     * Refused rather than warned about, and never offered a password: a
     * password typed here would go to whatever is answering. The remedy is
     * pairing it again from the machine's own screen.
     */
    case TheMachineIsNotTheOnePaired = 'fingerprint_changed';

    /**
     * The stack answered, and the address this app reached it at is not one it
     * says it is listening on.
     *
     * Never offered a password: the door refused where the request came from,
     * not what it carried, and the same password would be refused the same way.
     * The remedy is pairing again from the stack itself, which gives this app the
     * address the stack answers on.
     */
    case TheAddressIsNotTheStacks = 'address_not_the_stacks';

    /**
     * Whether the operator is in, with a session that will survive the launch.
     *
     * Deleted once as unused and back with a caller: the screen shows the way
     * onwards only in this state, and *signed in* is not the complement of
     * {@see mayTry()} — a door that has stopped listening offers no field
     * either, and offering to show a report to somebody who never got in would
     * be the screen answering a question nobody asked.
     */
    public function isSignedIn(): bool
    {
        return $this === self::SignedIn;
    }

    /**
     * Whether offering the same password again is worth doing.
     *
     * The obstacle distinction where it costs the operator most. A refused
     * password is answered by typing it again; a door counting attempts is
     * made worse by it, and a stack that is not answering is not a password
     * question at all. This is what keeps a screen from putting *try again*
     * under all three.
     */
    public function isWorthAnotherAttempt(): bool
    {
        return $this === self::CredentialWasRefused;
    }

    /**
     * Whether pairing the machine again is the way on.
     *
     * The one state whose button is not on this screen: what gets somebody
     * past it is the pairing screen, where a new code replaces the certificate
     * pinned for the stack.
     */
    public function asksForAnotherPairing(): bool
    {
        return $this === self::TheMachineIsNotTheOnePaired || $this === self::TheAddressIsNotTheStacks;
    }

    /**
     * The key for the sentence naming what happened.
     *
     * A key rather than the words, which is `A4` and `L1` together: an enum
     * reaching for a translator it never asked for is a class that has stopped
     * telling the truth about what it needs, and the template is where `__()`
     * belongs.
     *
     * **Built from the case rather than listed against it**, which is
     * {@see Permission::reason()}'s shape and the reason is the same: a `match`
     * naming a key per case spells every stem twice — once as the case's value
     * and once as the string beside it — and two spellings of one name drift.
     * The value *is* the stem, so a case added here has a key by existing, and
     * `EveryKeyTheAppNamesResolvesTest` is what catches one with no line.
     *
     * **Every case answers, including the two that are not refusals**, which is
     * what lets a template show one headline and one line of advice with no
     * branch at all. A method that answered for some states and not others
     * would put those branches back — and an arm no template ever reaches is an
     * arm no test can hold to being right.
     */
    public function said(): string
    {
        return InTheConnectionCatalogue::under($this->value)->said();
    }

    /**
     * The key for what to do about it.
     *
     * Separate from {@see said()} because an obstacle owes both and they are
     * not the same sentence: what happened is a fact about the world, and what
     * to do about it is advice — advice that differs sharply between a password
     * worth retyping and a door that is counting how often you try.
     *
     * `_action` is the suffix every remedy in this catalogue already carries,
     * which is why the stem can serve both: {@see Obstacle} spells its pair the
     * same way, and a screen reading one reads the other.
     */
    public function remedy(): string
    {
        return InTheConnectionCatalogue::under($this->value)->remedy();
    }

    /**
     * Where this state stands with respect to being got past.
     *
     * {@see Standing}'s own words: *"the distinction that earns its keep is
     * `Actionable` against `Guided`: one puts a button on the screen and the
     * other puts instructions on it, and a screen that confuses them offers to
     * do something it cannot do."* That is this method's whole job, and the
     * judgement is the kernel's rather than this surface's — {@see met()} is
     * held to agreeing with {@see Obstacle::standing()} by a test, so the two
     * cannot drift into deciding the same thing differently.
     *
     * The two states that are not obstacles answer for themselves. Somebody
     * already signed in has nothing to do here, which is `Suppressed` — the
     * case for something not re-shown. Somebody who has typed nothing yet has a
     * password to type, which is `Actionable`.
     *
     * A store that would not open is `Actionable` too, and deliberately: unlock
     * the phone and try again is advice somebody acts on and then comes
     * straight back to this screen, and returning to one with no way to proceed
     * would be the app forgetting what they came to do.
     */
    public function standing(): Standing
    {
        return match ($this) {
            self::NotYet, self::CredentialWasRefused => Standing::Actionable,
            self::SignedIn => Standing::Suppressed,
            self::TooManyAttempts, self::StackDidNotAnswer => Standing::Guided,
            self::NoStoreOnThisDevice, self::TheStoreWouldNotOpen => Standing::Actionable,
            // `Guided` rather than `Actionable`, which is the distinction that
            // makes the distinction worth having: the operator must act, and not here.
            // Offering a password field over a network the app is not allowed
            // onto would be the screen offering to do something it cannot do —
            // and the remedy is one screen further away than any other state
            // here, in the phone's own settings rather than on the machine.
            self::TheNetworkIsNotPermitted => Standing::Guided,
            // `Actionable`, as the kernel judges the obstacle: there is a
            // button, and it leads to pairing rather than to a password field.
            self::TheMachineIsNotTheOnePaired, self::TheAddressIsNotTheStacks => Standing::Actionable,
        };
    }

    /**
     * Whether its remedy is a switch on this app's page in the phone's settings.
     *
     * The sign-in's own name for the obstacle that {@see KindOfObstacle::isPutRightInTheAppsSettings()}
     * answers for, so the screen offers the way there for the same one.
     */
    public function isPutRightInTheAppsSettings(): bool
    {
        return $this === self::TheNetworkIsNotPermitted;
    }

    /**
     * Whether offering a password is worth putting in front of them at all.
     *
     * Asked of the standing rather than decided here, which is the point: this
     * was a `match` of its own until it was noticed that {@see Obstacle} had
     * already made the same judgement — and had made it differently. A stack
     * that is not answering was being offered a password field, which is the
     * screen offering to do something it cannot do.
     *
     * A changed certificate offers a button too, and it is not this one:
     * a password typed toward a machine that is not the one paired goes to
     * whatever is answering.
     */
    public function mayTry(): bool
    {
        return $this->standing()->offersAButton() && ! $this->asksForAnotherPairing();
    }

    /**
     * Whether the operator can put this screen back to asking.
     *
     * The other half of `Guided`. Instructions are something somebody goes and
     * acts on — check the machine is on, wait for the door to start listening
     * again — and they come back to a screen still holding what it was told
     * then. Without this they would have to leave and navigate in again, which
     * is the app making them do its bookkeeping.
     *
     * Not offered where a password field is, because the field *is* the way
     * back to asking, and two controls doing one thing is one of them being
     * tapped by mistake.
     */
    public function mayStartOver(): bool
    {
        return $this->standing() === Standing::Guided;
    }

    /** What a surface shows for each thing the operator met at the door. */
    public static function met(Obstacle $why): self
    {
        return match ($why->kind()) {
            KindOfObstacle::CredentialWasRefused => self::CredentialWasRefused,
            KindOfObstacle::TooManyAttempts => self::TooManyAttempts,
            // Its own state rather than folded in with a stack that
            // did not answer. They look alike at the socket and are opposite
            // everywhere that matters: one is a machine to go and check, the
            // other is a switch on the phone the operator is holding.
            KindOfObstacle::LocalNetworkIsNotPermitted => self::TheNetworkIsNotPermitted,
            KindOfObstacle::StackIsNotTheOnePaired => self::TheMachineIsNotTheOnePaired,
            KindOfObstacle::AddressIsNotTheStacks => self::TheAddressIsNotTheStacks,
            KindOfObstacle::StackDidNotAnswer,
            KindOfObstacle::DeviceHasNoNetwork,
            // The door answers neither: it is asked before any version is
            // read past it and before any work can hold the stack, so each
            // reaching here is a door that did not answer in a way this app reads.
            KindOfObstacle::VersionsDisagree,
            KindOfObstacle::StackIsBusy,
            // Not a state of its own, because this door cannot answer with it.
            // Entitlement is decided on what an account asks for after it is
            // admitted, and `Admissions` reads every other refusal from the
            // door as a stack that did not answer. The day the door does refuse
            // an account by name is the day a sign-in state is owed for it —
            // this enum has none that would be true of it, and inventing one
            // now would be a screen nobody can reach.
            KindOfObstacle::NotForThisAccount,
            // The door is the one place that never asks the media server about an
            // account it has not yet admitted, so this cannot arrive here either;
            // the day it does, it is owed a state of its own rather than this one.
            KindOfObstacle::MediaServerDidNotAnswer,
            // The door reads no household, so this cannot arrive here either.
            KindOfObstacle::HouseholdCouldNotBeRead => self::StackDidNotAnswer,
        };
    }

    /** What a surface shows for each reason the session could not be kept. */
    public static function unkept(WhySessionCannotBeKept $why): self
    {
        return match ($why) {
            WhySessionCannotBeKept::DeviceHasNoSecureStorage => self::NoStoreOnThisDevice,
            WhySessionCannotBeKept::StoreWouldNotOpen => self::TheStoreWouldNotOpen,
        };
    }
}
