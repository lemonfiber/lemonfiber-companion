<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_map;
use function implode;

use InvalidArgumentException;
use Modules\Kernel\Api\AnOriginIsUnnamed;
use Modules\Kernel\Api\WhoSetIt;

use function sprintf;

/**
 * An `origin` did not hold what the contract says one holds.
 *
 * One refusal for every envelope that attributes, because they all carry the
 * same four-armed table and the defects in it are the same wherever it is.
 * It is never shown on its own: each envelope's reader wraps it in its own
 * refusal, which says *which* setting, finding or service could not be
 * attributed, and keeps this one as the previous exception for what exactly
 * was wrong with the table.
 *
 * **Refused rather than defaulted, and the rule against guessing an origin is
 * the whole reason.** An origin that is missing, is not a table, or names a
 * word this app does not know is one this app cannot attribute — and the repair
 * that suggests itself, reading it as bundled, is precisely the one that rule
 * forbids. Reading it as *unknown* is no better: the stack has a reason when it
 * says unknown, and inventing one here would put this app's failure to read
 * behind the stack's own words.
 */
final class OriginIsUnreadable extends InvalidArgumentException
{
    public static function missing(NamesAWireField $field): self
    {
        return new self(sprintf(
            'It has no `%s`, or it is not what the contract says it is. This answer did not come from a lemonfiber of a version this app can read.',
            $field->value,
        ));
    }

    /**
     * The origin names a word this app does not know.
     *
     * Apart from {@see missing()} because it points somewhere else. Missing
     * means the answer did not come from a lemonfiber this app can read; this
     * means it came from a *newer* one, which added an arm and this app has not
     * been taught it. The word is quoted because it is the only thing that
     * finds the release that added it, and the words this app does read are
     * listed from the enum, so a case added cannot leave the message stale.
     */
    public static function nobodyHere(string $word): self
    {
        return new self(sprintf(
            'It attributes this to `%s`, and this app reads %s. This answer came from a lemonfiber newer than this app.',
            $word,
            implode(', ', array_map(
                static fn(WhoSetIt $who): string => sprintf('`%s`', $who->value),
                WhoSetIt::cases(),
            )),
        ));
    }

    /**
     * The origin names a plugin or a reason, and it is blank.
     *
     * The kernel's refusal, kept as the previous exception, because it is the
     * one that says which of the two was blank.
     */
    public static function namingNobody(AnOriginIsUnnamed $why): self
    {
        return new self(sprintf('It names nobody: %s.', $why->getMessage()), previous: $why);
    }
}
