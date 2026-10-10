<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use Closure;

use function is_string;

use Lemonfiber\Sdk\Exception\Busy;
use Lemonfiber\Sdk\Exception\CertificateWasRefused;
use Lemonfiber\Sdk\Exception\Declined;
use Lemonfiber\Sdk\Exception\Failed;
use Lemonfiber\Sdk\Exception\Misasked;
use Lemonfiber\Sdk\Exception\Missing;
use Lemonfiber\Sdk\Exception\NotAdmitted;
use Lemonfiber\Sdk\Exception\RequestFailed;
use Lemonfiber\Sdk\Exception\TooManyAttempts;
use Lemonfiber\Sdk\Generated\RefusalCode;
use Lemonfiber\Sdk\Refusal;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\WhatTheHouseholdWasTold;
use Modules\Kernel\Api\WhatTheRefusalNamed;

use function trim;

/**
 * What the operator met, given what the far end refused with.
 *
 * One place rather than one per adapter. Every reader in this module makes the
 * same call — a stack that refused, and which obstacle that is
 * — and two adapters deciding it independently is how one screen comes to say
 * *sign in again* where another says *the machine is not answering*, for the
 * same response. The operator meets both screens in one session.
 *
 * **The stack says which refusal it is, and this reads what it said.** Four
 * refusals share one status — a session it no longer admits, an address it is
 * not listening on, an account asking for what is not its own, and an account
 * its media server could not vouch for — and each has its own remedy: sign in
 * again, pair again, leave it be, try again. The refusal's code is what tells
 * them apart, never its sentence, which is written for a person and may be
 * reworded.
 *
 * Only the first ends the session, whatever status any other code came with.
 * Reading any other as it would sign a member out the first time they reached
 * something that was never theirs, or on the day their media server restarted.
 *
 * **A refusal with no code is read by its status.** A stack from before codes,
 * or a code newer than this app knows, still says `401` for a session it will
 * not accept and `403` for everything else it refuses about who is asking, and
 * that is what this falls back to.
 *
 * **One more refusal is this app's own, and it is not silence either.** A
 * peer that presents a certificate the pairing did not name answered, and it
 * is not the machine paired with. It is refused before anything is sent, and
 * it reaches the operator as the stack not being the one paired, with
 * re-pairing as the remedy.
 *
 * Everything else — a stack asleep, a network that dropped, an endpoint
 * answering five hundred — is the same sentence, and it is the one an obstacle
 * gives for a stack that is not answering.
 *
 * **Where the stack refused the work itself, it says why.** A problem
 * document that is none of the refusals of who is asking is the stack's answer
 * about what was asked — a copy it will not restore, work that stopped on a
 * problem — and {@see inItsWords()} carries it as {@see ARefusalInItsWords}.
 * Every port whose work the stack refuses in its words follows that one rule. A sentence with
 * no document around it may have come from anything standing in front of the
 * stack, and a document with no sentence says nothing, so both stay the
 * obstacle.
 *
 * `Internal` because which status means what is a detail of how this module
 * talks to a stack; the {@see Obstacle} it answers with is the shared word.
 */
