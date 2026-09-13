<?php

declare(strict_types=1);

use Modules\Connection\Api\HowThePairingWent;
use Modules\Connection\Api\HowTheSignInWent;
use Modules\Connection\Api\WhereTheCodeGot;
use Modules\Kernel\Api\Category;
use Modules\Kernel\Api\Conclusion;
use Modules\Kernel\Api\HowItWasRead;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Overall;
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
    return [
        HowTheSignInWent::class => aPairPerCase(
            HowTheSignInWent::cases(),
            static fn(HowTheSignInWent $went): array => [$went->said(), $went->remedy()],
        ),
        // `NotYet` is skipped: what a screen says before anything has happened
        // is what that screen is *for*, and the two pairing roads are for
        // different things — so each screen spells its own opening pair and
        // this enum answers only once there is an outcome.
        HowThePairingWent::class => aPairPerCase(
            array_values(array_filter(
                HowThePairingWent::cases(),
                static fn(HowThePairingWent $went): bool => ! $went->isNotYet(),
            )),
            static fn(HowThePairingWent $went): array => [$went->said(), $went->remedy()],
        ),
        // What the screen on each road is for, which no outcome can name.
        HowItWasRead::class => aPairPerCase(
            HowItWasRead::cases(),
            static fn(HowItWasRead $road): array => [$road->askedFor(), $road->howToStart()],
        ),
        WhyNothingWasScanned::class => aPairPerCase(
            WhyNothingWasScanned::cases(),
            static fn(WhyNothingWasScanned $why): array => [$why->saidOnTheScreen(), $why->remedy()],
        ),
        // Not derived on the enum itself — `HowTheSignInWent` and
        // `WhatTheStackTurnedOutToBe` both build these from an obstacle, so the
        // pair is held here rather than in one of the two that happens to.
        Obstacle::class => aPairPerCase(
            Obstacle::cases(),
            static fn(Obstacle $why): array => [
                sprintf('connection.%s', $why->value),
                sprintf('connection.%s_action', $why->value),
            ],
        ),
        WhereTheCodeGot::class => aPairPerCase(
            WhereTheCodeGot::cases(),
            static fn(WhereTheCodeGot $got): array => [$got->saidUnderTheField()],
        ),
        Conclusion::class => aPairPerCase(
            Conclusion::cases(),
            static fn(Conclusion $conclusion): array => [$conclusion->saidOnTheScreen()],
        ),
        Overall::class => aPairPerCase(
            Overall::cases(),
            static fn(Overall $overall): array => [$overall->saidOnTheScreen()],
        ),
        Category::class => aPairPerCase(
            Category::cases(),
            static fn(Category $category): array => [$category->saidOnTheScreen()],
        ),
    ];
}

/**
 * Every key a set of cases builds, flattened.
 *
 * One helper rather than eight loops, which the complexity gate asked for and
 * which reads better anyway: what differs per enum is *which* keys a case
 * builds, and that is now the only thing written per entry above.
 *
 * @template TCase of UnitEnum
 *
 * @param list<TCase>                $cases
 * @param Closure(TCase): list<string> $keys
 *
 * @return list<string>
 */
function aPairPerCase(array $cases, Closure $keys): array
{
    return array_merge(...array_map($keys, $cases));
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

    foreach (HowItWasRead::cases() as $road) {
        expect($road->askedFor())->toBe(sprintf('connection.%s_the_code', $road->value), $road->name)
            ->and($road->howToStart())->toBe(sprintf('connection.%s_the_code_action', $road->value), $road->name);
    }

    foreach (WhereTheCodeGot::cases() as $got) {
        expect($got->saidUnderTheField())->toBe(sprintf('connection.the_code_is_%s', $got->value), $got->name);
    }

    foreach (Conclusion::cases() as $conclusion) {
        expect($conclusion->saidOnTheScreen())
            ->toBe(sprintf('health.conclusion.%s', $conclusion->value), $conclusion->name);
    }

    foreach (Overall::cases() as $overall) {
        expect($overall->saidOnTheScreen())
            ->toBe(sprintf('health.overall.%s', $overall->value), $overall->name);
    }

    foreach (Category::cases() as $category) {
        expect($category->saidOnTheScreen())
            ->toBe(sprintf('health.category.%s', $category->value), $category->name);
    }

    foreach (WhyNothingWasScanned::cases() as $why) {
        expect($why->saidOnTheScreen())->toBe(sprintf('connection.%s', $why->value), $why->name)
            ->and($why->remedy())->toBe(sprintf('connection.%s_action', $why->value), $why->name);
    }
});
