<?php

declare(strict_types=1);

namespace Lemonfiber\Native\Player;

use function array_key_exists;
use function is_array;
use function is_string;

/** One sound or subtitle track the title offers, as the player names it. */
final readonly class OfferedTrack
{
    /**
     * @param string $id       what the player calls it, handed back to choose it.
     * @param string $language the language it is in, or empty.
     * @param string $label    what to show for it.
     */
    public function __construct(
        public string $id,
        public string $language,
        public string $label,
    ) {}

    /**
     * Every track in a list the bridge answered, leaving out anything that is not one.
     *
     * @param array<mixed>|null $said
     * @return list<self>
     */
    public static function listedIn(?array $said): array
    {
        $tracks = [];

        foreach ($said ?? [] as $one) {
            if (! is_array($one)) {
                continue;
            }

            $id = self::word($one, 'id');
            $language = self::word($one, 'language');
            $label = self::word($one, 'label');

            if ($id !== null && $language !== null && $label !== null) {
                $tracks[] = new self($id, $language, $label);
            }
        }

        return $tracks;
    }

    /**
     * The word under one key of a track, or nothing where there is no word there.
     *
     * @param array<mixed> $one
     */
    private static function word(array $one, string $key): ?string
    {
        return array_key_exists($key, $one) && is_string($one[$key]) ? $one[$key] : null;
    }
}
