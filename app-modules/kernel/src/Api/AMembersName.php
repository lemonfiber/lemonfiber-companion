<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * The name a member of the household signs in with, as they typed it.
 *
 * Not a secret, and never kept: it travels with the password once, to the
 * door that exchanges both for a session, and the session says whose it is.
 */
final readonly class AMembersName
{
    private function __construct(private string $name) {}

    /**
     * The one place a string becomes a member's name.
     *
     * Trimmed, because a space typed around a name is nobody's name; a space
     * inside one is the media server's business.
     */
    public static function of(string $name): self
    {
        $called = trim($name);

        if ($called === '') {
            throw NobodyWasNamed::atTheDoor();
        }

        return new self($called);
    }

    /** The name, for the exchange that offers it. */
    public function forTheExchange(): string
    {
        return $this->name;
    }
}
