<?php

declare(strict_types=1);

use Modules\Connection\Api\HowThePairingWent;
use Modules\Connection\Api\HowTheSignInWent;
use Modules\Connection\Api\WhereTheCodeGot;
use Modules\Kernel\Api\WhyNothingWasScanned;
use Tests\Support\Catalogue;

// `L7`'s blind half — the keys no regex can see.
//
// `EveryKeyTheAppNamesResolvesTest` reads catalogue keys out of the *text* of
// the sources, which is the right way to catch the ones nothing executes. It
// says, correctly, that the cure for a mistyped literal is usually to derive the
// key from a closed set instead — `Permission::reason()` builds
// `device.camera_reason` from the case, so there is one spelling and it cannot
// drift.
//
// Taking that cure removes the key from L7's sight. `sprintf('connection.%s',
// $this->value)` matches none of its three patterns, so an enum that gained a
// case and no catalogue line would pass every rule in the suite and show the
// operator `connection.the_new_case` on the glass.
//
// So each enum that builds its own keys is asked here, the way
// `PermissionsAreExplainedTest` asks it of `Permission`: every case, every
// locale, a line that is there and is not empty. The keys come from the case
// rather than being rebuilt in this file — a rule that builds its own copy of a
// key is a rule that can pass while the screen shows the key itself.
//
// **Adding an enum to the table below is the maintenance this asks for**, and it
// is deliberately a table rather than a scan: a scan would have to guess which
// methods return keys, and guessing wrong in the quiet direction is a rule that
// silently stops covering something.

/**
 * Every enum that derives catalogue keys, and the keys each case derives.
 *
 * @return array<string, list<string>> the enum's name => every key it builds
 */
function everyDerivedKey(): array
{
    // Every key is named before any is filled in, so a reader sees the whole
    // table at once and the analyser sees an array that always exists.
    $derived = [
        HowTheSignInWent::class => [],
        HowThePairingWent::class => [],
        WhyNothingWasScanned::class => [],
        WhereTheCodeGot::class => [],
    ];

    foreach (HowTheSignInWent::cases() as $went) {
        // Two per case, because `N1-R10` asks for what happened *and* what to do
        // about it, and a state with one of the pair is a screen half-written.
        $derived[HowTheSignInWent::class][] = $went->said();
        $derived[HowTheSignInWent::class][] = $went->remedy();
    }

    foreach (HowThePairingWent::cases() as $went) {
        // `NotYet` is skipped and only here: what a screen says before anything
        // has happened is what that screen is *for*, and the two pairing roads
        // are for different things — so each screen spells its own opening pair
        // and this enum answers only once there is an outcome.
        if ($went->isNotYet()) {
            continue;
        }

        $derived[HowThePairingWent::class][] = $went->said();
        $derived[HowThePairingWent::class][] = $went->remedy();
    }

    foreach (WhyNothingWasScanned::cases() as $why) {
        $derived[WhyNothingWasScanned::class][] = $why->saidOnTheScreen();
    }

    foreach (WhereTheCodeGot::cases() as $got) {
        $derived[WhereTheCodeGot::class][] = $got->saidUnderTheField();
    }

    return $derived;
}

it('L7 — every key an enum builds for itself is a line the catalogue holds', function (): void {
    $missing = [];

    foreach (Catalogue::locales() as $locale) {
        $lines = Catalogue::all($locale);

        foreach (everyDerivedKey() as $enum => $keys) {
            foreach ($keys as $key) {
                if (($lines[$key] ?? '') === '') {
                    $missing[] = sprintf('%s — %s (%s)', $key, $locale, $enum);
                }
            }
        }
    }

    sort($missing);

    expect($missing)->toBe([], sprintf(
        "These keys are built from a case and have no line behind them:\n  %s\n\n"
        . 'A derived key cannot be mistyped, which is why deriving it is the cure L7 '
        . 'recommends — but it can be built for a case nobody wrote a sentence for, and '
        . 'no regex over the sources can see that. The operator is shown the key. '
        . "Add the line to every locale under `lang/<locale>/`.\n",
        implode("\n  ", $missing),
    ));
});

it('a case value is the catalogue stem, so the two cannot drift apart', function (): void {
    // The property that makes the derivation worth having. Asserted rather than
    // assumed, because an enum could build `connection.<name>` from something
    // other than its own value — a `match`, a second table, a transformation —
    // and then be exactly the two-spellings-of-one-name this pattern removes.
    foreach (HowTheSignInWent::cases() as $went) {
        expect($went->said())->toBe(sprintf('connection.%s', $went->value), $went->name)
            ->and($went->remedy())->toBe(sprintf('connection.%s_action', $went->value), $went->name);
    }

    foreach (HowThePairingWent::cases() as $went) {
        // `NotYet` is exempt for the reason the table above gives: an outcome
        // cannot say what a screen is for, so it has no key and must not be
        // asked for one.
        if ($went->isNotYet()) {
            continue;
        }

        expect($went->said())->toBe(sprintf('connection.%s', $went->value), $went->name)
            ->and($went->remedy())->toBe(sprintf('connection.%s_action', $went->value), $went->name);
    }

    foreach (WhyNothingWasScanned::cases() as $why) {
        expect($why->saidOnTheScreen())->toBe(sprintf('connection.%s', $why->value), $why->name);
    }
});
