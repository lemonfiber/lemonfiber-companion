<?php

declare(strict_types=1);

namespace Lemonfiber\Native\Player;

use SensitiveParameter;

/**
 * One channel's live stream as the player is handed it: the tuner's own stream, a transcoded playlist of it, the door both are pinned to, and its name.
 *
 * Every field is what the core stated. Either address may be empty, and the
 * native half plays the tuner's own where it can decode it and the
 * transcoded playlist otherwise; it reads each field again and refuses what
 * does not hold, so nothing in PHP is trusted to have checked it.
 *
 * **Both addresses are secrets.** Each carries the pass the door lets the
 * stream through on, so they are carried to the bridge and to nothing else:
 * marked so a stack trace leaves them out, and never part of what this type
 * says about itself.
 */
final readonly class ChannelAtTheDoor
{
    /**
     * @param string $direct      where the tuner's own stream is served, pass included, or empty.
     * @param string $transcoded  where a transcoded playlist of it is served, pass included, or empty.
     * @param string $fingerprint the door's certificate, as 64 hex characters.
     * @param string $title       what the lock screen and picture-in-picture call it.
     */
    public function __construct(
        #[SensitiveParameter]
        private string $direct,
        #[SensitiveParameter]
        private string $transcoded,
        private string $fingerprint,
        private string $title,
    ) {}

    /**
     * What this says about itself where something prints it: everything but the addresses.
     *
     * @return array<string, string>
     */
    public function __debugInfo(): array
    {
        return ['direct' => '(withheld)', 'transcoded' => '(withheld)', 'fingerprint' => $this->fingerprint, 'title' => $this->title];
    }

    /**
     * What the bridge is handed.
     *
     * @return array<string, string>
     */
    public function asCarried(): array
    {
        return [
            'direct' => $this->direct,
            'transcoded' => $this->transcoded,
            'fingerprint' => $this->fingerprint,
            'title' => $this->title,
        ];
    }
}
