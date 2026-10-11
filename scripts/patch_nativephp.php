<?php

declare(strict_types=1);

/*
 * Applies the patches in `scripts/patch_nativephp/` to the NativePHP packages
 * this tree installs.
 *
 * Each patch is a unified diff, and the text before its first file says what
 * it changes and why. They are applied in the order of their names, since a
 * later patch can rewrite a line an earlier one wrote.
 *
 * A hunk is matched by its lines, never by the line numbers in its header: the
 * numbers say where the lines sat in the version this tree locks, and they
 * move whenever the package changes anything above them. Its lines are matched
 * as whole lines, wherever they appear, and must appear once: a hunk whose
 * lines appear in more than one place is refused, since a context too short to
 * be told apart rewrites every place it matches, unless its patch carries the
 * line `patch_nativephp: replace-all` before its first file, saying it means
 * every place. A hunk whose result is already in the file is skipped, because
 * `composer install` does not unpack a package again only because this script
 * runs, so most runs find every patch applied.
 *
 * `scripts/patch_nativephp/earlier/` holds what an earlier version of a hunk
 * wrote, under the name of the patch the hunk is in. A package an earlier
 * build patched carries that text, and is moved on to the hunk's current
 * result rather than refused.
 *
 * Run from `post-install-cmd` and `post-update-cmd`, so it survives the next
 * `composer install` rather than being a thing somebody remembers. It refuses
 * to be a no-op: an edit that matches nothing is the failure this repository
 * keeps finding in its own rules, and a silent one here would mean a device
 * fatal came back with the patch still in the tree looking applied. Every
 * patch is read before any file is written, so a patch this cannot read
 * leaves the packages as they were.
 */

/** Where the patches are, from this script's directory. */
const WHERE_THE_PATCHES_ARE = '/patch_nativephp';

/** Where the earlier versions of their hunks are, from this script's directory. */
const WHERE_EARLIER_HUNKS_ARE = '/patch_nativephp/earlier';

/** The only files a patch may name: NativePHP's, as composer installs them. */
const WHAT_A_PATCH_MAY_REWRITE = 'vendor/nativephp/';

/** The line a patch's prose carries to say each of its hunks rewrites every place its lines appear. */
const A_PATCH_MEANS_EVERY_MATCH = 'patch_nativephp: replace-all';

/** A hunk: its header, the line counts each side leaves out when it is one, and its lines. */
const A_HUNK = '/\A(@@ -\d+(?:,(\d+))? \+\d+(?:,(\d+))? @@[^\n]*)\n(.*)\z/s';

/**
 * What to do when a line a patch rewrites is not where it was.
 *
 * One literal rather than several joined, because a join between two literals
 * is three mutants — drop either, swap them — and nothing asserts this sentence
 * word for word.
 */
const WHEN_THE_LINE_HAS_MOVED = "patch_nativephp: the lines %2\$s rewrites are not in %1\$s.\n\nEither the package fixed it, in which case delete that hunk — and the patch, if it was its last, and this script, its patches and the two `composer.json` hooks that call it, if that was the last patch — or it moved, in which case a build is fatalling on a device again and nothing said so. Do not ignore this.\n";

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
const WHEN_THE_FILE_IS_NOT_THERE = "patch_nativephp: %1\$s, which %2\$s rewrites, is not there.\n\nThe package no longer ships the file this patch rewrites. Either it was renamed, in which case point the patch at the new path, or it is gone, in which case delete that file's part of the patch — and the patch, if nothing is left of it, and this script, its patches and the two `composer.json` hooks that call it, if that was the last patch. Skipping it quietly leaves a build fatalling on a device with nothing having said so. Do not ignore this.\n";

/**
 * What to do when the native project this tree builds from does not carry a line the package does.
 *
 * Its own sentence, because the remedy is not the other two's. The project
 * is a copy `native:install` took of the package, so a line missing from it
 * means the copy no longer matches the package this tree installed, and the
 * way out is to copy it again rather than to edit a patch.
 *
 * One literal for the reason the ones above are one literal.
 */
const WHEN_THE_INSTALLED_COPY_DIFFERS = "patch_nativephp: %1\$s does not carry what the package ships.\n\nThat file belongs to the native project `native:install` copied out of the package, and the copy no longer matches the package this tree installed. Run `php artisan native:install --force` to copy it again from the patched package. Building from it as it is leaves the device on code this patch never reached. Do not ignore this.\n";

