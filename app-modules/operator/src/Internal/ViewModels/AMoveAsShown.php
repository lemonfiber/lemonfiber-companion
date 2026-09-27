<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * Where one way of moving in stands, and what it came to or would come to, flattened for a template.
 *
 * What did not come across is its own list, drawn before the stance and
 * before what did, so it is never a footnote under a success. What wants a
 * copy first is its own list too, drawn above the yes, so it is said before
 * the move is agreed to.
 */
final readonly class AMoveAsShown
{
    /**
     * @param string                 $stanceSaid     the catalogue key for where it stands, as the stack gave it
     * @param string                 $refusal        why the stack turned it away, in its words, or empty
     * @param string                 $leftBehindSaid the catalogue key heading what did not come across, or empty where this way carries nothing
     * @param list<ALineOfTheSurvey> $leftBehind     what did not come across, or would not, each with why
     * @param list<ALineOfTheSurvey> $lines          what it came to, or would come to
     * @param list<ALineOfTheSurvey> $copyFirst      what it copies before anything is opened, said before the yes
     * @param string                 $agreeSaid      the catalogue key for the yes, or empty where there is nothing to agree to
     */
    public function __construct(
        public string $stanceSaid,
        public string $refusal,
        public string $leftBehindSaid,
        public array $leftBehind,
        public array $lines,
        public array $copyFirst,
        public string $agreeSaid,
    ) {}
}
