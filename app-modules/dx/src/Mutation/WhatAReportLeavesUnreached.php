<?php

declare(strict_types=1);

namespace Dx\Mutation;

use function simplexml_load_string;
use function sprintf;
use function str_starts_with;

/**
 * Every statement under a path that a clover report says nothing reached.
 *
 * A group is held to covering all of what it holds before anything is mutated
 * against it, because `--covered-only` skips a line the group does not reach
 * without saying so — and a line the suite covers and the group does not is a
 * mutant the gate would stop judging in silence. The coverage floor already
 * holds the suite to every statement, so a statement the group misses is one
 * the suite reaches and the group does not.
 */
final readonly class WhatAReportLeavesUnreached
{
    /**
     * Each unreached statement as `path:line`. A path with no file in the
     * report at all is answered as unreached as a whole, rather than as nothing
     * missed: a group that runs none of it covers none of it, and an empty list
     * would read as the opposite.
     *
     * @return list<string>
     */
    public static function of(string $clover, string $root, string $path): array
    {
        $report = $clover === '' ? false : simplexml_load_string($clover);
        $exactly = sprintf('%s/%s', $root, $path);
        $missed = [];
        $reached = false;

        foreach ($report === false ? [] : $report->xpath('//file') ?? [] as $file) {
            $name = (string) $file['name'];

            if ($name !== $exactly && ! str_starts_with($name, sprintf('%s/', $exactly))) {
                continue;
            }

            $reached = true;

            foreach ($file->xpath('line[@type="stmt"][@count="0"]') ?? [] as $line) {
                $missed[] = sprintf('%s:%s', Paths::relativeTo($root, $name), (string) $line['num']);
            }
        }

        return $reached ? $missed : [sprintf('%s, all of it', $path)];
    }
}
