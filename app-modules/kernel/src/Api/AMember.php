<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * Somebody the media server holds an account for, by name, whether they have taken it up, and where they stand.
 *
 * Two constructors rather than a flag, for {@see AnInvitation}'s reason. An
 * account nobody has set a password on is an invitation still out, not a
 * member who is absent, and the difference is what decides whether they are
 * sent the address again or told their password was taken off.
 */
final readonly class AMember
{
    private function __construct(private string $name, private bool $joined, private WhereAMemberStands $stands) {}

    /** Somebody who has set a password, standing where the stack said; a blank name is refused. */
    public static function joined(string $name, WhereAMemberStands $stands = WhereAMemberStands::Active): self
    {
        return new self(self::named($name), joined: true, stands: $stands);
    }

    /** An account nobody has taken up, standing where the stack said; a blank name is refused. */
    public static function stillInvited(string $name, WhereAMemberStands $stands = WhereAMemberStands::Invited): self
    {
        return new self(self::named($name), joined: false, stands: $stands);
    }

    /** The name their account is held under. */
    public function name(): string
    {
        return $this->name;
    }

    /** Whether they have set a password on it. */
    public function hasJoined(): bool
    {
        return $this->joined;
    }

    /** Where they stand: invited, lapsed, declined, in the household, or switched off. */
    public function stands(): WhereAMemberStands
    {
        return $this->stands;
    }

    /** The name, refused where it is blank. */
    private static function named(string $name): string
    {
        if (trim($name) === '') {
            throw InvitationSaysNothing::about('name');
        }

        return $name;
    }
}
