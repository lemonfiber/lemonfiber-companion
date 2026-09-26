<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * A member of the household, by the name their account is held under.
 *
 * All a password is taken off by: the stack finds the account, and a name it
 * cannot find is its refusal to say, naming the name.
 */
final readonly class SomebodyInTheHousehold
{
    private function __construct(private string $name) {}

    /** The member by that name; a blank one is refused. */
    public static function called(string $name): self
    {
        if (trim($name) === '') {
            throw InvitationSaysNothing::about('name');
        }

        return new self($name);
    }

    /** The name their account is held under. */
    public function name(): string
    {
        return $this->name;
    }
}
