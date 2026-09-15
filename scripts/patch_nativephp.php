<?php

declare(strict_types=1);

/*
 * A build must not ship an autoloader that requires files the bundle excludes.
 *
 * NativePHP assembles a bundle in steps that are asked different questions about
 * development dependencies, and a device needs all of them answered the same way.
 * Both lanes install them for a debug build; the dump that follows excludes them
 * in neither; and the step after that removes everything the device has no use
 * for, `tests/` among it.
 *
 * Disagreeing produces two fatals, and fixing either one alone produces the
 * other. While the dump is asked to keep `autoload-dev`, it writes back the
 * entry the bundle is about to delete the directory of — this application has
 * one, `tests/Support/rules.php`, which is where it belongs:
 *
 *     Warning: require(.../tests/Support/rules.php): Failed to open stream
 *
 * Once the dump excludes them but the install still fetches them, package
 * discovery reads a vendor directory holding development packages and registers
 * a provider the authoritative classmap was built without:
 *
 *     Class "…\Collision\Adapters\Laravel\CollisionServiceProvider" not found
 *
 * Either way `PHPBridge` reports an empty response, so the first frame never
 * renders and the app returns to the launcher without saying anything. Every
 * suite is green when that happens, because no suite builds a bundle.
 *
 * `--no-dev` in every lane rather than shipping `tests/` in one, because a
 * development dependency is useless to a device — the files that would use it
 * are the ones being excluded.
 *
 * Run from `post-install-cmd` and `post-update-cmd`, so it survives the next
 * `composer install` rather than being a thing somebody remembers. It refuses
 * to be a no-op: an edit that matches nothing is the failure this repository
 * keeps finding in its own rules, and a silent one here would mean the device
 * fatal came back with the patch still in the tree looking applied.
 */

/**
 * Every line this rewrites, and what it becomes.
 *
 * A list rather than a map keyed by file, so that two edits to one file stay
 * expressible. Each entry names its own file for the same reason the refusal
 * does: a patch that cannot say *which* line moved sends the reader to search
 * a package for it.
 */
const WHAT_THIS_REWRITES = [
    [
        'in' => '/../vendor/nativephp/mobile/src/Commands/BuildIosAppCommand.php',
        'ships' => "                    ...(\$this->option('release') ? ['--no-dev'] : []),",
        'becomes' => '                    ...[\'--no-dev\'],',
    ],
    [
        'in' => '/../vendor/nativephp/mobile/src/Concerns/RunsAndroid.php',
        // The comment is part of what is matched and part of what is removed:
        // it states the behaviour the line below it no longer has, and a reader
        // who found it still there would believe the package rather than this.
        'ships' => <<<'SHIPS'
                // Include dev dependencies for debug builds (like iOS does)
                $cleanCache = $this->buildType !== 'debug';
                $excludeDevDependencies = $this->buildType !== 'debug';
        SHIPS,
        'becomes' => <<<'BECOMES'
                $cleanCache = $this->buildType !== 'debug';
                $excludeDevDependencies = true;
        BECOMES,
    ],
    [
        'in' => '/../vendor/nativephp/mobile/src/Concerns/PreparesBuild.php',
        'ships' => "->run('composer dump-autoload --optimize --classmap-authoritative');",
        'becomes' => "->run('composer dump-autoload --optimize --classmap-authoritative --no-dev');",
    ],
];

/**
 * What to do when a line this patch rewrites is not where it was.
 *
 * One literal rather than several joined, because a join between two literals
 * is three mutants — drop either, swap them — and nothing asserts this sentence
 * word for word.
 */
const WHEN_THE_LINE_HAS_MOVED = "patch_nativephp: the line this patch rewrites is not in %s.\n\nEither the package fixed it, in which case delete that entry — and if it was the last one, this script and the two `composer.json` hooks that call it — or it moved, in which case a build is fatalling on a device again and nothing said so. Do not ignore this.\n";

$rewritten = 0;

foreach (WHAT_THIS_REWRITES as ['in' => $where, 'ships' => $ships, 'becomes' => $becomes]) {
    $path = sprintf('%s%s', __DIR__, $where);

    if (! file_exists($path)) {
        fwrite(STDERR, sprintf("patch_nativephp: %s is not there; nothing to patch.\n", $path));

        continue;
    }

    $source = file_get_contents($path);

    if (! is_string($source)) {
        fwrite(STDERR, sprintf("patch_nativephp: could not read %s.\n", $path));

        exit(1);
    }

    if (str_contains($source, $becomes)) {
        continue;
    }

    if (! str_contains($source, $ships)) {
        fwrite(STDERR, sprintf(WHEN_THE_LINE_HAS_MOVED, $path));

        exit(1);
    }

    file_put_contents($path, str_replace($ships, $becomes, $source));

    $rewritten++;
}

if ($rewritten > 0) {
    fwrite(STDOUT, sprintf("patch_nativephp: %d build step(s) now exclude dev dependencies.\n", $rewritten));
}
