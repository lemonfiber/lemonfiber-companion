<?php

declare(strict_types=1);

namespace Dx\Mutation\Proofs;

use function array_diff;
use function array_fill_keys;
use function array_filter;
use function array_intersect_key;
use function array_key_exists;
use function array_keys;
use function array_merge;
use function array_pop;
use function array_unique;
use function array_values;

/**
 * The files a set of files reaches by name, and the files their strings name.
 *
 * A test reaches its fakes and helpers by naming their classes, and those name
 * others in turn; a rule reaches `ARCHITECTURE.md` by writing its name in a
 * string. Both are followed as far as they go, and both over-read on purpose:
 * a word that happens to match a class name brings the class in.
 */
final readonly class WhatTheTestsLeanOn
{
    /**
     * @param array<string, WhatAPhpFileSays> $php    every PHP file, read
     * @param array<string, list<string>>     $byName every leaned-on file, by each name it declares
     * @param array<string, list<string>>     $named  the files read only where named, by the pattern that reads them
     * @param array<string, list<string>>     $words  the words that name each pattern's files
     */
    private function __construct(private array $php, private array $byName, private array $named, private array $words) {}

    /**
     * @param array<string, WhatAPhpFileSays> $php
     * @param list<string>                    $leanedOn
     * @param array<string, list<string>>     $named
     * @param array<string, list<string>>     $words
     */
    public static function of(array $php, array $leanedOn, array $named, array $words): self
    {
        $byName = [];

        foreach ($leanedOn as $path) {
            foreach ($php[$path]->declares as $name) {
                $byName[$name][] = $path;
            }
        }

        return new self($php, $byName, $named, $words);
    }

    /**
     * Every file these files reach: what they name, what that names, and every
     * file a string among all of them names.
     *
     * @param  list<string> $files
     * @return list<string>
     */
    public function from(array $files): array
    {
        $reached = [...$files, ...$this->byNameFrom($files)];

        return array_values(array_unique([...$reached, ...$this->namedIn($reached)]));
    }

    /**
     * The ones of these files whose strings mention one of these words.
     *
     * @param  list<string> $files
     * @param  list<string> $words
     * @return list<string>
     */
    public function mentioning(array $files, array $words): array
    {
        return array_values(array_filter($files, fn(string $file): bool => array_key_exists($file, $this->php) && $this->php[$file]->mentionsAnyOf($words)));
    }

    /**
     * @param  list<string> $files
     * @return list<string>
     */
    private function byNameFrom(array $files): array
    {
        $pending = $files;
        $seen = array_fill_keys($files, true);
        $found = [];

        while ($pending !== []) {
            $file = array_pop($pending);
            $names = array_key_exists($file, $this->php) ? $this->php[$file]->names : [];
            $new = array_values(array_diff(array_merge([], ...array_values(array_intersect_key($this->byName, $names))), array_keys($seen)));
            $seen += array_fill_keys($new, true);
            $pending = [...$pending, ...$new];
            $found = [...$found, ...$new];
        }

        return $found;
    }

    /**
     * @param  list<string> $files
     * @return list<string>
     */
    private function namedIn(array $files): array
    {
        $read = [];

        foreach ($this->named as $pattern => $paths) {
            if ($this->mentioning($files, array_key_exists($pattern, $this->words) ? $this->words[$pattern] : []) !== []) {
                $read = [...$read, ...$paths];
            }
        }

        return $read;
    }
}
