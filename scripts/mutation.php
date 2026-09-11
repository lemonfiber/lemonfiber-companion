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
 */

$root = dirname(__DIR__);
$manifests = glob(sprintf('%s/app-modules/*/composer.json', $root));

if ($manifests === false || $manifests === []) {
    fwrite(STDERR, "No module manifests found, so there is nothing to mutate.\n");

    exit(1);
}

/** @var array<int, list<string>> $byFloor */
$byFloor = [];

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

    $source = sprintf('%s/app-modules/%s/src', $root, $module);

    // Nothing to mutate yet. Said out loud rather than skipped in silence,
    // because "no mutants" and "every mutant killed" print the same way.
    if (sourceFiles($source) === []) {
        fwrite(STDOUT, sprintf("  %s: no code yet, nothing to mutate\n", $module));

        continue;
    }

    $byFloor[$floor][] = $source;
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

    $command = sprintf(
        '%s/vendor/bin/pest --mutate --covered-only --ignore-min-score-on-zero-mutations --min=%d --path=%s',
        escapeshellarg($root),
        $floor,
        escapeshellarg(implode(',', $paths)),
    );

    passthru($command, $status);

    $failed = $failed === 0 ? $status : $failed;
}

exit($failed === 0 ? 0 : 1);

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
