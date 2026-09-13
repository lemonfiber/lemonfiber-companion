<?php

declare(strict_types=1);

namespace Modules\Connection\Api;

use Modules\Kernel\Api\AtAGlance;

/**
 * The operator said the fingerprint on their phone is the one on the stack.
 *
 * `N1-R50` requires it for typed pairing and requires that the app not proceed
 * without it. A `bool` would satisfy the requirement in the reading that counts
 * calls and not in the one that counts mistakes: `confirmed: false` is a value
 * somebody can pass, and `$confirmed` is a variable somebody can leave `true`
 * from a branch above. A type that only exists where an operator answered yes
 * cannot be conjured by either.
 *
 * **It carries what was compared, not merely that something was.** `N1-R51`
 * asks for a short form derived from the whole fingerprint, and the whole
 * mechanism turns on the operator having compared *this* stack's — so the
 * confirmation names it, and {@see Introducing} can be handed a confirmation
 * that belongs to another machine and refuse it.
 *
 * There is no way to build one from a fingerprint alone, deliberately. The
 * argument a caller must supply is the thing the operator was shown, which is
 * the same value the screen rendered — so a call site that had no screen has
 * nothing to pass.
 */
final readonly class FingerprintWasConfirmed
{
    private function __construct(private AtAGlance $compared) {}

    /**
     * The operator compared this form and said it matched.
     *
     * Named for what happened rather than `of()`: a reader at the call site
     * should see a person's answer, because that is what it is and what it
     * costs to obtain.
     */
    public static function byTheOperator(AtAGlance $compared): self
    {
        return new self($compared);
    }

    /** Whether this is a confirmation of the certificate that stack presents. */
    public function covers(AtAGlance $presented): bool
    {
        return $this->compared->is($presented);
    }
}
