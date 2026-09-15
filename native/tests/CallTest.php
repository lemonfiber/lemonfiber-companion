<?php

declare(strict_types=1);

use Lemonfiber\Native\Call;

/**
 * The plugin's own manifest, which is what the NativePHP builder reads.
 *
 * A function rather than a line in each test, because `json_decode` with
 * `JSON_THROW_ON_ERROR` throws a checked exception and a checked exception
 * raised inside a Pest closure is one nothing declares. Named for this package:
 * a test file's helpers land in the global namespace beside every other root
 * suite's (`G10`).
 *
 * @return array{namespace: string, bridge_functions: list<array{name: string}>}
 */
function theNativeManifest(): array
{
    /** @var array{namespace: string, bridge_functions: list<array{name: string}>} $said */
    $said = json_decode(
        (string) file_get_contents(sprintf('%s/nativephp.json', dirname(__DIR__))),
        associative: true,
        flags: JSON_THROW_ON_ERROR,
    );

    return $said;
}

it('names exactly what the manifest declares', function (): void {
    // The check that makes `Call` worth having. The names are a vocabulary
    // spread across four files in three languages, and this is the only pair a
    // PHP test can hold together — but it is the pair that matters, because the
    // manifest is what the NativePHP builder reads when it wires the Kotlin and
    // the Swift.
    //
    // A disagreement is invisible at runtime: the bridge answers an unknown
    // function the way it answers a disconnected device, which reads here as
    // "the window is not protected". The failure would be a capture guard that
    // silently does nothing, on a handset, where nobody is watching a test run.
    $declared = array_column(theNativeManifest()['bridge_functions'], 'name');
    sort($declared);

    $named = array_map(static fn(Call $call): string => $call->value, Call::cases());
    sort($named);

    expect($named)->toBe($declared);
});

it('prefixes every call with the namespace the manifest declares', function (): void {
    // The bridge routes on the prefix, so a name that lost it would be looked
    // for in somebody else's namespace — and found, if they happen to have one
    // spelled the same.
    $prefix = sprintf('%s.', theNativeManifest()['namespace']);

    foreach (Call::cases() as $call) {
        expect($call->value)->toStartWith($prefix);
    }
});
