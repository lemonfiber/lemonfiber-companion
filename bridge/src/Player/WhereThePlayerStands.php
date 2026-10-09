<?php

declare(strict_types=1);

namespace Lemonfiber\Native\Player;

/**
 * Where the player stands, as the device answered it.
 *
 * Read only by asking: the player's event carries nothing, and this is what
 * whoever heard it gets back. It holds no address, no grant and no fingerprint,
 * because the device's answer has nowhere to put them.
 */
final readonly class WhereThePlayerStands
{
    /**
     * @param list<OfferedTrack> $audio
     * @param list<OfferedTrack> $subtitles
     */
    public function __construct(
        public WherePlaybackStands $stands,
        public float $position,
        public float $duration,
        public array $audio,
        public array $subtitles,
        public ?string $chosenAudio,
        public ?string $chosenSubtitle,
        public ?WhyPlaybackStopped $why,
    ) {}
}