/**
 * What to do when the lines a hunk rewrites appear in more than one place.
 *
 * One literal for the reason the ones above are one literal.
 */
const WHEN_THE_LINES_APPEAR_MORE_THAN_ONCE = "patch_nativephp: the lines %2\$s rewrites appear %3\$d times in %1\$s.\n\nA hunk is made where its lines appear, and these appear in more than one place, so it would rewrite every one of them. Give the hunk more context, until its lines appear once. Where the patch means every place, say so with the line `patch_nativephp: replace-all` before its first file. Nothing was written to that file. Do not ignore this.\n";

/**
 * What to do when a patch cannot be read as one.
 *
 * A patch read wrongly would apply less than it says, which is the quiet no-op
 * the rest of this script refuses. One literal for the reason the ones above
 * are one literal.
 */
const WHEN_A_PATCH_IS_NOT_ONE = "patch_nativephp: %1\$s is not a patch this can apply: %2\$s.\n\nEach patch is a unified diff of files under `vendor/nativephp/`, every hunk carrying exactly the lines its header counts. Nothing was written. Do not ignore this.\n";

/**
 * The package directory the bundle leaves out, so its copy of this tree has none.
 *
 * A build copies this tree without it, as `BundleExclusions::VENDOR_PATHS`
 * says, and runs `composer install` in the copy, which runs this script again.
 * Nothing in the bundle is built from that directory, so in the bundle's copy
 * there is nothing of it to patch. Where the directory is here and a file under
 * it is not, the file moved, and that is still refused.
 */
const WHAT_THE_BUNDLE_LEAVES_OUT = 'vendor/nativephp/mobile/resources';

/**
 * Where `native:install` copies a package template, which is what a build compiles.
 *
 * `native:install` copies the Android and Xcode projects out of the package
 * into `nativephp/android` and `nativephp/ios` once, and every build after that
 * compiles the copy without reading the package again. A rewrite of the
 * template reaches a project installed after it and never one installed before
 * it, so a hunk under the template is made to the installed copy as well. A
 * tree with no installed project has nothing there to patch: a fresh
 * checkout, whose install copies the template this run patched, and the
 * bundle's copy, which leaves `nativephp` out as `BundleExclusions::PROJECT`
 * says.
 */
const WHERE_AN_INSTALL_COPIES_A_TEMPLATE = [
    'vendor/nativephp/mobile/resources/androidstudio' => 'nativephp/android',
    'vendor/nativephp/mobile/resources/xcode' => 'nativephp/ios',
];

/** Stops the run, saying why. */
function refuse(string $message, string ...$arguments): never
{
    fwrite(STDERR, sprintf($message, ...$arguments));

    exit(1);
}

/**
 * The patches a directory holds, in the order they are applied.
 *
 * @return list<string>
 */
function patchesIn(string $directory): array
{
    $patches = glob(sprintf('%s%s/*.patch', __DIR__, $directory));

    return $patches === false ? [] : $patches;
}

/** Whether a patch's prose, before its first file, says each of its hunks means every place its lines appear. */
function meansEveryMatch(string $patch): bool
{
    $text = (string) file_get_contents($patch);
    $prose = preg_split('/^(?=--- )/m', $text, 2);

    return in_array(A_PATCH_MEANS_EVERY_MATCH, explode("\n", is_array($prose) ? $prose[0] : ''), strict: true);
}

/** How many places a file holds these lines in, as whole lines. */
function placesHolding(string $source, string $lines): int
{
    return mb_substr_count(sprintf("\n%s", $source), sprintf("\n%s", $lines));
}

/**
 * Each hunk a patch makes: the file, the lines it matches and what they become.
 *
 * What the patch says before its first file is prose. Everything after it is
 * a file's `---`/`+++` pair or a hunk, and a hunk carries exactly the lines its
 * header counts, so a header that counts fewer is refused rather than read as
 * a shorter hunk.
 *
 * @return list<array{path: string, ships: string, becomes: string}>
 */
