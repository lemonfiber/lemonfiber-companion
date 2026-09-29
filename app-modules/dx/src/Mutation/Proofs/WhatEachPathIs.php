<?php

declare(strict_types=1);

namespace Dx\Mutation\Proofs;

use function array_fill_keys;
use function array_key_exists;
use function array_keys;
use function array_map;

use Dx\Mutation\Paths;
use Dx\Mutation\TheLayout;

use function preg_match;

/**
 * Every file in the repository, sorted by how a proof reads it.
 *
 * - A **judge** is a file of test cases the coverage map knows. It is in the
 *   proof of the units it judges.
 * - A **leaned-on** file only declares, and is either support under the test
 *   directories or tooling that decides what is mutated rather than how. It is
 *   in the proof of every unit whose files name it.
 * - A **named** file is read only where a string in the proof names it
 *   ({@see TheLayout::readWhereNamed()}).
 * - Everything else is **context**, in every proof: every file that is not a
 *   test, and every file under the test directories that runs something when
 *   it is loaded or that no coverage map knows.
 *
 * A path nothing here places is context. That is the direction every doubt
 * goes in: a file in a proof it did not need costs a shard run, and a file out
 * of a proof it needed would cost a verdict.
 */
final readonly class WhatEachPathIs
{
    /**
     * @param list<string>                $judges
     * @param list<string>                $leanedOn
     * @param array<string, list<string>> $named    by the pattern that reads them
     * @param list<string>                $context
     */
    private function __construct(
        public array $judges,
        public array $leanedOn,
        public array $named,
        public array $context,
    ) {}

    /**
     * @param list<string>                    $paths every file git tracks
     * @param array<string, WhatAPhpFileSays> $php   every PHP file among them, read
     * @param list<string>                    $tests the id of every test the coverage map holds
     */
    public static function of(array $paths, array $php, array $tests, TheLayout $layout): self
    {
        $known = array_fill_keys(array_map(Paths::classOfTest(...), $tests), true);
        $sorted = ['judges' => [], 'leanedOn' => [], 'named' => [], 'context' => []];

        foreach ($paths as $path) {
            $pattern = self::patternNaming($path, $layout);

            match (true) {
                $layout->isATestCase($path) && array_key_exists(Paths::classOfTestFile($path), $known) => $sorted['judges'][] = $path,
                self::onlyDeclares($path, $php, $layout) => $sorted['leanedOn'][] = $path,
                $pattern !== '' => $sorted['named'][$pattern][] = $path,
                default => $sorted['context'][] = $path,
            };
        }

        return new self($sorted['judges'], $sorted['leanedOn'], $sorted['named'], $sorted['context']);
    }

    /**
     * Whether a file is test support or tooling that only declares, and so acts
     * on nothing that does not name it.
     *
     * @param array<string, WhatAPhpFileSays> $php
     */
    private static function onlyDeclares(string $path, array $php, TheLayout $layout): bool
    {
        $inPlace = ($layout->isUnderTests($path) && ! $layout->isATestCase($path)) || $layout->decidesOnlyWhatIsMutated($path);

        return $inPlace && array_key_exists($path, $php) && $php[$path]->onlyDeclares;
    }

    private static function patternNaming(string $path, TheLayout $layout): string
    {
        foreach (array_keys($layout->readWhereNamed()) as $pattern) {
            if (preg_match($pattern, $path) === 1) {
                return $pattern;
            }
        }

        return '';
    }
}
