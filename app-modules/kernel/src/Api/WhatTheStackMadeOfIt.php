<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What a stack said about a change it was asked to consider or to make.
 *
 * The same two arms every reading here has — the stack answered, or something
 * stood in the way of asking — and the distinction matters more on this one
 * than on a listing. A read that fails shows an operator a stale screen; a
 * write whose answer never arrived leaves them not knowing whether their stack
 * changed.
 *
 * So the refused arm carries an {@see Obstacle} and nothing else: it says the
 * asking did not complete, and deliberately does not say the change did not
 * happen, because from here that is not known.
 */
final readonly class WhatTheStackMadeOfIt
{
    /**
     * One field holding one of two things, rather than two nullable fields.
     *
     * Two nullables can express three states this type does not have — both
     * set, and neither — and the analyser is right to ask what happens in
     * them. A union holds exactly the invariant: there is one answer, and it
     * is one of these. The folds elsewhere here keep a non-null default on the
     * quiet side instead, which works where the quiet side has an empty value
     * worth the name; a change that did not happen has no empty version of
     * itself.
     */
    private function __construct(private WhereTheChangeStands|Obstacle $answered) {}

    /**
     * The stack considered it, and this is where it stands.
     */
    public static function said(WhereTheChangeStands $stands): self
    {
        return new self($stands);
    }

    /**
     * The asking did not complete, and this is what stood in the way.
     */
    public static function refused(Obstacle $why): self
    {
        return new self($why);
    }

    /**
     * Say what happens either way, and get back what you built.
     *
     * @template TSaid of object
     * @template TRefused of object
     *
     * @param  Closure(WhereTheChangeStands): TSaid  $said
     * @param  Closure(Obstacle): TRefused  $refused
     * @return TSaid|TRefused
     */
    public function either(Closure $said, Closure $refused): object
    {
        // Read off the refusal, as the rest of these do.
        return $this->answered instanceof Obstacle
            ? $refused($this->answered)
            : $said($this->answered);
    }
}
