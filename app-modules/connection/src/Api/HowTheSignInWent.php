<?php

declare(strict_types=1);

namespace Modules\Connection\Api;

use Modules\Kernel\Api\Obstacle;
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
 * **All six of `Obstacle`'s cases are named, and three share an answer.** No
 * network, a refused local network and a changed fingerprint all describe
 * something that happened before any credential was offered; on this screen
 * they are what a stack that did not answer is — the operator did not get in,
 * nothing about their password is known, and *check the machine is on and on
 * this network* is the right remedy for each. They are written out rather than
 * swept into a `default` arm, because a default is what lets a seventh case
 * arrive and be answered by the one written last.
 */
enum HowTheSignInWent: string
{
    /** Nothing has been offered yet. The state a screen opens in. */
    case NotYet = 'not_yet';

    /** The door opened and the session is where the next launch will find it. */
    case SignedIn = 'signed_in';

    /** The stack said no to the password. Another attempt is the remedy. */
    case CredentialWasRefused = 'credential_was_refused';

    /** Too many wrong attempts; the door has stopped listening for a while. */
    case TooManyAttempts = 'too_many_attempts';

    /** Nothing came back, so nothing is known about the password. */
    case StackDidNotAnswer = 'stack_did_not_answer';

    /** There is nowhere on this device this app may keep a session. */
    case NoStoreOnThisDevice = 'no_store_on_this_device';

    /** There is a store and it would not open, which is often temporary. */
    case TheStoreWouldNotOpen = 'the_store_would_not_open';

    /**
     * Whether offering the same password again is worth doing.
     *
     * `N1-R10`'s distinction where it costs the operator most. A refused
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
     * The key for the sentence naming what happened.
     *
     * A key rather than the words, which is `A4` and `L1` together: an enum
     * reaching for a translator it never asked for is a class that has stopped
     * telling the truth about what it needs, and the template is where `__()`
     * belongs. The keys are spelled here and nowhere else, so the catalogue
     * parity check has one place to compare against.
     *
     * **Every case answers, including the two that are not refusals**, which is
     * what lets a template show one headline and one line of advice with no
     * branch at all. A method that answered for some states and not others
     * would put those branches back — and an arm no template ever reaches is an
     * arm no test can hold to being right.
     */
    public function said(): string
    {
        return match ($this) {
            self::NotYet => 'connection.sign_in_to',
            self::SignedIn => 'connection.signed_in',
            self::CredentialWasRefused => 'connection.password_was_refused',
            self::TooManyAttempts => 'connection.too_many_attempts',
            self::StackDidNotAnswer => 'connection.no_answer',
            self::NoStoreOnThisDevice => 'connection.no_store_for_a_session',
            self::TheStoreWouldNotOpen => 'connection.session_would_not_keep',
        };
    }

    /**
     * The key for what to do about it.
     *
     * Separate from {@see said()} because `N1-R10` asks for both and they are
     * not the same sentence: what happened is a fact about the world, and what
     * to do about it is advice — advice that differs sharply between a password
     * worth retyping and a door that is counting how often you try.
     */
    public function remedy(): string
    {
        return match ($this) {
            self::NotYet => 'connection.sign_in_action',
            self::SignedIn => 'connection.signed_in_action',
            self::CredentialWasRefused => 'connection.password_was_refused_action',
            self::TooManyAttempts => 'connection.too_many_attempts_action',
            self::StackDidNotAnswer => 'connection.no_answer_action',
            self::NoStoreOnThisDevice => 'connection.no_store_for_a_session_action',
            self::TheStoreWouldNotOpen => 'connection.session_would_not_keep_action',
        };
    }

    /**
     * Whether offering a password is worth putting in front of them at all.
     *
     * Two states say no, for opposite reasons. Somebody already signed in has
     * nothing to type; somebody the door has stopped listening to would be
     * typing into a control that cannot help and, on a stack that counts
     * attempts, makes the wait longer. That second one is {@see Obstacle}'s
     * `Guided` standing arriving on a screen: the remedy is a sentence, not a
     * button.
     *
     * A store that would not open keeps the field, because *unlock the phone
     * and try again* is advice somebody acts on and then comes straight back to
     * this screen. So does a device with nowhere to keep a session: setting a
     * screen lock is the remedy, and returning to a screen with no way to
     * proceed would be the app forgetting what they came to do.
     */
    public function mayTry(): bool
    {
        return $this !== self::SignedIn && $this !== self::TooManyAttempts;
    }

    /** What a surface shows for each thing the operator met at the door. */
    public static function met(Obstacle $why): self
    {
        return match ($why) {
            Obstacle::CredentialWasRefused => self::CredentialWasRefused,
            Obstacle::TooManyAttempts => self::TooManyAttempts,
            Obstacle::StackDidNotAnswer,
            Obstacle::DeviceHasNoNetwork,
            Obstacle::LocalNetworkIsNotPermitted,
            Obstacle::StackIsNotTheOnePaired => self::StackDidNotAnswer,
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
