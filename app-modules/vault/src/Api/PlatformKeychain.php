<?php

declare(strict_types=1);

namespace Modules\Vault\Api;

use Lemonfiber\Native\Keeps;
use Lemonfiber\Native\WhenAValueMayBeRead;
use Lemonfiber\Native\WhyNothingWasKept;

use function mb_strpos;
use function mb_substr;

use Modules\Kernel\Api\Kept;
use Modules\Kernel\Api\Resumed;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Whose;
use Modules\Kernel\Api\WhySessionCannotBeKept;

use function sprintf;

/**
 * The device's own secure store, holding one session per stack.
 *
 * The one place "the platform's secure storage" becomes a call. Every
 * alternative — preferences, an app-readable file, an unencrypted backup — is
 * absent from this class rather than guarded against, which is the only way to
 * be sure: a fallback written for the device that has no store is the line that
 * writes a token to a file.
 *
 * **A key per stack.** Each stack's session is kept separate, and one key
 * holding "the session" is how two stacks come to share one — the second
 * pairing overwrites the first, and the first stack starts answering with
 * somebody else's credential.
 *
 * **Sessions are kept at the narrowest accessibility there is.** A session
 * token is readable only while the device is unlocked, because nothing in this
 * application reads one in the background and the wider setting exists for
 * things that do. The bridge answers back which it actually gave, and the two
 * differ on Android — that answer is not read here because nothing this class
 * decides turns on it, and inventing a use for it would be worse than letting
 * the caller who needs it ask.
 */
final readonly class PlatformKeychain implements SecureStorage
{
    /** What a stored key is prefixed with, so nothing else in the store collides. */
    private const string UNDER = 'lemonfiber.session';

    public function __construct(private Keeps $store) {}

    public function isAvailable(): bool
    {
        // Asked of the store itself rather than worked out here. Whether a
        // device has somewhere a session may go is a fact about the store, and
        // the answer already exists where the store's words are read — an
        // adapter reconstructing it would be a second reading of the same three
        // outcomes, and the second reading is the one that drifts.
        return $this->store->canBeAsked();
    }

    public function keep(StackId $stack, Session $session, Whose $whose): Kept
    {
        return $this->store
            ->keep($this->keyFor($stack), $this->written($session, $whose), WhenAValueMayBeRead::WhileUnlocked)
            ->either(
                done: static fn(): Kept => Kept::safely(),
                refused: static fn(WhyNothingWasKept $why): Kept => Kept::refused(self::meaning($why)),
            );
    }

    public function resume(StackId $stack): Resumed
    {
        return $this->store->read($this->keyFor($stack))->either(
            // A store that answered with an empty string is a store that lost
            // the value rather than one holding a session, and `Session::of()`
            // refuses a blank — so it is read as no session rather than allowed
            // to raise on a launch screen.
            found: static fn(string $written): Resumed => $written === ''
                ? Resumed::notHeld()
                : self::read($written),
            nothing: static fn(): Resumed => Resumed::notHeld(),
            // A store that will not open is a store with no session in it, as
            // far as this question goes: the operator is asked for the password,
            // which is both the honest outcome and the only useful one. The two
            // refusals are told apart where a session is being *kept*, because
            // the remedies differ there; here there is one remedy.
            refused: static fn(): Resumed => Resumed::notHeld(),
        );
    }

    public function forget(StackId $stack): Kept
    {
        // The answer is deliberately not read. Forgetting a session that was
        // never kept is the ordinary case after a refusal, which leaves the app
        // holding a session it could not store — and the one thing that must
        // always work is getting rid of it.
        $this->store->forget($this->keyFor($stack));

        return Kept::safely();
    }

    /**
     * The one string a stack's session is kept as: the subject, a newline, the token.
     *
     * Built field by field rather than by encoding the {@see Session}: that type
     * refuses to be serialised and redacts itself for `json_encode`, both on purpose,
     * so the token reaches this line through the accessor that names where it goes and
     * through nothing else.
     *
     * One value rather than two keys, which is what {@see SecureStorage::keep()}
     * carries the reasoning for. A newline rather than a document, because the whole
     * of what is kept is two strings of which one must survive byte for byte — and the
     * subject cannot carry a newline, while a token that did would not be a token an
     * HTTP header could hold.
     *
     * The subject goes first and the split below takes the *first* newline, so
     * everything after it is the token whatever the token contains.
     */
    private function written(Session $session, Whose $whose): string
    {
        return sprintf("%s\n%s", $whose->forTheStore(), $session->forTheHeader());
    }

    /**
     * The session a stored value holds, and whose it is.
     *
     * Static where the one above is not, because the reader beside it is a closure the
     * store hands its answer to and that closure carries nothing of this object.
     *
     * **A value with no newline in it is a bare token and the operator's**, and that is
     * a fact rather than a lenient reading: before a member could sign in at all, every
     * session this app kept was written as the token alone and belonged to the operator.
     */
    private static function read(string $written): Resumed
    {
        $at = mb_strpos($written, "\n");

        if ($at === false) {
            return Resumed::with(Session::of($written), Whose::theOperator());
        }

        $token = mb_substr($written, $at + 1);

        // A store holding a subject and no token has lost the half that matters, and
        // `Session::of()` refuses a blank — so it is read as no session rather than
        // allowed to raise on a launch screen, which is what an empty value already is.
        if ($token === '') {
            return Resumed::notHeld();
        }

        return Resumed::with(Session::of($token), Whose::member(mb_substr($written, 0, $at)));
    }

    /** What one of the bridge's refusals means in the terms this application reasons in. */
    private static function meaning(WhyNothingWasKept $why): WhySessionCannotBeKept
    {
        return match ($why) {
            WhyNothingWasKept::NoStoreOnThisDevice => WhySessionCannotBeKept::DeviceHasNoSecureStorage,
            WhyNothingWasKept::StoreWouldNotOpen => WhySessionCannotBeKept::StoreWouldNotOpen,
        };
    }

    /** One key per stack, so two paired stacks never share a session. */
    private function keyFor(StackId $stack): string
    {
        return sprintf('%s.%s', self::UNDER, $stack->stored());
    }
}
