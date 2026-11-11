<?php

declare(strict_types=1);

/*
 * The gate's heatmap against the base reads the functions of each file from
 * the proofs carried from the default branch, keyed by each unit's path. A
 * unit held by a `holds:` group is a directory, so the files under it have no
 * entry of their own, and the lookup returns null where `FunctionSpans` is
 * owed: the verdict stops with a type error before it judges anything, on
 * every pull request once the default branch has a ledger.
 *
 * This reads a file with no carried proof as having no functions, which is
 * what the heatmap already says of a file it cannot read.
 *
 * Delete this script and the two `composer.json` hooks that call it in the
 * pull request that takes the nightworksio/mutation-gate commit that fixes it.
 */

const WHERE = '/../vendor/nightworksio/mutation-gate/src/Cli/Flow/Heating.php';

const SHIPS = <<<'SHIPS'
            static fn(Path $file): FunctionSpans => $spans[$file->value()],
SHIPS;

const BECOMES = <<<'BECOMES'
            static fn(Path $file): FunctionSpans => $spans[$file->value()] ?? FunctionSpans::none(),
BECOMES;

const WHEN_THE_FILE_IS_NOT_THERE = "patch_mutation_gate_heating: %s is not there.\n\nThe gate no longer ships the file this patch rewrites. Check that a held unit carried from the default branch no longer stops the verdict, then delete this script and the two `composer.json` hooks that call it.\n";

const WHEN_THE_LINE_HAS_MOVED = "patch_mutation_gate_heating: the line this patch rewrites is not in %s.\n\nEither the gate fixed it, in which case delete this script and the two `composer.json` hooks that call it, or the line moved, in which case point this at it: until then every verdict stops on a held unit carried from the default branch.\n";

$path = sprintf('%s%s', __DIR__, WHERE);

if (! file_exists($path)) {
    fwrite(STDERR, sprintf(WHEN_THE_FILE_IS_NOT_THERE, $path));

    exit(1);
}

$source = file_get_contents($path);

if (! is_string($source)) {
    fwrite(STDERR, sprintf("patch_mutation_gate_heating: could not read %s.\n", $path));

    exit(1);
}

if (str_contains($source, BECOMES)) {
    fwrite(STDOUT, "patch_mutation_gate_heating: already applied.\n");

    exit(0);
}

if (mb_substr_count($source, SHIPS) !== 1) {
    fwrite(STDERR, sprintf(WHEN_THE_LINE_HAS_MOVED, $path));

    exit(1);
}

file_put_contents($path, str_replace(SHIPS, BECOMES, $source));

fwrite(STDOUT, "patch_mutation_gate_heating: a file with no carried proof reads as having no functions.\n");