function hunksIn(string $patch): array
{
    $text = file_get_contents($patch);

    if (! is_string($text)) {
        refuse(WHEN_A_PATCH_IS_NOT_ONE, $patch, 'it could not be read');
    }

    $files = preg_split('/^(?=--- )/m', $text);
    $hunks = [];

    foreach (array_slice(is_array($files) ? $files : [], 1) as $file) {
        $hunks = [...$hunks, ...hunksInFile($patch, $file)];
    }

    if ($hunks === []) {
        refuse(WHEN_A_PATCH_IS_NOT_ONE, $patch, 'it has no hunks');
    }

    return $hunks;
}

/**
 * The hunks under one `---`/`+++` pair, refused unless both name one NativePHP file.
 *
 * @return list<array{path: string, ships: string, becomes: string}>
 */
function hunksInFile(string $patch, string $file): array
{
    $parts = preg_split('/^(?=@@ )/m', $file);
    $parts = is_array($parts) ? $parts : [$file];
    $pair = (string) array_shift($parts);

    if (preg_match('~\A--- a/(\S+)\n\+\+\+ b/(\S+)\n\z~', $pair, $named) !== 1 || $named[1] !== $named[2]) {
        refuse(WHEN_A_PATCH_IS_NOT_ONE, $patch, sprintf('`%s` is not a `--- a/` and `+++ b/` pair naming one file', trim($pair)));
    }

    if (! str_starts_with($named[1], WHAT_A_PATCH_MAY_REWRITE) || str_contains($named[1], '..')) {
        refuse(WHEN_A_PATCH_IS_NOT_ONE, $patch, sprintf('`%s` is not a file under `%s`', $named[1], WHAT_A_PATCH_MAY_REWRITE));
    }

    $hunks = [];

    foreach ($parts as $hunk) {
        $hunks[] = ['path' => $named[1], ...hunk($patch, $hunk)];
    }

    return $hunks;
}

/**
 * The lines one hunk matches and what they become.
 *
 * A blank line counts as an empty context line, as `patch` reads one, so an
 * editor that strips the space from it leaves the patch as it was.
 *
 * @return array{ships: string, becomes: string}
 */
function hunk(string $patch, string $hunk): array
{
    if (preg_match(A_HUNK, $hunk, $parsed, PREG_UNMATCHED_AS_NULL) !== 1) {
        refuse(WHEN_A_PATCH_IS_NOT_ONE, $patch, sprintf('`%s` is not a hunk header', trim($hunk)));
    }

    [, $header, $shipsCounted, $becomesCounted, $lines] = $parsed;
    $ships = [];
    $becomes = [];

    foreach (explode("\n", str_ends_with($lines, "\n") ? mb_substr($lines, 0, -1) : $lines) as $line) {
        [$kind, $text] = lineIn($patch, $header, $line);
        $ships = $kind === '+' ? $ships : [...$ships, $text];
        $becomes = $kind === '-' ? $becomes : [...$becomes, $text];
    }

    $counted = count($ships) === (int) ($shipsCounted ?? '1') && count($becomes) === (int) ($becomesCounted ?? '1');

    if (! $counted || $ships === [] || $ships === $becomes) {
        refuse(WHEN_A_PATCH_IS_NOT_ONE, $patch, sprintf('the hunk at `%s` does not carry the lines its header counts, or has none to match, or changes none', $header));
    }

    return ['ships' => implode('', $ships), 'becomes' => implode('', $becomes)];
}

/**
 * Which side of a hunk a line is on, and the line it stands for.
 *
 * @return array{string, string}
 */
function lineIn(string $patch, string $header, string $line): array
{
    $kind = $line === '' ? ' ' : mb_substr($line, 0, 1);

    if (! in_array($kind, [' ', '-', '+'], strict: true)) {
        refuse(WHEN_A_PATCH_IS_NOT_ONE, $patch, sprintf('`%s`, in the hunk at `%s`, is on no side of it', $line, $header));
    }

    return [$kind, sprintf("%s\n", mb_substr($line, 1))];
}

/** Whether a file holds these lines as whole lines, so a match never starts mid-line. */
function holds(string $source, string $lines): bool
{
    return str_contains(sprintf("\n%s", $source), sprintf("\n%s", $lines));
}

/** The file with every whole-line run of one set of lines replaced by another. */
function rewrite(string $source, string $from, string $to): string
{
    return mb_substr(str_replace(sprintf("\n%s", $from), sprintf("\n%s", $to), sprintf("\n%s", $source)), 1);
}

/** What a hunk's result is known by: the file it is made to, and what it writes there. */
function resultOf(string $where, string $becomes): string
{
    return sprintf("%s\n%s", $where, $becomes);
}

