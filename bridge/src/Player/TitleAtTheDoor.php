<?php

declare(strict_types=1);

namespace Lemonfiber\Native\Player;

use SensitiveParameter;

/**
 * One title as the player is handed it: where it streams from, the door it is pinned to, and the grant.
 *
 * Every field is what the core stated, passed on rather than decided here. The
 * native half reads it again and refuses what does not hold — an address that
 * is not `https`, a fingerprint that is not one, a grant written into the
 * address — so nothing in PHP is trusted to have checked it.
 *
 * **The grant is a secret.** It is carried to the bridge and to nothing else:
 * marked so a stack trace leaves it out, and never part of what this type says
 * about itself.
 */
final readonly class TitleAtTheDoor
{
    /**
     * No language: for sound, the stream's own track plays; for subtitles, none show.
     *
     * `TrackRule` on each platform never forces subtitles on, so no language
     * to read in is no subtitles.
     */
    public const string NO_LANGUAGE = '';

    /**
     * @param string $location    where the core said the title streams from.
     * @param string $fingerprint the door's certificate, as 64 hex characters.
     * @param string $grant       what lets this member through the door.
     * @param float  $startAt     where to start, in seconds.
     * @param string $title       what the lock screen and picture-in-picture call it.
     * @param string $audio       the language the member prefers to hear, or empty.
     * @param string $subtitle    the language they prefer to read, empty, or `off` for none.
     */
    public function __construct(
        private string $location,
        private string $fingerprint,
        #[SensitiveParameter]
        private string $grant,
        private float $startAt,
        private string $title,
        private string $audio = '',
        private string $subtitle = '',
    ) {}

    /**
     * What this says about itself where something prints it: everything but the grant.
     *
     * @return array<string, float|string>
     */
    public function __debugInfo(): array
    {
        return ['grant' => '(withheld)'] + $this->asCarried();
    }

    /**
     * What the bridge is handed.
     *
     * @return array<string, float|string>
     */
    public function asCarried(): array
    {
        return [
            'location' => $this->location,
            'fingerprint' => $this->fingerprint,
            'grant' => $this->grant,
            'start_at' => $this->startAt,
            'title' => $this->title,
            'audio' => $this->audio,
            'subtitle' => $this->subtitle,
        ];
    }
}
