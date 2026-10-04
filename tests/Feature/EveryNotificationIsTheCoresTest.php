<?php

declare(strict_types=1);

use Tests\Support\OurCode;
use Tests\Support\Tree;

// Every notification the app shows is one the core decided on.
//
// `Notification` is built only from a `WhatTheCoreDecided`, and that type is
// made from a string by one named constructor. So the one call that could turn
// a sentence of this app's into an alert is that constructor, called anywhere
// but where the wire is read. Nothing reads a decision off the wire yet, so no
// call site exists at all; the day one is written, it belongs to the module
// that reads the stack.

/** Where the stack's answers are read, and so the one place a decision of the core's may be made. */
const WHERE_THE_WIRE_IS_READ = 'app-modules/sdk/src/';

/** The calls that make an alert. */
const WHAT_MAKES_AN_ALERT = [['WhatTheCoreDecided', 'toSay'], ['Notification', 'fromTheCore']];

/**
 * The calls one file makes that make an alert, read from the code rather than the comments.
 *
 * @return list<string>
 */
function alertsOneFileMakes(string $file): array
{
    $tokens = array_values(array_filter(
        PhpToken::tokenize((string) file_get_contents($file)),
        static fn(PhpToken $token): bool => ! $token->isIgnorable(),
    ));
    $found = [];

    foreach (array_keys($tokens) as $at) {
        $call = [$tokens[$at]->text, $tokens[$at + 1]->text ?? '', $tokens[$at + 2]->text ?? ''];

        foreach (WHAT_MAKES_AN_ALERT as [$type, $method]) {
            if (str_ends_with($call[0], $type) && $call[1] === '::' && $call[2] === $method) {
                $found[] = sprintf('%s::%s', $type, $method);
            }
        }
    }

    return $found;
}

/**
 * Every place outside the reader of the wire that makes an alert.
 *
 * @return list<string>
 */
function alertsMadeOutsideTheReaderOfTheWire(): array
{
    $found = [];

    foreach (OurCode::sourceFiles() as $file) {
        if (str_contains($file, WHERE_THE_WIRE_IS_READ)) {
            continue;
        }

        foreach (alertsOneFileMakes($file) as $call) {
            $found[] = sprintf('%s calls %s', str_replace(sprintf('%s/', Tree::root()), '', $file), $call);
        }
    }

    return $found;
}

it('makes no alert anywhere but where the stack\'s answers are read', function (): void {
    expect(OurCode::sourceFiles())->not->toBe([])
        ->and(is_dir(Tree::at(WHERE_THE_WIRE_IS_READ)))->toBeTrue()
        ->and(alertsMadeOutsideTheReaderOfTheWire())->toBe([]);
});
