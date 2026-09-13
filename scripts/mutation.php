<?php

declare(strict_types=1);

/*
 * Mutation testing, at the floor each module declared for itself.
 *
 * Coverage floors are read out of the clover report by the `Floors` suite.
 * Mutation floors cannot be: there is no machine-readable mutation report —
 * Pest offers `--min`, which fails a run, and nothing that emits a score. So
 * the floor is enforced by invocation rather than by reading, and this is what
 * does the invoking.
 *
 * Modules that share a floor share a run. A floor of 100 admits no offsetting
 * between them — one surviving mutant anywhere in the path list drops the score
 * below 100 and fails — so grouping costs nothing in rigour and saves a full
 * suite pass per module, which is what each extra invocation actually costs.
 * A module whose floor differs gets its own run, because that is exactly where
 * a shared one would let the stricter module carry the looser.
 *
 * The paths come from the manifests. The list used to be written out in
 * composer.json, which is a second source of truth that goes stale the day a
 * module is added and goes stale silently — the run still passes, over less.
 *
 * **Two arguments, both for CI.** `--list` prints the modules worth mutating as
 * a JSON array, which is what a workflow matrix reads; `--module=<name>`
 * narrows a run to one of them. Together they let the slowest gate in the
 * repository run a module per runner instead of all of them in a row — it is
 * the one gate where the work is genuinely separable, because a floor of 100
 * admits no offsetting between modules and each is already judged alone.
 *
 * Neither changes what is mutated locally: `composer test:mutation` with no
 * arguments is the whole of it, in one process, which is what somebody running
 * it by hand wants.
 */

$root = dirname(__DIR__);
$manifests = glob(sprintf('%s/app-modules/*/composer.json', $root));

/** @var list<string> $given */
$given = array_slice($argv ?? [], 1);

$asked = argument($given, '--module=');
$listing = in_array('--list', $given, strict: true);

if ($manifests === false || $manifests === []) {
    fwrite(STDERR, "No module manifests found, so there is nothing to mutate.\n");

    exit(1);
}

/** @var array<int, list<string>> $byFloor */
$byFloor = [];

/** @var list<string> $worthMutating */
$worthMutating = [];

foreach ($manifests as $manifest) {
    $raw = file_get_contents($manifest);
    $module = basename(dirname($manifest));
    $floor = declaredFloor(is_string($raw) ? $raw : '');

    if ($floor === null) {
        fwrite(STDERR, sprintf(
            "%s declares no mutation floor.\n\n"
            . "Add it beside the kind in the module's own manifest:\n"
            . "    \"extra\": { \"lemonfiber\": { \"floors\": { \"mutation\": 100 } } }\n\n"
            . "There is no default on purpose — a module that inherits one is exempt from\n"
            . "the decision rather than held to it. G7 reports the same omission in the\n"
            . "test suite, so this should already have failed there.\n",
            $module,
        ));

        exit(1);
    }

    // A floor of zero is a declared position rather than a gap: a component
    // holds state and an adapter forwards a call, so mutating either measures
    // the fake rather than the application.
    if ($floor === 0) {
        continue;
    }

    // Narrowed to one module where a runner was given one. The floor is still
    // read for every module rather than only this one, because the refusal
    // above is the check that a module declared a floor at all — and a shard
    // that skipped it would let an undeclared floor through on eleven runners
    // out of twelve.
    if ($asked !== null && $asked !== $module) {
        continue;
    }

    $source = sprintf('%s/app-modules/%s/src', $root, $module);

    // Nothing to mutate yet. Said out loud rather than skipped in silence,
    // because "no mutants" and "every mutant killed" print the same way.
    if (sourceFiles($source) === []) {
        if (! $listing) {
            fwrite(STDOUT, sprintf("  %s: no code yet, nothing to mutate\n", $module));
        }

        continue;
    }

    if ($listing) {
        $worthMutating[] = $module;

        continue;
    }

    $byFloor[$floor][] = $source;
}

