<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * The check whose finding explains this one, where another does.
 *
 * The engine sets this after a run rather than during it: a check is
 * independent by construction and cannot see what any other found, so
 * attributing one failure to another is a judgement made once the whole run is
 * in. That judgement crossed the wire and this app dropped it.
 *
 * **What it is worth is the difference between five problems and one.** A VPN
 * that is down makes the tunnel check fail, and the client behind it, and the
 * trackers behind that. Shown flat, an operator reads five things wrong with
 * their machine and has no way to tell which one to fix. Shown with the cause
 * named, they read one broken thing and four services that noticed.
 *
 * Two arms rather than a nullable check, for `C2`'s reason and for a sharper
 * one here: a screen that forgets to ask whether there is a cause renders the
 * word "because" above nothing, which is worse than not saying it.
 */
final readonly class Because
{
    private function __construct(private ?Check $check) {}

    /** Nothing else in the run explains this one; it stands by itself. */
    public static function nothingElse(): self
    {
        return new self(null);
    }

    /** Another check's finding explains this one, and this is which. */
    public static function theCheck(Check $check): self
    {
        return new self($check);
    }

    /**
     * @template TAlone of object
     * @template TExplained of object
     *
     * @param  Closure(): TAlone  $alone
     * @param  Closure(Check): TExplained  $explained
     * @return TAlone|TExplained
     */
    public function either(Closure $alone, Closure $explained): object
    {
        // Read off the arm that carries something, as `WhatTheCheckSaid` does:
        // the arm with a cause is the one this type is written around, and a
        // fall-through is how a branch becomes the one nobody tested.
        return $this->check instanceof Check
            ? $explained($this->check)
            : $alone();
    }
}
