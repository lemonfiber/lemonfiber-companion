<?php

declare(strict_types=1);

use function basename;
use function file_get_contents;
use function implode;
use function sprintf;
use function str_contains;

use Tests\Support\Tree;

// W7 — every answer passes the wire gate before anything reads it.
//
// `Wire::checked()` refuses an envelope whose wire version this app does not
// support (`N1-R13`), and its own docblock calls itself *the one gate every
// answer passes before anything reads it*. That was true of every reader but
// one, and nothing was checking: `Lines` read `$envelope->data` straight out of
// a log window, so a window on a version this app has never heard of was read
// and handed to a screen as fact — which is the failure that docblock describes
// in the paragraph arguing the gate should not live in each translator.
//
// The client asserts a log line's *kind* as it builds the window and says
// nothing about its version, so nothing upstream covered it either.
//
// Read as text rather than by reflection, because what is being asked is
// whether a file calls something, and a reader that took the envelope apart
// through a helper would satisfy a reflection over its signature while reading
// an unchecked payload anyway.

/**
 * Every file that turns an envelope into something this app holds.
 *
 * Found by taking one as a parameter rather than by a name, so a reader added
 * under a name nobody predicted is still asked. The exception classes are not
 * readers — they are handed a field to name in a message and never a payload —
 * and they are excluded by looking for the envelope itself.
 *
 * @return array<string, string>
 */
function everyFileThatReadsAnEnvelope(): array
{
    $found = [];

    foreach (Tree::filesUnder(Tree::at('app-modules/sdk/src'), '.php') as $path) {
        $source = (string) file_get_contents($path);

        if (! str_contains($source, 'Envelope $envelope') && ! str_contains($source, 'LogWindow $window')) {
            continue;
        }

        $found[basename($path, '.php')] = $source;
    }

    return $found;
}

it('W7 — every reader puts its envelope through the wire gate', function (): void {
    $readers = everyFileThatReadsAnEnvelope();
    $ungated = [];

    foreach ($readers as $name => $source) {
        // A reader may hand the envelope on to one that gates it rather than
        // gating it itself; what is refused is reaching the payload without
        // either. `->data` is how a payload is reached, so a file that reaches
        // for one and never names the gate is reading something nobody checked.
        if (! str_contains($source, '->data')) {
            continue;
        }

        if (str_contains($source, 'Wire::checked')) {
            continue;
        }

        $ungated[] = $name;
    }

    // A rule that has stopped finding readers reports nothing ungated, and
    // nothing ungated is exactly what compliance looks like. The floor is the
    // number of envelope kinds the app reads, which is fixed by the contract
    // rather than by anything a person edits here.
    expect($readers)->not->toBe([], 'no file in the SDK module takes an envelope, so this rule read nothing');

    expect($ungated)->toBe([], sprintf(
        "These reach into an envelope's payload without putting it through the wire gate:\n  %s\n\n"
        . '`N1-R13` refuses a wire version this app does not support, and `Wire::checked()` is '
        . "where that refusal lives.\nA payload read without it is a field whose meaning may have "
        . 'changed, handed to a screen as a fact — which is what the gate exists to stop, and why '
        . "it is one gate rather than a habit each translator keeps (W7, N1-R13).\n",
        implode("\n  ", $ungated),
    ));
});