final readonly class WhatARefusalMeant
{
    /**
     * The status a stack turns away a session it will not accept with.
     *
     * Named rather than written as `401` at the comparison: a bare number at a
     * call site says nothing about which of the statuses a refusal is turned
     * away with it is. The other is an account that may not ask, which never
     * ends a session: of the readings it could have, it is the one that cannot
     * sign somebody out by mistake.
     */
    private const int SESSION_IS_NOT_ACCEPTED = 401;

    /** The first status that is a refusal at all. */
    private const int A_REFUSAL = 400;

    /** The first status a stack answers a request it could not carry out with, rather than one it refused. */
    private const int THE_STACK_ITSELF_FAILED = 500;

    /**
     * The stack's own sentence for turning a request down, or nothing where the refusal is an obstacle.
     *
     * A refused session and an account that may not ask are obstacles with
     * remedies of their own. Anything else turned down in the asking or the
     * naming, with a sentence, is the stack's refusal, and that sentence is the
     * answer. A fault on the stack's side has no sentence to hand on.
     */
    public static function inItsOwnWords(RequestFailed $why): ?string
    {
        if (! self::obstacle($why)->is(KindOfObstacle::StackDidNotAnswer)) {
            return null;
        }

        return $why->status() >= self::A_REFUSAL && $why->status() < self::THE_STACK_ITSELF_FAILED
            ? $why->said()
            : null;
    }

    /**
     * The obstacle a refusal is, carrying what the core wrote for the household where it sent a problem document.
     *
     * A member is told the core's sentence and remedy in place of this app's
     * lines, carried as text and never read. A refusal
     * with no document, and a certificate refused before anything was sent,
     * carry none, and are said in this app's words.
     */
    public static function obstacle(CertificateWasRefused|RequestFailed $why): Obstacle
    {
        $met = self::met($why);
        $problem = $why instanceof RequestFailed ? $why->refusal() : null;

        return $problem instanceof Refusal && trim($problem->summary()) !== ''
            ? $met->withWhatTheHouseholdWasTold(WhatTheHouseholdWasTold::said($problem->summary(), self::firstRemedy($problem)))
            : $met;
    }

    /**
     * The stack's refusal in its own words where it sent a problem document
     * with a sentence in it, and the obstacle everywhere else.
     *
     * @template TRefused of object
     * @template TMet of object
     *
     * @param Closure(ARefusalInItsWords): TRefused $refused
     * @param Closure(Obstacle): TMet               $met
     *
     * @return TRefused|TMet
     */
    public static function inItsWords(CertificateWasRefused|RequestFailed $why, Closure $refused, Closure $met): object
    {
        $obstacle = self::obstacle($why);
        $said = $why instanceof RequestFailed ? $why->said() : null;
        $problem = $why instanceof RequestFailed ? $why->refusal() : null;

        return $obstacle->kind() !== KindOfObstacle::StackDidNotAnswer || $said === null || ! $problem instanceof Refusal
            ? $met($obstacle)
            : $refused(ARefusalInItsWords::said($said, $problem->meaning(), self::named($problem)));
    }

    /**
     * A yes the stack turned down, read as moved where the refusal is `$moved`.
     *
     * A yes refused because what it was given for has moved is an answer to
     * offer again on, so it is read in the stack's words; every other refusal
     * is what was met.
     *
     * @template TMoved of object
     * @template TMet of object
     *
     * @param Closure(ARefusalInItsWords): TMoved $refused
     * @param Closure(Obstacle): TMet             $met
     *
     * @return TMoved|TMet
     */
    public static function whereItMoved(RefusalCode $moved, CertificateWasRefused|RequestFailed $why, Closure $refused, Closure $met): object
    {
        return $why instanceof RequestFailed && $why->code() === $moved
            ? self::inItsWords($why, $refused, $met)
            : $met(self::obstacle($why));
    }

    /** What the first remedy a problem document gives says to do, or nothing where it gives none. */
    private static function firstRemedy(Refusal $problem): string
    {
        foreach ($problem->remedies() as $remedy) {
            return $remedy->action();
        }

        return '';
    }

    /** Which obstacle a refusal is, by its code where it has one this app knows, and by its status everywhere else. */
    private static function met(CertificateWasRefused|RequestFailed $why): Obstacle
    {
        if ($why instanceof CertificateWasRefused) {
            return Obstacle::of(KindOfObstacle::StackIsNotTheOnePaired);
        }

        $code = $why->code();

        if (! $code instanceof RefusalCode) {
            return self::byStatus($why);
        }

        return match ($code) {
            RefusalCode::NotAdmitted => Obstacle::of(KindOfObstacle::CredentialWasRefused),
            RefusalCode::NotYours => Obstacle::of(KindOfObstacle::NotForThisAccount),
            RefusalCode::Unconfirmed,
            RefusalCode::ServerSilent => Obstacle::of(KindOfObstacle::MediaServerDidNotAnswer),
            RefusalCode::Elsewhere => Obstacle::of(KindOfObstacle::AddressIsNotTheStacks),
            RefusalCode::NotOpen => Obstacle::of(KindOfObstacle::InvitationNotOpen),
            RefusalCode::ShortChoice => Obstacle::of(KindOfObstacle::ChosenPasswordTooShort),
            // The door's own refusals, which `Admissions` reads where the
            // password is offered. One reaching here is about what was offered
            // at the door, and it ends no session.
            RefusalCode::NotThePassword,
            RefusalCode::TooManyAttempts,
            RefusalCode::NotAPassword,
            // A key's own refusals. This app sends a session and no key, so one
            // reaching it ends no session, and its words are the answer.
            RefusalCode::KeyInTheClear,
            RefusalCode::NotForAKey,
            // The stack refusing what was asked rather than who asked. Its own
            // words are the answer, and every code is written out so that one the
            // contract adds is decided here rather than swept in unread.
            RefusalCode::NoSuchAction,
            RefusalCode::MissingArgument,
            RefusalCode::UnrecognisedArgument,
            RefusalCode::UnwantedArgument,
            RefusalCode::ArgumentsTogether,
            RefusalCode::NotArguments,
            RefusalCode::NoSuchJob,
            RefusalCode::NotAnAnswer,
            RefusalCode::NoEndpoint,
            RefusalCode::WrongMethod,
            RefusalCode::NotAKeyRequest,
            RefusalCode::NotAnIdempotencyKey,
            RefusalCode::IdempotencyKeyReused,
            RefusalCode::Unwanted,
            RefusalCode::Repeated,
            RefusalCode::NoSuchRead,
            RefusalCode::NoTerm,
            RefusalCode::NotASeason,
            RefusalCode::NoSetting,
            RefusalCode::NoMember,
            RefusalCode::NoShelfWithoutAMember,
            RefusalCode::NotAnItem,
            RefusalCode::NotOnTheirShelf,
            RefusalCode::NotADevice,
            RefusalCode::NobodyNamed,
            RefusalCode::NoSuchPicture,
            RefusalCode::NotACount,
            RefusalCode::TooManyAtOnce,
            RefusalCode::NoSuchGroup,
            RefusalCode::NoSuchRemoval,
            RefusalCode::NoUpdateObject,
            RefusalCode::NotALineCount,
            RefusalCode::NotAChoice,
            RefusalCode::MemberAndDefaults,
            RefusalCode::Unrenderable,
            RefusalCode::NoJobName,
            RefusalCode::Unanswered,
            // An answer given for an offer or a listing that has since moved.
            // The stack's words say what moved, and the adapter following the
            // work it ended decides whether there is a fresh offer to read.
            RefusalCode::Stale,
            RefusalCode::MovedOn,
            RefusalCode::OfferMoved,
            RefusalCode::AnotherOffer,
            RefusalCode::PluginOfferMoved,
            RefusalCode::RestartMoved,
            RefusalCode::UpdateMoved,
            RefusalCode::PausingMoved,
            RefusalCode::AnotherReading => Obstacle::of(KindOfObstacle::StackDidNotAnswer),
            // The stack unable to read what it holds — its own description, or
            // the record of what is installed — and refusing a choice of what
            // fills a capability, which `Fillers` also reads by its code. Its
            // words say what stood in the way, and turned away it is an
            // account that may not ask: a known code ends no session.
            RefusalCode::StackUnreadable,
            RefusalCode::StackUnusable,
            RefusalCode::StackNotEmbedded,
            RefusalCode::StackNotSetUp,
            RefusalCode::StackNotWritten,
            RefusalCode::StackInvalid,
            RefusalCode::StackMalformed,
            RefusalCode::StackUnrecognised,
            RefusalCode::StackNeedsNewer,
            RefusalCode::StackUnassembled,
            RefusalCode::Unrecorded,
            RefusalCode::Unreadable,
            RefusalCode::Refused,
            RefusalCode::Already,
            RefusalCode::Nowhere,
            RefusalCode::Unwritable,
            RefusalCode::Unrecordable,
            RefusalCode::Unproved,
            RefusalCode::NothingToRemove,
            RefusalCode::NothingToUpdate,
            RefusalCode::Stuck,
            RefusalCode::Answered,
            RefusalCode::TwoSources,
            RefusalCode::SourceOff,
            RefusalCode::Unfetched,
            RefusalCode::NoRevision,
            RefusalCode::CatalogueOff,
            RefusalCode::CatalogueUnreachable,
            RefusalCode::SignatureUnverified,
            RefusalCode::CatalogueUnreadable,
            RefusalCode::NotCatalogued,
            RefusalCode::NotAsReviewed,
            RefusalCode::SpelledAlike,
            RefusalCode::Unapproved,
            RefusalCode::AnotherPlugin,
            RefusalCode::Occupied,
            RefusalCode::SchemeRefused,
            RefusalCode::AddressRefused,
            RefusalCode::HeaderNamed,
            RefusalCode::InputUnmatched,
            RefusalCode::CallRefused,
            RefusalCode::StepFailed,
            RefusalCode::PathNotPlain,
            RefusalCode::ValueWithheld,
            RefusalCode::CatalogueReplaced,
            RefusalCode::NewestUnkept,
            RefusalCode::NoSuchFiller,
            RefusalCode::CannotFill,
            RefusalCode::NothingAsks,
            RefusalCode::ChoiceUnwritable,
            RefusalCode::WiringMoved,
            RefusalCode::Unreasonable,
            RefusalCode::AlreadyFills,
            RefusalCode::NothingToPlayFrom,
            RefusalCode::NotInTheHousehold,
            RefusalCode::SignsNoDeviceIn => self::byFamily($why),
        };
    }

    /**
     * What a refusal with a code this app knows, other than the session's, means, read from its family.
     *
     * The code is known and it is not the one that says the session is not
     * admitted, so whatever status it came with, it signs nobody out: a request
     * turned away is an account that may not ask. Every other family is read as
     * a refusal with no code is.
     */
    private static function byFamily(RequestFailed $why): Obstacle
    {
        return $why instanceof NotAdmitted || $why instanceof Declined
            ? Obstacle::of(KindOfObstacle::NotForThisAccount)
            : self::byStatus($why);
    }

    /**
     * What a refusal carrying no code, or a code this app does not know, means, read from its status and family.
     *
     * Every family is named, and a test refuses one the SDK adds that is not,
     * so each is decided here rather than swept into an answer written for
     * another. A request turned away, for its credential or otherwise, is a
     * session not accepted where it was answered 401 and an account that may
     * not ask everywhere else; other work holding the stack is a remedy of its
     * own; and the rest are a stack that did not answer what was asked.
     */
    private static function byStatus(RequestFailed $why): Obstacle
    {
        return match (true) {
            $why instanceof NotAdmitted,
            $why instanceof Declined => Obstacle::of(
                $why->status() === self::SESSION_IS_NOT_ACCEPTED ? KindOfObstacle::CredentialWasRefused : KindOfObstacle::NotForThisAccount,
            ),
            $why instanceof Busy => Obstacle::of(KindOfObstacle::StackIsBusy),
            $why instanceof TooManyAttempts,
            $why instanceof Misasked,
            $why instanceof Missing,
            $why instanceof Failed => Obstacle::of(KindOfObstacle::StackDidNotAnswer),
            // The base is abstract and every family it has is named above, so
            // nothing reaches this; `WhatARefusalMeantTest` refuses a family
            // the SDK adds that is not named here.
            default => Obstacle::of(KindOfObstacle::StackDidNotAnswer),
        };
    }

    /** What a problem document named in `detail`, or nothing where it named nothing. */
    private static function named(Refusal $problem): WhatTheRefusalNamed
    {
        $detail = $problem->detail();

        return is_string($detail) ? WhatTheRefusalNamed::as($detail) : WhatTheRefusalNamed::nothing();
    }
}
