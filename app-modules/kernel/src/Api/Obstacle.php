<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What stood between the app and a stack, as it was met: its kind, and the facts it was met with.
 *
 * {@see KindOfObstacle} is the closed set, and why each kind is told apart from
 * the rest. This is one of them met, which is the kind and, where the kind
 * carries any, its facts. One kind carries facts so far: a version mismatch
 * names the version the stack answered in and the one this app reads, so the
 * sentence can say both and the remedy can say which side to update. Every
 * other kind is met with nothing beyond itself, through {@see self::of()}.
 *
 * Each fact-carrying kind has its own named constructor and its own typed
 * field, so a kind cannot be met without its facts and a fact cannot be given
 * to a kind that does not say it.
 */
final readonly class Obstacle
{
    /** The stem the remedy is under where the stack is the older of the two. */
    private const string FROM_AN_OLDER_STACK = 'version_mismatch_older';

    private function __construct(private KindOfObstacle $kind, private ?TheVersionsSpoken $versions) {}

    /** A kind met with nothing beyond itself; a kind that carries facts is refused here. */
    public static function of(KindOfObstacle $kind): self
    {
        if ($kind === KindOfObstacle::VersionsDisagree) {
            throw ObstacleIsNotOne::withoutItsFacts($kind);
        }

        return new self($kind, null);
    }

    /** The stack answered in a version of the API this app does not read. */
    public static function versionsDisagree(TheVersionsSpoken $versions): self
    {
        return new self(KindOfObstacle::VersionsDisagree, $versions);
    }

    /** Which kind it is. */
    public function kind(): KindOfObstacle
    {
        return $this->kind;
    }

    /** Whether it is of this kind. */
    public function is(KindOfObstacle $kind): bool
    {
        return $this->kind === $kind;
    }

    /** The key for what happened, filled with {@see self::filling()}. */
    public function said(): string
    {
        return $this->kind->said();
    }

    /**
     * The key for what to do about it, filled with {@see self::filling()}.
     *
     * The kind's own remedy, except where the versions disagree and the stack
     * is the older: then it is the machine that is updated, not this app.
     */
    public function remedy(): string
    {
        return $this->versions instanceof TheVersionsSpoken && ! $this->versions->isTheStackNewer()
            ? InTheConnectionCatalogue::under(self::FROM_AN_OLDER_STACK)->remedy()
            : $this->kind->remedy();
    }

    /**
     * What its sentences are filled with, by the names the catalogue gives them.
     *
     * The facts are held typed, above; this is their shape for the translator,
     * made where a sentence is drawn and nowhere else.
     *
     * @return array<string, int>
     */
    public function filling(): array
    {
        return $this->versions instanceof TheVersionsSpoken
            ? ['answered' => $this->versions->answered(), 'spoken' => $this->versions->spoken()]
            : [];
    }

    /** Whether meeting it means the session this device holds is no longer one; the kind decides. */
    public function meansWeAreSignedOut(): bool
    {
        return $this->kind->meansWeAreSignedOut();
    }

    /** The identifier an operator can search for. */
    public function code(): Code
    {
        return $this->kind->code();
    }

    /** How much it matters. */
    public function severity(): Severity
    {
        return $this->kind->severity();
    }

    /** Whether the app can offer to do something about it. */
    public function standing(): Standing
    {
        return $this->kind->standing();
    }
}
