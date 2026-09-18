<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * The operator had a session on this stack and no longer has a usable one.
 *
 * Three requirements meet here and each of them is a thing this type refuses to
 * let a screen get wrong.
 *
 * **`N1-R44` — report it where the operator is, offer a new session there, and
 * put them back.** The tempting shape is a boolean that sends the app to a
 * sign-in screen, and it is wrong in a way nobody notices until they are the
 * operator: the work in front of them disappears, and what they were halfway
 * through is gone. So this cannot be constructed without a {@see Whereabouts},
 * and {@see resumeAt()} answers on **both** arms. Returning somebody to where
 * they were is not something a screen remembers to do; it is the only thing
 * this type can be read for.
 *
 * **`N1-R46` — an ended session is not a refused credential.** The two arms
 * take different arguments, which is this codebase's way of making one
 * unreadable as the other: a refusal carries an {@see Obstacle} — a sentence
 * and a remedy, which is what `N1-R10` asks of every refusal — and an ended
 * session carries none, because there is nothing wrong to explain. "Your
 * session ended, sign in again" and "that credential was not accepted" are
 * different sentences to the person reading them: one says wait a moment, the
 * other says you have something to fix. A single `bool` collapses them, and the
 * collapse is silent.
 *
 * **`N1-R45` — the pairing survives.** A credential expiring is not the machine
 * changing, which `ADR-0018` is explicit about, and re-pairing on a session
 * ending would throw away a pinned fingerprint that is still correct — and then
 * ask the operator to accept a new one, which is a habit an attacker would like
 * them to have. This type names its stack with a {@see StackId} and touches
 * nothing else. There is no constructor here that accepts a {@see Pairing} or a
 * {@see Fingerprint}, no reader that produces one, and so no path through an
 * interruption that can discard what pairing established. `N1-R22` pins trust
 * to the stack rather than to where it answers, so naming the stack is enough
 * to come back to the same pinned certificate.
 */
final readonly class Interrupted
{
    private function __construct(
        private StackId $on,
        private Whereabouts $was,
        private ?Obstacle $refused,
    ) {}

    /**
     * The session ended on its own — expired, or revoked at the stack.
     *
     * No obstacle, and the absence is the point. Nothing went wrong that the
     * operator can act on, and a screen handed an error to show would show one:
     * a red message about a credential that was never at fault teaches somebody
     * to distrust a stack that is behaving correctly.
     */
    public static function theSessionEnded(StackId $on, Whereabouts $was): self
    {
        return new self($on, $was, null);
    }

    /**
     * The credential was presented and not accepted.
     *
     * Takes the obstacle because this one has something to say: `N1-R10` wants
     * a sentence and a remedy per refusal, and the remedy here is not the same
     * remedy as an expiry's. Whoever built the obstacle knows whether the
     * password was wrong or the account is gone; this type only refuses to let
     * that sentence go missing.
     */
    public static function theCredentialWasRefused(StackId $on, Whereabouts $was, Obstacle $because): self
    {
        return new self($on, $was, $because);
    }

    /**
     * Which stack this was about.
     *
     * On both arms, because `N1-R11` keeps each stack's session separate and an
     * interruption that cannot name its stack would sign the operator out of
     * all of them.
     */
    public function on(): StackId
    {
        return $this->on;
    }

    /**
     * Where to put the operator back, once they are admitted again.
     *
     * On both arms, because the requirement makes no distinction: a session
     * that was rejected and one that ended both end with the operator back on
     * the screen they were on. This is deliberately not reachable through
     * `either()` — a caller that has decided which sentence to show must not
     * also get to decide whether returning somebody to their work is part of
     * this case.
     */
    public function resumeAt(): Whereabouts
    {
        return $this->was;
    }

    /**
     * @template TEnded of object
     * @template TRefused of object
     *
     * @param  Closure(Whereabouts): TEnded  $ended
     * @param  Closure(Obstacle, Whereabouts): TRefused  $refused
     * @return TEnded|TRefused
     */
    public function either(Closure $ended, Closure $refused): object
    {
        // Read off the refusal, the way `Kept` does: the arm with something to
        // explain is the one the type is written around, and a fall-through is
        // how a branch becomes the one nobody tested.
        return $this->refused instanceof Obstacle
            ? $refused($this->refused, $this->was)
            : $ended($this->was);
    }
}
