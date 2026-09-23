<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Who resolved a contested capability: the stack, or the operator.
 *
 * Two cases, and the distinction between them is load-bearing enough to have
 * its own type rather than a boolean. A settlement the stack reached may not be
 * presented as one the operator made, and a `bool $byOperator` is the
 * shape that loses that at the first call site which reads it the wrong way
 * round.
 *
 * **Why an operator would care.** A choice the stack made is a default it
 * applied on their behalf and would apply again; a choice they made is a
 * decision of record, and standing in for a bundled service is the operator's
 * recorded choice rather than a manifest's assertion. Shown the
 * first as the second, an operator stops looking for the decision they never
 * made — and stops being able to find out why their stack does what it does.
 *
 * **Not {@see Whose}, which is the same word about a different question.** That
 * one says whose *session* this is, and decides which surface a person sees.
 * This says who made one past decision. They are never interchangeable: a
 * household member's session can read a capability the operator settled.
 */
enum WhoSettledIt: string
{
    /** The stack applied its own rule, without being asked. */
    case Stack = 'stack';

    /** Somebody decided it, and it is theirs. */
    case Operator = 'operator';
}
