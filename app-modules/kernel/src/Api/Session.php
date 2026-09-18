<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function hash_equals;

use JsonSerializable;

use function trim;

/**
 * What a stack admitted this app with, and the one place it may go.
 *
 * There is no `shown()`. The only way to read a session is `forTheHeader()`,
 * and the name is the rule: `N1-R8` says the session is carried in the
 * credential header the API defines and **must not** be placed in a URL or a
 * query parameter. A type with a general-purpose accessor makes that a thing to
 * remember; a type whose only accessor says where the value goes makes putting
 * it anywhere else a line somebody has to write on purpose, in front of a
 * reviewer.
 *
 * **It is deliberately awkward to print.** `N1-R15` says no credential, session
 * token or stack address may be logged, transmitted, or included in a
 * diagnostic report, and the way that rule is broken is never a decision — it
 * is a `var_dump` in a crash handler, or an object that fell into a JSON
 * payload. So `__debugInfo` redacts the first and `JsonSerializable` redacts
 * the second.
 *
 * `__debugInfo` is not the magic `P1` refuses. That rule is about methods which
 * take something out of the analyser's view — `__get`, `__call` and their
 * relatives, which make a property or a call invisible. This one changes
 * nothing about what the analyser can see and only narrows what a debugger
 * prints.
 *
 * **What this does not close, and cannot:** `var_export` reads private
 * properties directly and no method intercepts it. `var_dump` and `print_r` do
 * consult `__debugInfo()` and are covered — naming them here as well would send
 * the next reader to defend a door that is already shut, which is how a real
 * hole gets lost among three imaginary ones. That is worth writing down rather
 * than implying a guarantee this type does not give. What
 * actually closes it is the thing that assembles a diagnostic report refusing
 * to walk a `Session` at all, and there is no report assembler yet.
 * This narrows the surface; it does not seal it.
 */
final readonly class Session implements JsonSerializable
{
    private function __construct(private string $token) {}

    /**
     * What a debugger prints.
     *
     * @return array<string, string>
     */
    public function __debugInfo(): array
    {
        return ['token' => '(a session, hidden)'];
    }

    /**
     * What `serialize()` writes, which is nothing.
     *
     * `__debugInfo()` above answers the readers that are people. This answers
     * the one that is code, and the answer is a refusal — see
     * {@see MustNotLeaveThisProcess} for why it is not a redaction.
     *
     * @return array<string, never>
     */
    public function __serialize(): array
    {
        throw MustNotLeaveThisProcess::aSession();
    }

    /**
     * What `unserialize()` reads, which is nothing either.
     *
     * The other half of the same door. Without it a crafted payload naming this
     * class would be walked back into an object with whatever properties it
     * carried, which is a Session nobody constructed.
     *
     * @param array<string, never> $data
     */
    public function __unserialize(array $data): void
    {
        throw MustNotLeaveThisProcess::aSession();
    }

    /**
     * The one place a string becomes a session.
     *
     * Not trimmed on the way in beyond the blank check: whitespace inside a
     * token is the stack's business, and a client that quietly edits a
     * credential before sending it fails in a way the server cannot explain.
     */
    public static function of(string $token): self
    {
        if (trim($token) === '') {
            throw SessionIsBlank::fromTheStack();
        }

        return new self($token);
    }

    /**
     * The value for the credential header, and nothing else.
     *
     * Named for its destination rather than for its contents, so that reading
     * it for any other purpose reads wrong at the call site.
     */
    public function forTheHeader(): string
    {
        return $this->token;
    }

    /**
     * Whether this is the same session, compared in constant time.
     *
     * `hash_equals` rather than `===` because the comparison is against a
     * secret: a string comparison that stops at the first differing byte tells
     * anyone who can time it how much of a guess was right.
     */
    public function is(self $other): bool
    {
        return hash_equals($this->token, $other->token);
    }

    /** What `json_encode` writes. */
    public function jsonSerialize(): string
    {
        return '(a session, hidden)';
    }
}
