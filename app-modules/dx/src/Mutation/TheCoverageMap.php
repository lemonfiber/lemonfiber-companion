<?php

declare(strict_types=1);

namespace Dx\Mutation;

use function array_any;
use function array_fill_keys;
use function array_key_exists;
use function array_map;
use function count;

/**
 * Which tests ran which line, as the `tests` job's coverage run recorded it.
 *
 * The same map every mutation shard reads instead of running the suite again,
 * held here as plain paths and test ids: paths relative to the repository, and
 * each covered line with the id of every test that ran it.
 */
final readonly class TheCoverageMap
{
    /**
     * @param array<string, array<int, list<string>>> $lines every covered line of every file, with the tests that ran it
     * @param list<string>                            $tests the id of every test the run held, covering anything or not
     */
    public function __construct(public array $lines, public array $tests) {}

    /**
     * Every file at least one of these test files ran a line of, in the order
     * the map holds them.
     *
     * Pest names each test file's class after its path, so a file and the
     * tests in it are matched as the letters and digits both are spelt with.
     *
     * @param  list<string> $testFiles
     * @return list<string>
     */
    public function filesRunBy(array $testFiles): array
    {
        $wanted = array_fill_keys(array_map(Paths::classOfTestFile(...), $testFiles), true);
        $reached = [];

        foreach ($this->lines as $file => $lines) {
            if ($this->anyRunBy($lines, $wanted)) {
                $reached[] = $file;
            }
        }

        return $reached;
    }

    /** How many lines of a file some test ran. */
    public function coveredLinesOf(string $file): int
    {
        return array_key_exists($file, $this->lines) ? count($this->lines[$file]) : 0;
    }

    /**
     * @param array<int, list<string>> $lines
     * @param array<string, true>      $wanted
     */
    private function anyRunBy(array $lines, array $wanted): bool
    {
        foreach ($lines as $ids) {
            if (array_any($ids, static fn(string $id): bool => array_key_exists(Paths::classOfTest($id), $wanted))) {
                return true;
            }
        }

        return false;
    }
}
