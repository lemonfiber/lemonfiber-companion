<?php

declare(strict_types=1);

/*
 * A source include with a wildcard measures nothing under a dot directory.
 *
 * `phpunit.xml` names `app-modules/*` + `/src` as a source directory, because
 * the list of modules is not something a coverage config should have to be told
 * twice. PHPUnit maps that entry to files by walking it, and then asks of each
 * file whether it sits in a hidden directory *relative to the entry's root*:
 *
 *     $basePath = realpath($path);
 *     ...
 *     $relativePath = str_replace((string) $basePath, '', $path);
 *
 * `realpath()` answers `false` for a path holding a wildcard. `(string) false`
 * is the empty string, and `str_replace('', '', $file)` returns the file path
 * **unchanged** — so the hidden-directory test runs against the absolute path
 * rather than the relative one. A checkout whose absolute path holds a dot
 * component therefore has every file under that entry dropped from the map.
 *
 * `.claude/worktrees/agent-…` is exactly such a path, and it is where an agent
 * working in isolation puts its checkout. Measured from one: 34 files instead
 * of 481 — `bootstrap/Composition` and `bridge/src` survive because their
 * entries are literal and resolve, and all 401 files under `app-modules/*` +
 * `/src` vanish. Both `--min=100` and the Floors suite then pass, over almost
 * nothing.
 *
 * **The root is resolvable; only the whole pattern is not.** So where
 * `realpath()` refuses the entry, this resolves the literal part in front of
 * the first wildcard instead. `app-modules/*` + `/src` has the root
 * `app-modules`, and a file under it reads as `/kernel/src/Api/Held.php` — no
 * dot component, correctly kept. A file at `app-modules/kernel/src/.build/X.php`
 * still reads as hidden and is still dropped, which is what the test is for.
 * Nothing about the check's intent changes; it is given the root it was always
 * meant to subtract.
 *
 * The alternative was naming the thirteen module directories literally, which
 * trades this for a worse failure: a module added tomorrow would be silently
 * unmeasured, and the gate would pass over it exactly as it passes over
 * everything today. A wildcard that measures the wrong set is fixable; a
 * literal list that goes stale is not detectable.
 *
 * Run from `post-install-cmd` and `post-update-cmd`, so it survives the next
 * `composer install` rather than being a thing somebody remembers. It refuses
 * to be a no-op in every direction it can: a package that is not there, a file
 * it cannot read, and an anchor that has moved all stop the install, because a
 * patch that quietly matched nothing would leave coverage measuring a fraction
 * of the tree with this file sitting in it looking applied.
 */

/** The file this rewrites, relative to this script. */
const WHERE = '/../vendor/phpunit/phpunit/src/TextUI/Configuration/SourceMapper.php';

const SHIPS = <<<'SHIPS'
            $basePath = realpath($path);
SHIPS;

const BECOMES = <<<'BECOMES'
            $basePath = realpath($path);

            // A wildcard entry has no realpath, and `isInHiddenDirectory()`
            // subtracts `(string) false` — the empty string — leaving it to
            // test the absolute path. Resolve the literal part in front of the
            // wildcard, which is the root that test was always relative to.
            // See scripts/patch_phpunit_source_mapper.php.
            if ($basePath === false) {
                $wildcard = strpbrk($path, '*?[');

                if ($wildcard !== false) {
                    $basePath = realpath(substr($path, 0, strlen($path) - strlen($wildcard)));
                }
            }
BECOMES;

const WHEN_THE_PACKAGE_IS_NOT_THERE = "patch_phpunit_source_mapper: %s is not there.\n\nPHPUnit no longer ships the file this patch rewrites. Either it was renamed, in which case point this at the new path, or the package is gone, in which case delete this script and the two `composer.json` hooks that call it. Skipping it quietly leaves coverage measuring 34 files instead of 481 in any checkout under a dot directory, with `--min=100` and the Floors suite both green over it. Do not ignore this.\n";

const WHEN_THE_LINE_HAS_MOVED = "patch_phpunit_source_mapper: the line this patch rewrites is not in %s.\n\nEither PHPUnit fixed it — in which case check that a wildcard source include measures the whole tree from a checkout under a dot directory, and then delete this script and the two `composer.json` hooks that call it — or it moved, in which case coverage is about to go back to measuring `bootstrap/Composition` and `bridge/src` alone from such a checkout, and every gate over it will report a pass. Do not ignore this.\n";

$path = sprintf('%s%s', __DIR__, WHERE);

if (! file_exists($path)) {
    fwrite(STDERR, sprintf(WHEN_THE_PACKAGE_IS_NOT_THERE, $path));

    exit(1);
}

$source = file_get_contents($path);

if (! is_string($source)) {
    fwrite(STDERR, sprintf("patch_phpunit_source_mapper: could not read %s.\n", $path));

    exit(1);
}

// Already applied. Said rather than counted as a rewrite, because `composer
// install` runs this twice in a row often enough that a second pass finding
// nothing is the ordinary case rather than the suspicious one.
if (str_contains($source, BECOMES)) {
    fwrite(STDOUT, "patch_phpunit_source_mapper: already applied.\n");

    exit(0);
}

if (! str_contains($source, SHIPS)) {
    fwrite(STDERR, sprintf(WHEN_THE_LINE_HAS_MOVED, $path));

    exit(1);
}

file_put_contents($path, str_replace(SHIPS, BECOMES, $source));

fwrite(STDOUT, "patch_phpunit_source_mapper: a wildcard source include now resolves its own root.\n");
