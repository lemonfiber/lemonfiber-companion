<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_map;
use function implode;

use InvalidArgumentException;
use Modules\Kernel\Api\WhatLemonfiberAsksFor;
use Modules\Sdk\Api\Fields\OutboundField;

use function sprintf;

/**
 * The `outbound` envelope did not hold what the contract says it holds.
 *
 * {@see ProvenanceIsUnreadable}'s refusal, for what leaves a machine, and for
 * its reason: every one of these is a bug somewhere other than here, and the
 * message names the list, the row and the field. A developer reads it, so it
 * is `sprintf` and never translated (`L1`).
 *
 * **Refused rather than salvaged, and here the direction of error is a privacy
 * claim.** A connection dropped for being unreadable is a connection the
 * screen says this machine does not make — the one wrong answer this surface
 * can give that the person checking will believe.
 */
final class OutboundIsUnreadable extends InvalidArgumentException
{
    public static function missing(NamesAWireField $field): self
    {
        return new self(sprintf(
            'The outbound envelope has no `%s`, or it is not what the contract says it is. This answer did not come from a lemonfiber of a version this app can read.',
            $field->value,
        ));
    }

    public static function row(NamesAWireField $list, int $position): self
    {
        return new self(sprintf(
            'Entry %d of `%s` in the outbound envelope is not a connection. It is refused rather than dropped: a list one row short says this machine does not make a connection it does.',
            $position,
            $list->value,
        ));
    }

    public static function said(NamesAWireField $list, NamesAWireField $field, int $position): self
    {
        return new self(sprintf(
            'Entry %d of `%s` in the outbound envelope has no readable `%s`. An entry in a list of what leaves a machine that will not say it reads as complete to the person checking.',
            $position,
            $list->value,
            $field->value,
        ));
    }

    /**
     * One service could not say who put it on the stack.
     *
     * Refused rather than listed unattributed: a plugin's service read as the
     * stack's own is a connection an operator will not think to trace back to
     * the plugin that brought it.
     */
    public static function origin(int $position, OriginIsUnreadable $why): self
    {
        return new self(sprintf(
            'Entry %d of `%s` in the outbound envelope cannot say where its service came from. %s',
            $position,
            OutboundField::Theirs->value,
            $why->getMessage(),
        ), previous: $why);
    }

    public static function reach(string $said, int $position): self
    {
        // The accepted list comes from the enum, so a request added cannot
        // leave this message describing the old set.
        return new self(sprintf(
            'Entry %d of `ours` in the outbound envelope is `%s`, and this app reads %s. Drawing it under the nearest name would be a claim about where somebody\'s data goes.',
            $position,
            $said,
            implode(', ', array_map(
                static fn(WhatLemonfiberAsksFor $asks): string => sprintf('`%s`', $asks->value),
                WhatLemonfiberAsksFor::cases(),
            )),
        ));
    }
}
