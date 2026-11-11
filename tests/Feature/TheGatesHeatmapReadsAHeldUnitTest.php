<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

/** Where the gate ships the file the patch rewrites, inside a tree. */
const THE_GATES_HEATING = 'vendor/nightworksio/mutation-gate/src/Cli/Flow/Heating.php';

/** The line the gate ships, which looks up a file's functions with nothing to fall back on. */
const THE_LOOKUP_AS_SHIPPED = '            static fn(Path $file): FunctionSpans => $spans[$file->value()],';

/** The line the patch writes, which reads a file with no carried proof as having no functions. */
const THE_LOOKUP_AS_PATCHED = '            static fn(Path $file): FunctionSpans => $spans[$file->value()] ?? FunctionSpans::none(),';

/**
 * A tree holding the patch script and a Heating.php holding these lines, or none, run once or twice.
 *
 * @return list<array{exit: int|null, said: string, holds: string}>
 */
function theGatesHeatingPatched(?string $holds, int $times = 1): array
{
    $tree = sprintf('%s/heating-%s', sys_get_temp_dir(), uniqid(more_entropy: true));
    mkdir(sprintf('%s/scripts', $tree), recursive: true);
    copy(base_path('scripts/patch_mutation_gate_heating.php'), sprintf('%s/scripts/patch_mutation_gate_heating.php', $tree));

    if ($holds !== null) {
        mkdir(dirname(sprintf('%s/%s', $tree, THE_GATES_HEATING)), recursive: true);
        file_put_contents(sprintf('%s/%s', $tree, THE_GATES_HEATING), $holds);
    }

    $runs = [];

    for ($run = 0; $run < $times; $run++) {
        $process = new Process([PHP_BINARY, 'scripts/patch_mutation_gate_heating.php'], $tree);
        $process->run();
        $runs[] = [
            'exit' => $process->getExitCode(),
            'said' => sprintf('%s%s', $process->getOutput(), $process->getErrorOutput()),
            'holds' => $holds === null ? '' : (string) file_get_contents(sprintf('%s/%s', $tree, THE_GATES_HEATING)),
        ];
    }

    new Filesystem()->deleteDirectory($tree);

    return $runs;
}

it('reads a file with no carried proof as having no functions, and says so', function (): void {
    [$ran] = theGatesHeatingPatched(sprintf("<?php\n\n%s\n", THE_LOOKUP_AS_SHIPPED));

    expect($ran['exit'])->toBe(0)
        ->and($ran['holds'])->toBe(sprintf("<?php\n\n%s\n", THE_LOOKUP_AS_PATCHED))
        ->and($ran['said'])->toContain('a file with no carried proof reads as having no functions');
});

it('finds the patch in place on a second install and changes nothing', function (): void {
    [, $again] = theGatesHeatingPatched(sprintf("<?php\n\n%s\n", THE_LOOKUP_AS_SHIPPED), times: 2);

    expect($again['exit'])->toBe(0)
        ->and($again['holds'])->toBe(sprintf("<?php\n\n%s\n", THE_LOOKUP_AS_PATCHED))
        ->and($again['said'])->toContain('already applied');
});

it('stops the install where the line is gone or written twice, and writes nothing', function (string $holds): void {
    [$ran] = theGatesHeatingPatched($holds);

    expect($ran['exit'])->toBe(1)
        ->and($ran['holds'])->toBe($holds)
        ->and($ran['said'])->toContain('the line this patch rewrites is not in');
})->with([
    'gone' => ["<?php\n\nreturn null;\n"],
    'written twice' => [sprintf("<?php\n\n%s\n%1\$s\n", THE_LOOKUP_AS_SHIPPED)],
]);

it('stops the install where the gate no longer ships the file', function (): void {
    [$ran] = theGatesHeatingPatched(null);

    expect($ran['exit'])->toBe(1)
        ->and($ran['said'])->toContain('is not there');
});
