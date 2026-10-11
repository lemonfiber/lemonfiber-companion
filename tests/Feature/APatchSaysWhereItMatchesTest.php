<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

/** The file every patch in this test rewrites, which holds the same two lines in two places. */
const THE_FILE_A_PATCH_REWRITES = 'vendor/nativephp/mobile/src/Twice.php';

/** What that file holds before any patch: one line, written twice. */
const WHAT_THE_PACKAGE_SHIPS = "<?php\n\nfunction first()\n{\n    return 'same';\n}\n\nfunction second()\n{\n    return 'same';\n}\n";

/**
 * A tree holding the patch script, one patch and the file it rewrites, and what running the script there came to.
 *
 * @return array{exit: int|null, said: string, holds: string}
 */
function patchedWith(string $prose, string $hunk): array
{
    $tree = sprintf('%s/patch-%s', sys_get_temp_dir(), uniqid(more_entropy: true));
    mkdir(sprintf('%s/scripts/patch_nativephp', $tree), recursive: true);
    mkdir(dirname(sprintf('%s/%s', $tree, THE_FILE_A_PATCH_REWRITES)), recursive: true);
    copy(base_path('scripts/patch_nativephp.php'), sprintf('%s/scripts/patch_nativephp.php', $tree));
    file_put_contents(sprintf('%s/%s', $tree, THE_FILE_A_PATCH_REWRITES), WHAT_THE_PACKAGE_SHIPS);
    file_put_contents(
        sprintf('%s/scripts/patch_nativephp/01-a-patch.patch', $tree),
        sprintf("%s\n\n--- a/%2\$s\n+++ b/%2\$s\n%3\$s", $prose, THE_FILE_A_PATCH_REWRITES, $hunk),
    );

    $run = new Process([PHP_BINARY, 'scripts/patch_nativephp.php'], $tree);
    $run->run();

    $ran = [
        'exit' => $run->getExitCode(),
        'said' => $run->getErrorOutput(),
        'holds' => (string) file_get_contents(sprintf('%s/%s', $tree, THE_FILE_A_PATCH_REWRITES)),
    ];
    new Filesystem()->deleteDirectory($tree);

    return $ran;
}

it('refuses a hunk whose lines appear in more than one place, naming the patch and how many, and writes nothing', function (): void {
    $ran = patchedWith('Says something else.', "@@ -5 +5 @@\n-    return 'same';\n+    return 'other';\n");

    expect($ran['exit'])->toBe(1)
        ->and($ran['said'])->toContain('01-a-patch.patch rewrites appear 2 times in')
        ->and($ran['said'])->toContain('patch_nativephp: replace-all')
        ->and($ran['holds'])->toBe(WHAT_THE_PACKAGE_SHIPS);
});

it('rewrites every place a hunk\'s lines appear where its patch says it means every place', function (): void {
    $ran = patchedWith("Says something else, everywhere.\npatch_nativephp: replace-all", "@@ -5 +5 @@\n-    return 'same';\n+    return 'other';\n");

    expect($ran['exit'])->toBe(0)
        ->and(mb_substr_count($ran['holds'], "return 'other';"))->toBe(2)
        ->and($ran['holds'])->not->toContain("return 'same';");
});

it('rewrites the one place a hunk with enough context names', function (): void {
    $ran = patchedWith('Says something else, once.', "@@ -9,2 +9,2 @@\n {\n-    return 'same';\n+    return 'other';\n");

    expect($ran['exit'])->toBe(1);

    $named = patchedWith('Says something else, once.', "@@ -8,3 +8,3 @@\n function second()\n {\n-    return 'same';\n+    return 'other';\n");

    expect($named['exit'])->toBe(0)
        ->and($named['holds'])->toBe(str_replace("second()\n{\n    return 'same';", "second()\n{\n    return 'other';", WHAT_THE_PACKAGE_SHIPS));
});

it('reads the opt-in only from the patch\'s own prose, never from a line it writes', function (): void {
    $ran = patchedWith('Says something else.', "@@ -5 +5,2 @@\n-    return 'same';\n+    return 'other';\n+    // patch_nativephp: replace-all\n");

    expect($ran['exit'])->toBe(1)
        ->and($ran['holds'])->toBe(WHAT_THE_PACKAGE_SHIPS);
});
