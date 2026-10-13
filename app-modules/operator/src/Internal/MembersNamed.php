<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Kernel\Api\TheMembers;

/** The names of the household's members a key may be made for: those who have joined. */
final readonly class MembersNamed
{
    /** @param list<string> $names */
    private function __construct(public array $names) {}

    /** The members who have joined, in the stack's order. */
    public static function joined(TheMembers $members): self
    {
        $names = [];

        foreach ($members as $member) {
            if ($member->hasJoined()) {
                $names[] = $member->name();
            }
        }

        return new self($names);
    }

    /** None, where the household could not be read. */
    public static function none(): self
    {
        return new self([]);
    }
}
