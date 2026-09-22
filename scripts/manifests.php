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

/*
 * Where the manifests are, asked of the one file that already knows.
 *
 * This globbed `app-modules/*` and missed `bridge/composer.json`, which is a
 * manifest this repository owns, carries its own `extra` block, and was
 * un-normalized for as long as nobody looked — `license` and `type` the wrong
 * way round. The root's own `composer validate` and `normalize` read the root
 * and stop there, so nothing reached it from either direction.
 *
 * A second entry beside the glob would have fixed that file and left the next
 * one to be found the same way. The root already declares every package this
 * repository owns, as a `path` repository, because that is how composer is
 * told to symlink them — so that declaration is the list, and a package added
 * tomorrow is checked the day it is added rather than the day somebody
 * remembers this file.
 *
 * Which is the argument the header above already makes about the modules: two
 * copies of *where the packages are* disagree the day one moves.
 */
$declared = json_decode((string) file_get_contents(sprintf('%s/composer.json', $root)), associative: true);
$repositories = is_array($declared) && array_key_exists('repositories', $declared) && is_array($declared['repositories'])
    ? $declared['repositories']
    : [];

$manifests = [];

foreach ($repositories as $repository) {
    if (! is_array($repository) || ! array_key_exists('type', $repository) || $repository['type'] !== 'path') {
        continue;
    }

    // A path repository with no `url` is not a package this can find, and is
    // composer's to complain about rather than this script's.
    if (! array_key_exists('url', $repository) || ! is_string($repository['url'])) {
        continue;
    }

    $found = glob(sprintf('%s/%s/composer.json', $root, $repository['url']));

    if ($found !== false) {
        // In the order the root declares them, and within one entry in the
        // order `glob` returns — which is already sorted. Not sorted again
        // here: the order a person reads these in is the order the root lists
        // them, and re-sorting would put `bridge` among the modules.
        $manifests = [...$manifests, ...$found];
    }
}

if ($manifests === []) {
    fwrite(STDERR, "The root manifest declares no path repositories, so there is nothing to check.\n");

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
