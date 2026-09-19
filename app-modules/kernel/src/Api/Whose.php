<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

use function trim;

/**
 * Who a session belongs to: the operator, or one member of the household.
 *
 * The application a person is given is decided by the identity that signed in, and
 * this is that identity. It is not a permission model — what a member may *do* stays
 * the core's answer and is read off what the core sent. This decides only which
 * surface somebody is shown and whose requests are theirs.
 *
 * **Absent is the operator, and the absence is the whole discriminator.** The stack
 * says so by leaving the member off an admission rather than by setting a second
 * field beside it, and this keeps that shape: there is one fact here, so there is
 * nothing for a second one to disagree with on the day either moves.
 *
 * **Not a secret.** A member's identifier is not a credential, a session or pairing
 * material, so none of the rules that make {@see Session} awkward to print apply. It
 * is held beside a session rather than inside one for the reason {@see Opening} gives
 * about an ending: a session is a secret with one destination, and a value a screen
 * has to read is not that.
 */
final readonly class Whose
{
    private function __construct(private ?string $member) {}

    /** Whoever holds this stack's own password. */
    public static function theOperator(): self
    {
        return new self(null);
    }

    /**
     * One member of the household, by the identifier the media server files them
     * under.
     *
     * The identifier and nothing else. What they are called, what they may watch and
     * what they may ask for are read from the household, which carries all of it per
     * member — a second copy here would be one able to disagree with that read.
     *
     * A blank identifier is nobody rather than a member, and is read as the operator:
     * a stack that answered with an empty member has said the same thing as one that
     * left it out, and inventing a member from it would sign somebody in as an
     * account that does not exist.
     */
    public static function member(string $id): self
    {
        return trim($id) === '' ? self::theOperator() : new self($id);
    }

    /**
     * Say what happens either way, and get back what you built.
     *
     * There is no `isTheOperator()` beside a `memberId()`. A check-then-get pair is a
     * pair somebody forgets, and the branch they forget here is the one that decides
     * which application a person is looking at — see {@see Resumed} for the same
     * reasoning about a session.
     *
     * @template TOperator of object
     * @template TMember of object
     *
     * @param Closure(): TOperator     $operator
     * @param Closure(string): TMember $member
     *
     * @return TOperator|TMember
     */
    public function either(Closure $operator, Closure $member): object
    {
        return $this->member === null ? $operator() : $member($this->member);
    }

    /**
     * The subject, as the one string a store keeps it as.
     *
     * Named for its destination the way {@see Session::forTheHeader()} is, and total
     * in both directions: every subject is one string and every string is one subject,
     * because a blank identifier already reads as the operator. So there is no branch
     * here for a caller to forget and no absence for one to mistake — which is what
     * lets a store write this without an accessor that hands back a member or nothing.
     *
     * The operator is the empty string. That is the same statement the stack makes by
     * leaving the member off an admission, said in the one shape a keychain holds.
     */
    public function forTheStore(): string
    {
        return $this->member ?? '';
    }

    /**
     * Whether this is the same person.
     *
     * Not a constant-time comparison, and deliberately: an identifier is not a
     * secret, so the timing of a comparison tells nobody anything they could not
     * read off a household listing.
     */
    public function is(self $other): bool
    {
        return $this->member === $other->member;
    }
}
