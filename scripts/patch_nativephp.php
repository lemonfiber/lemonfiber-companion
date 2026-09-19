<?php

declare(strict_types=1);

/*
 * Every step of a build must be asked the same question about development
 * dependencies.
 *
 * NativePHP assembles a bundle in three steps. Both platform lanes decide
 * whether to install development dependencies from the build type — debug keeps
 * them, release does not — and the dump that follows is not asked at all: it
 * passes no flag, so it always keeps `autoload-dev`.
 *
 * That disagreement is a fatal on a release build. The install has dropped the
 * development packages and the dump writes an authoritative classmap that
 * expects them, so package discovery registers a provider the classmap was
 * built without:
 *
 *     Class "…\Collision\Adapters\Laravel\CollisionServiceProvider" not found
 *
 * `PHPBridge` then reports an empty response, so the first frame never renders
 * and the app returns to the launcher without saying anything. Every suite is
 * green when that happens, because no suite builds a bundle.
 *
 * So the dump is asked the same question the install was, from the same
 * variable, in the same method. A debug build keeps its development
 * dependencies through all three steps and a release build drops them through
 * all three — which is what lets a stand-in for a stack reach a handset while
 * being absent from anything shipped.
 *
 * **What the bundle removes is the other half of this, and it is not patched
 * here.** The cleanup drops `tests` at any depth, so an autoloader entry
 * pointing into it is a file that is gone by the time anything reads it. Only
 * `autoload-dev.files` is `require`d unconditionally at boot, so that is the
 * entry this application must not have — see {@see \Tests\Support\Rules},
 * which is a class for exactly that reason. A classmap entry naming a dropped
 * file is inert until something autoloads it, and on a device nothing does.
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
        // A bundle carrying development dependencies is a bigger bundle, and
        // the copy that assembles it passes no timeout of its own — so it takes
        // Laravel's default sixty seconds, which a debug build exceeds. Sixty
        // seconds is a budget rather than a correctness property, and what it
        // produces when it runs out is an rsync killed halfway: a partial tree,
        // a build that fails somewhere later, and nothing saying the copy is
        // what ended.
        'in' => '/../vendor/nativephp/mobile/src/Support/BundleFileManager.php',
        'ships' => <<<'SHIPS'
        $result = Process::run("rsync -a --copy-links {$excludeFlags} \"{$source}/\" \"{$destination}/\"");
SHIPS,
        'becomes' => <<<'BECOMES'
        $result = Process::timeout(600)->run("rsync -a --copy-links {$excludeFlags} \"{$source}/\" \"{$destination}/\"");
BECOMES,
    ],
    [
        'in' => '/../vendor/nativephp/mobile/src/Concerns/PreparesBuild.php',
        // The closing marker sits at column zero so that nothing is stripped:
        // PHP removes the marker's own indentation from every line of a
        // heredoc, and these lines have to arrive with the twelve, sixteen and
        // twenty spaces the package wrote them with or the match is a match
        // against text that exists nowhere.
        'ships' => <<<'SHIPS'
            $this->components->task('Optimizing autoloader', function () use ($tempDir) {
                $result = Process::path($tempDir)
                    ->timeout(60)
                    ->run('composer dump-autoload --optimize --classmap-authoritative');
SHIPS,
        'becomes' => <<<'BECOMES'
            $this->components->task('Optimizing autoloader', function () use ($tempDir, $excludeDevDependencies) {
                $result = Process::path($tempDir)
                    ->timeout(60)
                    ->run('composer dump-autoload --optimize --classmap-authoritative'.($excludeDevDependencies ? ' --no-dev' : ''));
BECOMES,
    ],
    [
        // An agent's git worktree inside the project is a second checkout of
        // this repository — a `vendor/` of its own, its own `app-modules`, its
        // own lockfile — and the bundler copies it. Measured on 2026-09-16: a
        // debug bundle came to 243 MB, of which 225 MB was one worktree under
        // `.claude/`, carried onto a handset and unpacked there.
        //
        // `.git` is already excluded at any depth and this is the same fact
        // wearing a different name: a directory a tool keeps its own state in,
        // which no build has a use for. It sits beside `.git` rather than in
        // `PROJECT` because a worktree can be nested anywhere, and a
        // project-root rule would miss one a directory deeper.
        //
        // The alternative was asking every agent to put its worktree somewhere
        // else, which is a convention — and a convention is what this
        // repository calls the thing that holds until somebody new arrives.
        'in' => '/../vendor/nativephp/mobile/src/Support/BundleExclusions.php',
        'ships' => <<<'SHIPS'
    public const ANY_DEPTH = [
        '.git',
SHIPS,
        'becomes' => <<<'BECOMES'
    public const ANY_DEPTH = [
        '.git',
        '.claude',
BECOMES,
    ],
    [
        // `bridge/` is a path repository, so composer symlinks it into
        // `vendor/lemonfiber/bridge` — and the bundler copies with
        // `rsync -a --copy-links`, which follows the link and takes everything
        // under it. That includes `.build`, where SwiftPM leaves its module
        // caches and object files: 231 MB measured on 2026-09-19, growing every
        // time `swift test` runs, and carried onto a handset.
        //
        // Size is the smaller half. A module cache holds absolute paths from
        // the machine that built it and objects compiled from this checkout,
        // and none of it is anything a device has a use for — so what ships is
        // a copy of somebody's working tree inside the app.
        //
        // It already stops builds rather than merely bloating them: a debug
        // build died with `rsync: .../.build/debug/ModuleCache/...: No space
        // left on device` and succeeded unchanged once the directory was gone.
        //
        // Sits beside `.git` and `.claude` for their reason: a directory a tool
        // keeps its own state in, which no build has a use for, and which can
        // appear at any depth rather than only at the project root.
        'in' => '/../vendor/nativephp/mobile/src/Support/BundleExclusions.php',
        'ships' => <<<'SHIPS'
        '.claude',
SHIPS,
        'becomes' => <<<'BECOMES'
        '.claude',
        '.build',
BECOMES,
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

/**
 * What to do when the file a patch rewrites is not there at all.
 *
 * A separate sentence from the one above, because the two send a reader to
 * different places: that one says to open the file and look for a line, and
 * this one is about a file there is nothing to open. Told apart here rather
 * than at the reader, who would otherwise go looking for a line in a path that
 * does not exist.
 *
 * One literal for the reason the one above is one literal.
 */
const WHEN_THE_FILE_IS_NOT_THERE = "patch_nativephp: %s is not there.\n\nThe package no longer ships the file this patch rewrites. Either it was renamed, in which case point that entry at the new path, or it is gone, in which case delete the entry — and if it was the last one, this script and the two `composer.json` hooks that call it. Skipping it quietly leaves a build fatalling on a device with nothing having said so. Do not ignore this.\n";

$rewritten = 0;

foreach (WHAT_THIS_REWRITES as ['in' => $where, 'ships' => $ships, 'becomes' => $becomes]) {
    $path = sprintf('%s%s', __DIR__, $where);

    if (! file_exists($path)) {
        fwrite(STDERR, sprintf(WHEN_THE_FILE_IS_NOT_THERE, $path));

        exit(1);
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
    fwrite(STDOUT, sprintf("patch_nativephp: %d line(s) rewritten in the packager.\n", $rewritten));
}
