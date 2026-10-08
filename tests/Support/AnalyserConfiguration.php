<?php

declare(strict_types=1);

namespace Tests\Support;

use function array_diff;
use function array_map;
use function basename;
use function file_get_contents;
use function implode;
use function is_string;
use function preg_match_all;

use RuntimeException;

use function sort;
use function sprintf;

/**
 * The analyser's configuration: `phpstan.neon` and the files under `phpstan/` it includes.
 *
 * Read as text, as every rule reading the configuration does. Refused where a
 * file under `phpstan/` is not included, because a rule reading that file
 * would be reading settings the analyser never loads.
 */
final readonly class AnalyserConfiguration
{
    /** The file the analyser is started with. */
    private const string THE_ROOT = 'phpstan.neon';

    /** The directory the root includes this repository's own settings from. */
    private const string WHERE_IT_INCLUDES_FROM = 'phpstan';

    /**
     * The root, then each file it includes from `phpstan/`, in the order it includes them.
     *
     * @return list<string>
     */
    public static function files(): array
    {
        preg_match_all(
            sprintf('/^[ \t]+-[ \t]+(%s\/[^\s\/]+\.neon)[ \t]*$/m', self::WHERE_IT_INCLUDES_FROM),
            self::read(Tree::at(self::THE_ROOT)),
            $included,
        );

        $onDisk = array_map(
            static fn(string $file): string => sprintf('%s/%s', self::WHERE_IT_INCLUDES_FROM, basename($file)),
            Tree::filesUnder(Tree::at(self::WHERE_IT_INCLUDES_FROM), '.neon'),
        );
        $notIncluded = array_diff($onDisk, $included[1]);
        sort($notIncluded);

        if ($notIncluded !== []) {
            throw new RuntimeException(sprintf(
                '%s does not include %s, so the analyser never loads what a rule would read there.',
                self::THE_ROOT,
                implode(', ', $notIncluded),
            ));
        }

        return [Tree::at(self::THE_ROOT), ...array_map(Tree::at(...), $included[1])];
    }

    /** Every file of it, as one text. */
    public static function text(): string
    {
        $texts = [];

        foreach (self::files() as $file) {
            $texts[] = self::read($file);
        }

        return implode("\n", $texts);
    }

    private static function read(string $path): string
    {
        $contents = file_get_contents($path);

        if (! is_string($contents)) {
            throw new RuntimeException(sprintf('%s could not be read.', $path));
        }

        return $contents;
    }
}