/**
 * Each file a hunk is made to, with what to say where the line is not in it.
 *
 * The package's own file, unless this is the bundle's copy, and the installed
 * project's copy of it where there is one.
 *
 * @return list<array{path: string, file_is_not_there: string, line_has_moved: string}>
 */
function whereItIsMade(string $where): array
{
    $made = [];

    $leftOutOfThisCopy = str_starts_with($where, sprintf('%s/', WHAT_THE_BUNDLE_LEAVES_OUT))
        && ! is_dir(sprintf('%s/../%s', __DIR__, WHAT_THE_BUNDLE_LEAVES_OUT));

    if (! $leftOutOfThisCopy) {
        $made[] = [
            'path' => sprintf('%s/../%s', __DIR__, $where),
            'file_is_not_there' => WHEN_THE_FILE_IS_NOT_THERE,
            'line_has_moved' => WHEN_THE_LINE_HAS_MOVED,
        ];
    }

    foreach (WHERE_AN_INSTALL_COPIES_A_TEMPLATE as $template => $installed) {
        $isUnderIt = str_starts_with($where, sprintf('%s/', $template));

        if ($isUnderIt && is_dir(sprintf('%s/../%s', __DIR__, $installed))) {
            $made[] = [
                'path' => sprintf('%s/../%s%s', __DIR__, $installed, mb_substr($where, mb_strlen($template))),
                'file_is_not_there' => WHEN_THE_INSTALLED_COPY_DIFFERS,
                'line_has_moved' => WHEN_THE_INSTALLED_COPY_DIFFERS,
            ];
        }
    }

    return $made;
}

$patches = patchesIn(WHERE_THE_PATCHES_ARE);

if ($patches === []) {
    refuse(WHEN_A_PATCH_IS_NOT_ONE, sprintf('%s%s', __DIR__, WHERE_THE_PATCHES_ARE), 'it holds no patches');
}

$hunks = [];
$results = [];

foreach ($patches as $patch) {
    $everyMatch = meansEveryMatch($patch);

    foreach (hunksIn($patch) as $hunk) {
        $hunks[] = [...$hunk, 'patch' => basename($patch), 'every_match' => $everyMatch];
        $results[resultOf($hunk['path'], $hunk['becomes'])] = true;
    }
}

$earlier = [];

foreach (patchesIn(WHERE_EARLIER_HUNKS_ARE) as $patch) {
    foreach (hunksIn($patch) as ['path' => $where, 'ships' => $was, 'becomes' => $becomes]) {
        if (! array_key_exists(resultOf($where, $becomes), $results)) {
            refuse(WHEN_A_PATCH_IS_NOT_ONE, $patch, 'a hunk in it ends in lines no current hunk writes, so nothing moves a package on from it');
        }

        $earlier[resultOf($where, $becomes)] = $was;
    }
}

$rewritten = 0;

foreach ($hunks as ['path' => $where, 'ships' => $ships, 'becomes' => $becomes, 'patch' => $patch, 'every_match' => $everyMatch]) {
    $was = array_key_exists(resultOf($where, $becomes), $earlier) ? $earlier[resultOf($where, $becomes)] : $ships;

    foreach (whereItIsMade($where) as ['path' => $path, 'file_is_not_there' => $fileIsNotThere, 'line_has_moved' => $lineHasMoved]) {
        if (! file_exists($path)) {
            refuse($fileIsNotThere, $path, $patch);
        }

        $source = file_get_contents($path);

        if (! is_string($source)) {
            refuse("patch_nativephp: could not read %s.\n", $path);
        }

        if (holds($source, $becomes)) {
            continue;
        }

        $from = holds($source, $was) ? $was : $ships;

        if (! holds($source, $from)) {
            refuse($lineHasMoved, $path, $patch);
        }

        $places = placesHolding($source, $from);

        if ($places > 1 && ! $everyMatch) {
            refuse(WHEN_THE_LINES_APPEAR_MORE_THAN_ONCE, $path, $patch, (string) $places);
        }

        file_put_contents($path, rewrite($source, $from, $becomes));

        $rewritten++;
    }
}

if ($rewritten > 0) {
    fwrite(STDOUT, sprintf("patch_nativephp: %d line(s) rewritten in NativePHP.\n", $rewritten));
}
