<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Whether a finding is a choice the operator may answer, and whether they have.
 *
 * The stack answers a warning and never a failure, and an answered warning comes back suppressed: still a warning, no longer leading.
 */
enum WhereAChoiceStands
{
    /** A pass, a failure, or a check that said nothing: nothing to answer. */
    case NotAChoice;

    /** A warning the operator has not answered. */
    case Unanswered;

    /** A warning the operator accepted, which still holds. */
    case Accepted;

    /** Where this finding stands as a choice. */
    public static function of(Finding $finding): self
    {
        if ($finding->conclusion() !== Conclusion::Warned) {
            return self::NotAChoice;
        }

        return $finding->said()->either(
            nothingWrong: static fn(): self => self::NotAChoice,
            wentWrong: static fn(Code $code, string $meaning, Remedies $remedies, Severity $severity, Standing $standing): self => $standing === Standing::Suppressed ? self::Accepted : self::Unanswered,
            couldNotSay: static fn(): self => self::NotAChoice,
        );
    }
}
