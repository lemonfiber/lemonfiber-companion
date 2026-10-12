<?php

declare(strict_types=1);

use Tests\Support\OurCode;

// F21 — a tap that hands a value is written by `ATap`, in PHP as in a
// template.
//
// A presenter that formats `"open('%s')"` around a name the stack wrote has
// put that name into the expression the package parses, where a quote in it
// closes the argument and `addslashes()` turns it into a different value
// rather than a safe one. A call written by hand is a format string or a
// concatenation that opens on a method and a parenthesis.

it('writes no tap by formatting or joining a value into it', function (): void {
    $byHand = [];
    $looked = 0;

    foreach (OurCode::sourceFiles() as $file) {
        $source = (string) file_get_contents($file);
        $looked++;

        foreach (explode("\n", $source) as $number => $line) {
            if (preg_match('/[\'"][a-z]\w*\(\\\\?[\'"]?%[sd]|[\'"][a-z]\w*\(\\\\?[\'"]?[\'"]\s*\./', $line) === 1) {
                $byHand[] = sprintf('%s:%d %s', $file, $number + 1, trim($line));
            }
        }
    }

    expect($looked)->toBeGreaterThan(100, 'no source file was read, so this rule read nothing')
        ->and($byHand)->toBe([], sprintf(
            "These write a tap by hand, with a value inside the expression the package parses:\n  %s\n\n"
            . "Write it with `ATap::calling('method', \$value)`.\n",
            implode("\n  ", $byHand),
        ));
});
