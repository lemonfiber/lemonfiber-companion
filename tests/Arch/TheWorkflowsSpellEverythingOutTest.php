<?php

declare(strict_types=1);

use Tests\Support\Tree;

// Every workflow says each value where it is used.
//
// A YAML anchor names a value once and an alias repeats it elsewhere, which
// reads as tidy and costs the one check that judges a release train's bump:
// pin-only compares a workflow's lines before and after, and an alias is a line
// whose meaning lives somewhere else. So each value is written out, and where
// two jobs have to agree on one, a test here holds them to it.

/**
 * Every workflow this repository runs, by path.
 *
 * @return list<string>
 */
function everyWorkflow(): array
{
    return Tree::filesUnder(Tree::at('.github/workflows'), '.yml');
}

/** The `runs-on` of one job in a workflow's text, or empty where the job is not there. */
function whereAJobRuns(string $workflow, string $job): string
{
    $found = preg_match(sprintf('/^  %s:\n(?:(?!  \S).*\n)*?    runs-on: (\S+)/m', preg_quote($job, '/')), $workflow, $said);

    return $found === 1 ? $said[1] : '';
}

it('names no anchor and repeats no alias in any workflow', function (): void {
    $shorthand = [];

    foreach (everyWorkflow() as $path) {
        foreach (explode("\n", (string) file_get_contents($path)) as $number => $line) {
            $code = preg_replace('/\s#.*$/', '', $line) ?? $line;

            if (preg_match('/(?::|-)\s+[&*][A-Za-z][\w-]*|<<:/', $code) === 1) {
                $shorthand[] = sprintf('%s:%d %s', $path, $number + 1, trim($line));
            }
        }
    }

    expect(everyWorkflow())->not->toBe([], 'no workflow was read, so this rule read nothing')
        ->and($shorthand)->toBe([], sprintf(
            "These name a value once and repeat it by reference:\n  %s\n\nWrite the value out where it is used.\n",
            implode("\n  ", $shorthand),
        ));
});

it('runs both Swift gates on the one macOS image', function (): void {
    $ci = (string) file_get_contents(Tree::at('.github/workflows/ci.yml'));

    expect(whereAJobRuns($ci, 'typecheck'))->toStartWith('macos-')
        ->and(whereAJobRuns($ci, 'swift'))->toBe(whereAJobRuns($ci, 'typecheck'));
});
