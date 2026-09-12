<?php

declare(strict_types=1);

/*
 * One composer command, over every module manifest.
 *
 * Composer's own commands read the manifest they are run in. This repository
 * has thirteen: the root one, and one per module under `app-modules/`, each a
 * path package with its own `require`, its own autoload map and its own
 * declared kind and floors. A gate that runs `composer <something>` and stops
 * there has checked the one manifest that changes least often and said nothing
 * about the twelve that carry the architecture.
 *
 * `validate:modules` already existed as a glob written inline in composer.json,
 * which is the same loop as this one with the quoting escaped twice. Normalize
 * needed it too, and the next command will as well — so the glob lives here
 * once rather than once per gate, because two copies of "where the modules are"
 * disagree the day a module moves.
 *
 * Usage: php scripts/manifests.php <command> [options...]
 *   php scripts/manifests.php validate --strict --no-check-publish
 *   php scripts/manifests.php normalize --dry-run
 *
 * Every manifest is visited even after one fails. A run that stops at the first
 * failure reports one file per push, and a sweep across twelve modules then
 * takes twelve rounds of CI to see the end of.
 */

// `$argv ?? []` rather than `$argv`: the CLI SAPI always populates it, and the
// analyser cannot know which SAPI this runs under. Reading `$_SERVER` for the
// same thing would route around Q2.
$arguments = array_slice($argv ?? [], 1);

if ($arguments === []) {
    fwrite(STDERR, "Usage: php scripts/manifests.php <command> [options...]\n");

    exit(1);
}

$root = dirname(__DIR__);
$manifests = glob(sprintf('%s/app-modules/*/composer.json', $root));

if ($manifests === false || $manifests === []) {
    fwrite(STDERR, "No module manifests found, so there is nothing to check.\n");

    exit(1);
}

$failed = 0;

foreach ($manifests as $manifest) {
    $command = implode(' ', array_map(
        escapeshellarg(...),
        [...$arguments, $manifest],
    ));

    passthru(sprintf('composer %s', $command), $status);

    $failed = $failed === 0 ? $status : $failed;
}

exit($failed === 0 ? 0 : 1);
