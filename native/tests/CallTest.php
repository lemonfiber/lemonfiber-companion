<?php

declare(strict_types=1);

use Lemonfiber\Native\Call;

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
    $manifest = json_decode(
        (string) file_get_contents(dirname(__DIR__) . '/nativephp.json'),
        associative: true,
        flags: JSON_THROW_ON_ERROR,
    );

    $declared = array_column($manifest['bridge_functions'], 'name');
    sort($declared);

    $named = array_map(static fn(Call $call): string => $call->value, Call::cases());
    sort($named);

    expect($named)->toBe($declared);
});

it('prefixes every call with the namespace the manifest declares', function (): void {
    // The bridge routes on the prefix, so a name that lost it would be looked
    // for in somebody else's namespace — and found, if they happen to have one
    // spelled the same.
    $manifest = json_decode(
        (string) file_get_contents(dirname(__DIR__) . '/nativephp.json'),
        associative: true,
        flags: JSON_THROW_ON_ERROR,
    );

    foreach (Call::cases() as $call) {
        expect($call->value)->toStartWith($manifest['namespace'] . '.');
    }
});
