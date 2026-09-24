<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * The one group a service belongs to in the stack's compose file.
 *
 * **Not a {@see Form}, and the two must not be mistaken for each other.** A
 * profile is a fact about one service — what it *is* — and every service has
 * exactly one. A form is a named combination of profiles, declared by the
 * stack as what an operator starts: `library` is a form whose one profile is
 * `media`, and `torrent` is a profile eight forms include. The two sets share
 * some spellings on the stack this product ships and are different sets all
 * the same, so a profile handed to a verb as a form is refused by the stack as
 * a form it has never heard of — or, where the spellings happen to meet, reaches
 * a different set of services from the one the row was about.
 *
 * So nothing here turns one into the other. A profile is shown on a service's
 * row and is never what a verb is asked for by.
 *
 * A value object over the wire's string for {@see Form}'s reason: the set is
 * the stack's, declared at runtime, and an enum would be a list of what this
 * build had heard of.
 */
final readonly class Profile
{
    private function __construct(private string $named) {}

    /** The profile, named as the stack names it. */
    public static function called(string $profile): self
    {
        $named = trim($profile);

        if ($named === '') {
            throw ProfileIsUnnamed::whereOneWasExpected();
        }

        return new self($named);
    }

    /** The name, for showing. */
    public function named(): string
    {
        return $this->named;
    }
}
