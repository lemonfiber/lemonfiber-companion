<?php

declare(strict_types=1);

use Tests\Support\Tree;

/**
 * A tree every gate already passed on still reaches SonarCloud with its coverage.
 *
 * `ci` skips the suite on a proven tree, so `tests and coverage` writes no
 * report on that run. Without one SonarCloud reads every new line as untested
 * and its quality gate fails on new coverage of nothing, on a pull request
 * whose code passed the same 100% floor the run before. So the proof carries
 * the report that proved the tree, and the scan reads that one.
 *
 * Over the text of the workflow, as the other guards here are: what must not
 * come back is a step or a key, and a cache that GitHub holds between runs is
 * not something a suite can stand up.
 */

/** The workflow, read once per test. */
function theCiWorkflow(): string
{
    return (string) file_get_contents(Tree::at('.github/workflows/ci.yml'));
}

/** One job of it: its id's line and every line indented under it, up to the next job. */
function theJobNamed(string $id): string
{
    preg_match(sprintf('/^  %s:\n(?:(?:    .*|)\n)+/m', preg_quote($id, '/')), theCiWorkflow(), $found);

    return $found[0] ?? '';
}

/** What SonarCloud is told to read, by its property's name. */
function whatSonarReads(string $property): string
{
    preg_match(sprintf('/^%s=(.+)$/m', preg_quote($property, '/')), (string) file_get_contents(Tree::at('sonar-project.properties')), $found);

    return trim($found[1] ?? '');
}

it('keeps every proof under the one key, whoever reads or writes it', function (): void {
    preg_match_all('/path: \.ci-proved\n\s+key: (.+)/', theCiWorkflow(), $keys);

    expect($keys[1])->toHaveCount(3, 'the lookup, the save and the restore of a proof are not all where this expects them')
        ->and(array_values(array_unique(array_map(static fn(string $key): string => explode('}}-', $key)[0], $keys[1]))))
        ->toBe(['${{ env.PROVED_TREE '], 'a proof is read or written under a key other than PROVED_TREE');
});

it('records the report that proved a tree in the proof', function (): void {
    $recording = theJobNamed('proved-tree');

    expect($recording)->not->toBe('', 'there is no proved-tree job, so this guard read nothing')
        ->and($recording)->toContain("name: coverage-report\n          path: .ci-proved/coverage")
        ->and(str_contains((string) strstr($recording, 'name: coverage-report'), 'actions/cache/save'))->toBeTrue('the report is fetched after the proof is saved, so the proof holds none');
});

it('hands the scan the proof\'s report where the suite was skipped on a proven tree, and fails without one', function (): void {
    $scan = theJobNamed('sonar');
    $clover = whatSonarReads('sonar.php.coverage.reportPaths');
    $junit = whatSonarReads('sonar.php.tests.reportPath');

    expect($scan)->toContain('needs: [scope, tests]')
        ->and($scan)->toContain("needs.tests.result == 'skipped' && needs.scope.outputs.proved == 'true'")
        ->and($scan)->toContain('fail-on-cache-miss: true')
        ->and([$clover, $junit])->toBe(['coverage/clover.xml', 'coverage/junit.xml'], 'SonarCloud reads its reports from somewhere this guard does not expect')
        ->and($scan)->toContain(sprintf('cp .ci-proved/%s .ci-proved/%s %s/', $clover, $junit, dirname($clover)))
        ->and(theJobNamed('scope'))->toContain('proved: ${{ steps.proved.outputs.cache-hit }}');
});
