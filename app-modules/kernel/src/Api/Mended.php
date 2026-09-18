<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * One repair the operator agreed to, and what became of it.
 *
 * The repair travels with its outcome because neither is worth anything alone:
 * *fixed* says nothing without what was fixed, and a repair without its outcome
 * is the listing the operator was already shown. The rule is about the agreeing
 * being its own act, and this is what that act produces, one row at a time.
 *
 * **Read through one closure rather than three accessors**, which is
 * {@see Repair::stated()}'s shape and its argument: the outcome, what it left
 * and which repair it was are one fact, and three getters are three chances to
 * render two of them. The one that would be dropped is what was left behind,
 * because it is the only one that is often empty — and a half-moved library
 * nobody was told about is the worst outcome this type can describe.
 */
final readonly class Mended
{
    private function __construct(
        private Repair $repair,
        private WhatBecameOfIt $became,
        private LeftBehind $left,
    ) {}

    /**
     * A repair that stopped part-way, and what it left.
     *
     * Its own constructor because it is the only outcome that can leave
     * anything, and the only one where a caller has something to supply. The
     * others cannot be given one by accident, which is what keeps *declined*
     * from ever carrying a description of debris that does not exist.
     */
    public static function stopped(Repair $repair, LeftBehind $left): self
    {
        return new self($repair, WhatBecameOfIt::Stopped, $left);
    }

    /**
     * Any other outcome, which leaves nothing by construction.
     *
     * `Stopped` is refused here rather than quietly given nothing to have left:
     * a stopped repair that reports leaving nothing is a real and different
     * answer from one nobody asked about, and folding them would lose the
     * difference in the one place it matters.
     */
    public static function went(Repair $repair, WhatBecameOfIt $became): self
    {
        if ($became === WhatBecameOfIt::Stopped) {
            throw RepairSaysNothing::whetherItLeftAnything();
        }

        return new self($repair, $became, LeftBehind::nothing());
    }

    /**
     * Whether this one changed anything on the machine.
     *
     * Published on its own where the other three are not, and the asymmetry is
     * {@see Repair::answers()}'s: this is not something the operator reads. It
     * is what a count is made from, and a caller holding it has learned nothing
     * about what happened — only that something did.
     */
    public function changedSomething(): bool
    {
        return $this->became->changedSomething();
    }

    /**
     * Say all three, and get back whatever saying them produced.
     *
     * @template TSaid of object
     *
     * @param Closure(Repair, WhatBecameOfIt, LeftBehind): TSaid $say
     *
     * @return TSaid
     */
    public function said(Closure $say): object
    {
        return $say($this->repair, $this->became, $this->left);
    }
}
