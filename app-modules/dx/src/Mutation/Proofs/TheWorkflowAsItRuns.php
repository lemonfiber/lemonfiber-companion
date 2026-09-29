<?php

declare(strict_types=1);

namespace Dx\Mutation\Proofs;

use function array_filter;
use function array_map;
use function array_values;
use function explode;
use function implode;
use function ltrim;
use function mb_strlen;
use function preg_match;
use function preg_quote;
use function preg_replace;
use function sprintf;

/**
 * The part of the workflow a shard's verdict depends on, written the same way
 * whatever else in the file moved.
 *
 * That is the environment every job shares and the job that runs a shard. The
 * jobs that plan the shards, record the proofs and answer for the gate decide
 * what is mutated, not how, and a comment or the revision an action is pinned
 * at changes neither. Where the file has no job of that name, the whole file is
 * read, so a workflow reshaped past recognising is a workflow that changed.
 */
final readonly class TheWorkflowAsItRuns
{
    public static function of(string $workflow, string $job): string
    {
        $lines = array_values(array_filter(explode("\n", $workflow), static fn(string $line): bool => preg_match('/^\s*(#.*)?$/u', $line) !== 1));
        $shardJob = self::block($lines, sprintf('/^  %s:\s*$/u', preg_quote($job, '/')));
        $read = $shardJob === [] ? $lines : [...self::block($lines, '/^env:\s*$/u'), ...$shardJob];

        return implode("\n", array_map(
            static fn(string $line): string => preg_replace('/^(\s*(?:-\s+)?uses:\s*[^@\s]+)@[0-9a-f]{40}(\s+#.*)?$/u', '$1@pinned', $line) ?? $line,
            $read,
        ));
    }

    /**
     * The line that opens a block and every line indented deeper than it.
     *
     * @param  list<string> $lines
     * @return list<string>
     */
    private static function block(array $lines, string $opening): array
    {
        $block = [];
        $depth = null;

        foreach ($lines as $line) {
            $indent = mb_strlen($line) - mb_strlen(ltrim($line));

            if ($depth !== null && $indent <= $depth) {
                break;
            }

            if ($depth !== null || preg_match($opening, $line) === 1) {
                $block[] = $line;
                $depth ??= $indent;
            }
        }

        return $block;
    }
}
