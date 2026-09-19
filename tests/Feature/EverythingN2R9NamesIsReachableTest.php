<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Category;
use Modules\Kernel\Api\Check;
use Modules\Kernel\Api\Conclusion;
use Modules\Kernel\Api\Finding;
use Modules\Kernel\Api\Findings;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Overall;
use Modules\Kernel\Api\Report;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\WhatTheCheckSaid;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\HowThisStackIs;
use Modules\Operator\Internal\Screens\WhatStoppedComingIn;
use Modules\Operator\Internal\ViewModels\WhichFamilyToRead;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AStackThatWasAsked;
use Tests\Support\Fakes\StacksInMemory;

// "Stuck downloads, provider health, disk pressure and VPN verification
// MUST each be reachable."
//
// Four subjects in one sentence, and they arrive by two different roads. Three
// of them are families of the diagnostic run and are reached by narrowing the
// health screen to one; the fourth has no finding behind it at all and needed a
// screen and a route of its own.
//
// That asymmetry is why this file exists. Each half is already tested where it
// lives — `SeeingHowAStackIsTest` for narrowing, `SeeingWhatStoppedComingInTest`
// for the screen — and neither of them is about this requirement. A reader
// asking *is the family reachable* would have to know that `Category::Vpn` happens to be a
// case and that a route happens to be registered, which is knowledge held
// nowhere. Remove the VPN family from `Category` and every test above stays
// green while one quarter of a requirement silently stops being true.

/** The machine each of the four is reached on. */
function theStackTheFourAreReachedOn(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** A run with one finding in each family this requirement names. */
function aRunTouchingEachOfTheFour(): Report
{
    $findings = [];

    foreach ([Category::Providers, Category::Storage, Category::Vpn] as $family) {
        $findings[] = Finding::of(
            Check::of(sprintf('%s.something', $family->value)),
            $family,
            'Something worth saying',
            Conclusion::Warned,
            WhatTheCheckSaid::nothingWrong(),
        );
    }

    return Report::of(Overall::Degraded, Findings::of(...$findings));
}

/** The health screen, holding a run that has something in each family. */
function theScreenTheFourAreReachedFrom(): HowThisStackIs
{
    $stack = theStackTheFourAreReachedOn();
    $keychain = AKeychainInMemory::working();
    $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());

    $screen = new HowThisStackIs(
        AStackThatWasAsked::saying(aRunTouchingEachOfTheFour()),
        $keychain,
        StacksInMemory::holding($stack),
    );
    $screen->setParams(['stack' => $stack->id()->stored()]);

    return $screen;
}

it('N2-R9 — provider health, disk pressure and VPN verification are each reachable', function (): void {
    // Reached by narrowing the run to one family. The chip is offered only
    // where that family has something to say, which is what makes the row worth
    // reading — so the run above puts one finding in each of the three.
    $offered = array_map(
        static fn(WhichFamilyToRead $family): string => $family->family,
        theScreenTheFourAreReachedFrom()->families(),
    );

    $unreachable = [];

    foreach ([Category::Providers, Category::Storage, Category::Vpn] as $family) {
        if (! in_array($family->value, $offered, strict: true)) {
            $unreachable[] = $family->value;
        }
    }

    expect($unreachable)->toBe([], sprintf(
        "N2-R9 names these and nothing on the health screen reaches them:\n  %s\n\n"
        . 'Each is a family of the diagnostic run, and the chip that narrows to one is '
        . "offered only where that family has something to say.\n",
        implode("\n  ", $unreachable),
    ));
});

it('N2-R9 — stuck downloads are reachable, and not by narrowing a run', function (): void {
    // The fourth, and the one that is not a family. Nothing in the diagnostic
    // report is about a download that stopped — a stack can pass every check
    // while four titles the house asked for sit at a stage nothing will move
    // them past — so this arrives as a screen of its own with a route of its
    // own, and the health screen points at it.
    $screen = theScreenTheFourAreReachedFrom();

    expect(Category::tryFrom('stuck'))->toBeNull(
        'If stuck downloads became a health family, this requirement would be met by '
        . 'narrowing and the separate screen would be the thing to remove — deliberately, '
        . 'not by leaving both.',
    );

    $resolved = NativeRouter::resolve($screen->goes()->stuck());

    expect($resolved['class'] ?? null)->toBe(WhatStoppedComingIn::class);
});