// What a workflow matrix reads. An empty array is a legitimate answer — no
// module holds code yet — and a matrix over it runs nothing, which is why the
// job that aggregates the shards has to treat "nothing ran" as a pass rather
// than as an absence.
if ($listing) {
    // Not sorted here, and that is not an omission. `glob()` sorts what it
    // returns, so this list arrives in the order the manifests were walked and
    // stays in it — which is all a matrix needs, so that its runners do not
    // reshuffle between commits. Sorting it again would have meant reaching for
    // a byte comparison `L6` forbids, and claiming an exemption for a line that
    // changes nothing is worse than the line.
    fwrite(STDOUT, sprintf("%s\n", json_encode($worthMutating)));

    exit(0);
}

if ($asked !== null && $byFloor === []) {
    fwrite(STDERR, sprintf(
        "There is no module called %s with code to mutate.\n\n"
        . "A shard naming one that is gone is a shard that passes having done nothing,\n"
        . "which is the whole failure this gate exists to prevent. The matrix is built\n"
        . "from `--list` on the same commit, so this means the two disagree.\n",
        $asked,
    ));

    exit(1);
}

if ($byFloor === []) {
    fwrite(STDOUT, "No module has code to mutate yet.\n");

    exit(0);
}

$failed = 0;

foreach ($byFloor as $floor => $paths) {
    fwrite(STDOUT, sprintf(
        "\nMutation at %d%%: %s\n",
        $floor,
        implode(', ', array_map(static fn(string $p): string => basename(dirname($p)), $paths)),
    ));

    // The same two suites `composer test` leaves out, for the same reasons and
    // with an extra one here. `Floors` reads the clover report rather than
    // producing one, so it fails outright in a run that was never asked for
    // coverage — and a mutation run is exactly that. `Guards` plants violations
    // and runs the analyser and the suite over them as subprocesses, which
    // under mutation would be re-run once per mutant.
    //
    // Nothing was catching this: with no module holding code, the loop above
    // never reached a run at all, so the invocation was unexercised until the
    // first one did.
    $command = sprintf(
        '%s/vendor/bin/pest --mutate --covered-only --ignore-min-score-on-zero-mutations '
        . '--exclude-testsuite=Guards,Floors --min=%d --path=%s',
        escapeshellarg($root),
        $floor,
        escapeshellarg(implode(',', $paths)),
    );

    passthru($command, $status);

    $failed = $failed === 0 ? $status : $failed;
}

exit($failed === 0 ? 0 : 1);

/**
 * The value of a `--name=` argument, or null where it was not given.
 *
 * Read off the arguments rather than through `getopt()`, which stops at the
 * first argument it does not recognise and would silently drop everything
 * composer passes through after `--`.
 *
 * `mb_substr` and `mb_strlen` because `L3` forbids the byte versions
 * everywhere, and the rule is right to be blanket: the exception a module name
 * would earn is the exception somebody copies to a stack name.
 *
 * @param list<string> $given
 */
function argument(array $given, string $prefix): ?string
{
    foreach ($given as $argument) {
        if (str_starts_with($argument, $prefix)) {
            return mb_substr($argument, mb_strlen($prefix));
        }
    }

    return null;
}

/**
 * The mutation floor a manifest declares, or null where it declares none.
 *
 * Each step is checked rather than reached through, because a manifest is a
 * file on disk: an `??` chain through four keys would turn a malformed one into
 * a silent zero, which reads exactly like a module that meant to declare none.
 */
function declaredFloor(string $manifest): ?int
{
    /** @var mixed $decoded */
    $decoded = json_decode($manifest, associative: true);

    if (! is_array($decoded)) {
        return null;
    }

    foreach (['extra', 'lemonfiber', 'floors'] as $key) {
        if (! is_array($decoded) || ! array_key_exists($key, $decoded)) {
            return null;
        }

        /** @var mixed $decoded */
        $decoded = $decoded[$key];
    }

    if (! is_array($decoded) || ! array_key_exists('mutation', $decoded)) {
        return null;
    }

    /** @var mixed $floor */
    $floor = $decoded['mutation'];

    return is_int($floor) ? $floor : null;
}

/**
 * Every PHP file under a directory, at any depth.
 *
 * @return list<string>
 */
function sourceFiles(string $directory): array
{
    if (! is_dir($directory)) {
        return [];
    }

    $found = [];

    $tree = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
    );

    /** @var SplFileInfo $file */
    foreach ($tree as $file) {
        if ($file->getExtension() === 'php') {
            $found[] = $file->getPathname();
        }
    }

    return $found;
}
