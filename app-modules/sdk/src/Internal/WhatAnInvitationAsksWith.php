<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use Modules\Kernel\Api\AnInvitationAskedFor;
use Modules\Kernel\Api\WhatBecomesOfUnrated;
use Modules\Sdk\Api\Fields\InvitationField;
use Modules\Sdk\Api\Fields\UpdateField;
use Modules\Sdk\Api\WireField;

/**
 * The arguments an invitation is sent with, as the `invite` action reads them.
 *
 * Here rather than in the kernel for {@see WhatADecisionAsksWith}'s reason:
 * which fields an action expects is the wire's business. An age limit and a
 * word about unrated material are sent only where they were said, read
 * through the kernel's folds, so nothing is sent in the place of either.
 *
 * `confirm` is always sent, rather than left to its default, so the one field
 * deciding whether an account is made is written on every request that could
 * make one.
 */
final readonly class WhatAnInvitationAsksWith
{
    /** @param array<string, bool|int|list<string>|string> $said what the action is asked with */
    private function __construct(public array $said) {}

    /** The rehearsal: everything the invitation was asked with, and no yes. */
    public static function offering(AnInvitationAskedFor $asked): self
    {
        return self::asking($asked, agreed: false);
    }

    /** The same arguments with the yes, as the rehearsal the operator was shown was asked. */
    public static function agreeing(AnInvitationAskedFor $asked): self
    {
        return self::asking($asked, agreed: true);
    }

    /**
     * The one body both are built by.
     *
     * `agreed` is named at both call sites above, for {@see \Modules\Sdk\Api\Adjustments}'
     * reason: the two lines deciding whether somebody gets an account read as
     * `agreed: false` and `agreed: true`.
     */
    private static function asking(AnInvitationAskedFor $asked, bool $agreed): self
    {
        // Collected by hand rather than through `iterator_to_array`, for the
        // reason `C10` gives.
        $libraries = [];

        foreach ($asked->libraries() as $library) {
            $libraries[] = $library;
        }

        $said = [
            WireField::Name->value => $asked->name(),
            InvitationField::Libraries->value => $libraries,
            UpdateField::Confirm->value => $agreed,
        ];

        $said = $asked->age(
            upTo: static fn(int $age): self => new self([...$said, InvitationField::AgeLimit->value => $age]),
            none: static fn(): self => new self($said),
        )->said;

        return $asked->unrated(
            chosen: static fn(WhatBecomesOfUnrated $unrated): self => new self([...$said, InvitationField::Unrated->value => $unrated->asked()]),
            unsaid: static fn(): self => new self($said),
        );
    }
}
