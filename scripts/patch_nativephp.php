<?php

declare(strict_types=1);

/*
 * A debug build must not install the dependencies it then throws away.
 *
 * NativePHP's iOS build runs `composer install` and adds `--no-dev` only for a
 * release, while the bundle excludes `tests/` either way. So a debug build
 * writes an autoloader that requires every entry in `autoload-dev.files` and
 * ships without the directory holding them. This application has one —
 * `tests/Support/rules.php`, which is where it belongs — and the result on a
 * real device is a fatal before the first frame:
 *
 *     Failed opening required '.../tests/Support/rules.php'
 *     PersistentPHPRuntime: boot FAILED (-2) -> falling back to classic mode
 *     [NativeBoot] Native session exited: status=500
 *
 * Every suite is green when that happens, because no suite builds a bundle.
 * Proved both ways on an iPhone 12 mini: the release build of the same commit
 * boots in 160ms with `booted=true`.
 *
 * `--no-dev` in both lanes rather than shipping `tests/` in one, because a
 * development dependency is useless to a device — the files that would use it
 * are the ones being excluded.
 *
 * Run from `post-install-cmd` and `post-update-cmd`, so it survives the next
 * `composer install` rather than being a thing somebody remembers. It refuses
 * to be a no-op: an edit that matches nothing is the failure this repository
 * keeps finding in its own rules, and a silent one here would mean the device
 * fatal came back with the patch still in the tree looking applied.
 */

/** Where the build command lives, relative to this script. */
const THE_BUILD_COMMAND = '/../vendor/nativephp/mobile/src/Commands/BuildIosAppCommand.php';

/** What the package ships. */
const INSTALLS_DEV_DEPENDENCIES = "                    ...(\$this->option('release') ? ['--no-dev'] : []),";

/** What it is rewritten to. */
const INSTALLS_NEITHER_LANES_DEV = "                    ...['--no-dev'],";

/**
 * What to do when the line this patch rewrites is not where it was.
 *
 * One literal rather than several joined, because a join between two literals
 * is three mutants — drop either, swap them — and nothing asserts this sentence
 * word for word.
 */
const WHEN_THE_LINE_HAS_MOVED = "patch_nativephp: the line this patch rewrites is not in %s.\n\nEither the package fixed it, in which case delete this script and the two `composer.json` hooks that call it — or it moved, in which case a debug build is fatalling on a device again and nothing said so. Do not ignore this.\n";

$path = sprintf('%s%s', __DIR__, THE_BUILD_COMMAND);

if (! file_exists($path)) {
    fwrite(STDERR, sprintf("patch_nativephp: %s is not there; nothing to patch.\n", $path));

    exit(0);
}

$source = file_get_contents($path);

if (! is_string($source)) {
    fwrite(STDERR, sprintf("patch_nativephp: could not read %s.\n", $path));

    exit(1);
}

if (str_contains($source, INSTALLS_NEITHER_LANES_DEV)) {
    exit(0);
}

if (! str_contains($source, INSTALLS_DEV_DEPENDENCIES)) {
    fwrite(STDERR, sprintf(WHEN_THE_LINE_HAS_MOVED, $path));

    exit(1);
}

file_put_contents($path, str_replace(INSTALLS_DEV_DEPENDENCIES, INSTALLS_NEITHER_LANES_DEV, $source));

fwrite(STDOUT, "patch_nativephp: debug builds now install without dev dependencies.\n");
