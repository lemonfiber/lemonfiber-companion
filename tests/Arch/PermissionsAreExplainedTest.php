<?php

declare(strict_types=1);

use Modules\Kernel\Api\Permission;
use Tests\Support\Catalogue;
use Tests\Support\Manifests;

// The app explains itself before the platform interrupts.
//
// The platform's own prompt is one line the app does not write, shown at the
// moment it is least welcome, and on iOS it is shown exactly once — decline it
// and there is no second chance to explain. The app's own words go
// first, which means a sentence has to exist whether or not anybody has been
// asked yet.
//
// The alternative is the half that is easy to write down and hard to keep: every
// permission is optional, and each declined one has a *working alternative*.
// `Permission::hasAnAlternative()` already says all three do. This is what stops
// that being a promise nobody can read — an alternative the operator is never
// told about is not offered.
//
// Both are asked over `Permission::cases()` rather than over the catalogue, so
// the day a fourth permission is added the failure names it. Adding a case and
// leaving the sentences for later is exactly the commit this refuses.
//
// The keys come from the case rather than being built here, so that this file
// and whichever adapter reads the line are spelling the same string. A rule that
// builds its own copy of a key is a rule that can pass while the screen shows
// the key itself.

it('N4-R2 — every permission is explained in the app\'s own words', function (): void {
    $missing = [];

    foreach (Catalogue::locales() as $locale) {
        $lines = Catalogue::all($locale);

        foreach (Permission::cases() as $permission) {
            $key = $permission->reason();

            if (($lines[$key] ?? '') === '') {
                $missing[] = sprintf('%s — %s', $key, $locale);
            }
        }
    }

    sort($missing);

    expect($missing)->toBe([], sprintf(
        "These permissions have no sentence saying what they are for:\n  %s\n\n"
        . 'The platform prompt is one line the app does not write, shown when it is '
        . 'least welcome, and on iOS it is shown once — decline it and there is no '
        . "second chance to explain.\n"
        . 'Add the line to every locale under `lang/<locale>/device.php` (N4-R2).',
        implode("\n  ", $missing),
    ));
});

it('N4-R3 — every permission says what still works without it', function (): void {
    $missing = [];

    foreach (Catalogue::locales() as $locale) {
        $lines = Catalogue::all($locale);

        foreach (Permission::cases() as $permission) {
            if (! $permission->hasAnAlternative()) {
                continue;
            }

            $key = $permission->alternative();

            if (($lines[$key] ?? '') === '') {
                $missing[] = sprintf('%s — %s', $key, $locale);
            }
        }
    }

    sort($missing);

    expect($missing)->toBe([], sprintf(
        "These permissions claim a working alternative and never say what it is:\n  %s\n\n"
        . '`Permission::hasAnAlternative()` answers true for each of these, which is the '
        . 'app promising `N4-R3` is kept. An alternative the operator is never told '
        . "about is not offered — it is the same screen with a different excuse.\n"
        . 'Add the line to every locale under `lang/<locale>/device.php` (N4-R3).',
        implode("\n  ", $missing),
    ));
});

it('N4-R16 — the local-network purpose is declared, and is not a placeholder', function (): void {
    // The one purpose string the platform reads rather than the app: iOS shows
    // `NSLocalNetworkUsageDescription` in its own dialog, and a build without it
    // is rejected at review. A build carrying a marker somebody left for
    // themselves is not rejected, which is the case worth a rule.
    //
    // Read from this application's own plugin manifest, because that is where it
    // lives: `nativephp/mobile` turns a plugin's `ios.info_plist` into the
    // built app's `Info.plist`, so the string the operator sees is the string in
    // that file.
    $said = Manifests::localNetworkPurpose();

    // `toBeTrue` on the comparison rather than `->not->toBe('')`, because the
    // latter renders its message inline with the expectation and the newlines
    // are lost — the reader gets one run-on sentence at exactly the moment they
    // need instructions.
    expect($said !== '')->toBeTrue(implode(PHP_EOL, [
        'No NSLocalNetworkUsageDescription is declared.',
        '',
        'iOS shows this sentence in its own dialog before it will let the app reach',
        'the local network at all, and a build without one is rejected at review.',
        'Declare it under `ios.info_plist` in `bridge/nativephp.json` (N4-R16).',
    ]));

    expect(Manifests::readsLikeAPlaceholder($said))->toBeFalse(sprintf(
        "The local-network purpose reads like a placeholder:\n  %s\n\n"
        . 'A build with `TODO` in this string is not rejected by review — it ships, and '
        . 'the operator is asked to allow something by a sentence written for a '
        . "developer.\n"
        . 'Say what the network is used for in the operator\'s terms (N4-R16).',
        $said,
    ));
});

it('N4-R16 — the purpose says what the network is used for, in the operator\'s terms', function (): void {
    // The half a placeholder check cannot reach. "Required for the app to
    // function" is not a placeholder and is not an explanation either; what the
    // requirement asks for is what the network is *used for*.
    //
    // Checked by asking that the sentence name the thing at the other end. That
    // is a narrow test and deliberately so — it cannot judge prose, and a rule
    // that claimed to would be the kind that gets argued with and then removed.
    // What it can do is fail the sentence that names nothing.
    $said = Manifests::localNetworkPurpose();

    // `str_contains` rather than `toContain`, which takes variadic *needles*:
    // a failure message passed as a second argument becomes a second thing the
    // string must contain, and the assertion then fails on a sentence that is
    // perfectly fine. It did, on the first run of this file.
    expect(str_contains(mb_strtolower($said), 'stack'))->toBeTrue(implode(PHP_EOL, [
        'The local-network purpose does not mention the stack.',
        '',
        sprintf('  %s', $said),
        '',
        'iOS shows this to somebody deciding whether to allow it. A sentence that does',
        'not say what is at the other end of the network asks them to take it on trust',
        "(N4-R16).",
    ]));
});
