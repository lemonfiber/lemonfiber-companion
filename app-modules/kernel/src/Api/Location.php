<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function str_starts_with;

/**
 * Where something is served at the household's door, as the core stated it.
 *
 * Carried as the core wrote it and never put together here: the core builds
 * every location from the address the stack publishes for the media server,
 * so a location this app composed would be a second copy of how the library
 * is reached. The door serves over TLS only, so a location that is not
 * `https` is not one the core states.
 */
final readonly class Location
{
    /** The scheme every location at the door carries. */
    private const string AT_THE_DOOR = 'https://';

    private function __construct(private string $at) {}

    public static function of(string $at): self
    {
        if (! str_starts_with($at, self::AT_THE_DOOR)) {
            throw LocationIsUnfit::notAtTheDoor();
        }

        return new self($at);
    }

    /** The location as the player is handed it, and nowhere else. */
    public function forThePlayer(): string
    {
        return $this->at;
    }
}
