<?php

declare(strict_types=1);

use Bootstrap\Composition\NativePHP\OutOfTheOperatorsScreens;
use Illuminate\Http\Request;
use Modules\Household\Internal\Screens\WhatYouCanWatch;
use Modules\Kernel\Api\DeviceAuth;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\Locked;
use Modules\Stacks\Api\AStacksScreen;
use Modules\Wayfinding\Api\TheHouseholdsTabs;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\Fakes\ADeviceThatKnowsYou;
use Tests\Support\Fakes\AKeychainInMemory;

// Runs the composition root rather than reading it, so its mutants are judged
// here: see `scripts/mutation.php`.
pest()->group('holds:bootstrap/Composition');

// A member's session never reaches the operator's screens. Every operator
// screen about a stack the router serves is asked for on a phone holding a
// member's session for that stack, and the member's Home is what is built. The operator's session, and no session, get the screen asked for.

const THE_OPERATORS_HOUSE = 'a';
const A_MEMBERS_HOUSE = 'b';
const NOBODYS_HOUSE = 'c';

/** A stack this file signs into, by letter. Named for this file. */
function aHouseSignedInto(string $letter): StackId
{
    return StackId::of(Nonce::of(str_repeat($letter, Nonce::SHORTEST)));
}

/** A keychain holding the operator's session for one stack and a member's for another. */
function aKeychainHoldingBothSessions(): AKeychainInMemory
{
    $keychain = AKeychainInMemory::working();
    $keychain->keep(aHouseSignedInto(THE_OPERATORS_HOUSE), Session::of('a-session-not-a-secret'), Whose::theOperator());
    $keychain->keep(aHouseSignedInto(A_MEMBERS_HOUSE), Session::of('a-session-not-a-secret'), Whose::member('robin'));

    return $keychain;
}

/**
 * Every screen the router serves whose route names a stack, by its route's pattern.
 *
 * @return array<string, string>
 */
function everyScreenAboutAStack(string $surface): array
{
    $screens = [];

    foreach (NativeRouter::registeredRoutes() as $pattern => $route) {
        $class = is_array($route) ? ($route['class'] ?? null) : null;

        if (is_string($pattern) && is_string($class) && str_starts_with($class, $surface) && str_contains($pattern, '{stack}')) {
            $screens[$pattern] = $class;
        }
    }

    return $screens;
}

/**
 * The operator's screens about a stack that a member is kept out of, by its route's pattern.
 *
 * @return array<string, string>
 */
function everyOperatorsScreenAMemberIsKeptFrom(): array
{
    return array_filter(
        everyScreenAboutAStack('Modules\\Operator\\'),
        static fn(string $class): bool => ! array_key_exists($class, OutOfTheOperatorsScreens::EVERY_SESSION_OPENS),
    );
}

/** The class built for one screen asked about one stack, on that keychain. */
function whatIsBuiltFor(SecureStorage $keychain, string $asked, StackId $stack): string
{
    $built = '';
    $guard = new OutOfTheOperatorsScreens($keychain, ['stack' => $stack->stored(), 'service' => 'sonarr'], static function (string $class) use (&$built): string {
        $built = $class;

        return $class;
    });

    $guard->screen($asked);

    return $built;
}

/** The path a route's pattern names for one stack. */
function thePathOf(string $pattern, StackId $stack): string
{
    return str_replace(['{stack}', '{service}'], [$stack->stored(), 'sonarr'], $pattern);
}

/** The screen a member lands on, Home, as the router serves the route for it. */
function whereAMemberLands(): string
{
    $route = NativeRouter::registeredRoutes()[TheHouseholdsTabs::Home->screen()->value] ?? [];

    return is_array($route) && is_string($route['class'] ?? null) ? $route['class'] : '';
}

/** What the application says it built for one path, on that keychain, with the device's lock as given. */
function whatTheApplicationBuiltFor(SecureStorage $keychain, string $path, ?DeviceAuth $device = null): string
{
    app()->instance(DeviceAuth::class, $device ?? ADeviceThatKnowsYou::unlocked());
    app()->instance(SecureStorage::class, $keychain);
    app()->instance('request', Request::create($path));

    return (string) app()->handle(Request::create($path))->getContent();
}

it('builds the member\'s Home in place of every operator screen about a stack, for a member\'s session', function (): void {
    $screens = everyOperatorsScreenAMemberIsKeptFrom();

    expect($screens)->not->toBeEmpty()
        ->and(whereAMemberLands())->toBe(WhatYouCanWatch::class);

    foreach ($screens as $pattern => $class) {
        expect(whatIsBuiltFor(aKeychainHoldingBothSessions(), $class, aHouseSignedInto(A_MEMBERS_HOUSE)))->toBe(whereAMemberLands(), $pattern);
    }
});

it('builds every operator screen about a stack as asked, for the operator\'s session and for none', function (string $house): void {
    foreach (everyScreenAboutAStack('Modules\\Operator\\') as $pattern => $class) {
        expect(whatIsBuiltFor(aKeychainHoldingBothSessions(), $class, aHouseSignedInto($house)))->toBe($class, $pattern);
    }
})->with([
    'the operator\'s session' => THE_OPERATORS_HOUSE,
    'no session' => NOBODYS_HOUSE,
]);

it('builds the screens every session opens as asked, for a member\'s session', function (): void {
    foreach (array_keys(OutOfTheOperatorsScreens::EVERY_SESSION_OPENS) as $class) {
        expect(whatIsBuiltFor(aKeychainHoldingBothSessions(), $class, aHouseSignedInto(A_MEMBERS_HOUSE)))->toBe($class, $class);
    }
});

it('names only operator screens about a stack as opened by every session, and does not let them grow', function (): void {
    expect(array_diff(array_keys(OutOfTheOperatorsScreens::EVERY_SESSION_OPENS), everyScreenAboutAStack('Modules\\Operator\\')))->toBe([])
        ->and(count(OutOfTheOperatorsScreens::EVERY_SESSION_OPENS))->toBeLessThanOrEqual(1);
});

it('builds the member\'s own screens as asked, for a member\'s session', function (): void {
    $screens = array_filter(
        everyScreenAboutAStack('Modules\\Household\\'),
        static fn(string $class): bool => ! array_key_exists($class, OutOfTheOperatorsScreens::ONLY_THE_OPERATOR_OPENS),
    );

    expect($screens)->not->toBeEmpty();

    foreach ($screens as $pattern => $class) {
        expect(whatIsBuiltFor(aKeychainHoldingBothSessions(), $class, aHouseSignedInto(A_MEMBERS_HOUSE)))->toBe($class, $pattern);
    }
});

it('builds the member\'s Home in place of the preview of the member\'s side, for a member\'s session', function (): void {
    // The preview is drawn by the household's screens and is the operator's
    // all the same: a member is somebody, and their side is their own.
    expect(OutOfTheOperatorsScreens::ONLY_THE_OPERATOR_OPENS)->not->toBeEmpty();

    foreach (array_keys(OutOfTheOperatorsScreens::ONLY_THE_OPERATOR_OPENS) as $class) {
        expect(whatIsBuiltFor(aKeychainHoldingBothSessions(), $class, aHouseSignedInto(A_MEMBERS_HOUSE)))->toBe(whereAMemberLands(), $class)
            ->and(whatIsBuiltFor(aKeychainHoldingBothSessions(), $class, aHouseSignedInto(THE_OPERATORS_HOUSE)))->toBe($class, $class);
    }
});

it('builds an operator screen as asked where its route names no stack, or one that is not a stack', function (array $params): void {
    $built = '';
    $guard = new OutOfTheOperatorsScreens(aKeychainHoldingBothSessions(), $params, static function (string $class) use (&$built): string {
        $built = $class;

        return $class;
    });

    $asked = everyOperatorsScreenAMemberIsKeptFrom()[AStacksScreen::Services->value];
    $guard->screen($asked);

    expect($built)->toBe($asked);
})->with([
    'no stack' => [[]],
    'a stack that is not text' => [['stack' => 7]],
    'a stack named by blanks' => [['stack' => '   ']],
]);

it('serves the member\'s Home for an operator\'s path, through the application, for a member\'s session', function (): void {
    $said = whatTheApplicationBuiltFor(aKeychainHoldingBothSessions(), thePathOf(AStacksScreen::Services->value, aHouseSignedInto(A_MEMBERS_HOUSE)));

    expect($said)->toContain(whereAMemberLands());
});

it('serves the operator\'s screen for its path, through the application, for the operator\'s session', function (): void {
    $said = whatTheApplicationBuiltFor(aKeychainHoldingBothSessions(), thePathOf(AStacksScreen::Services->value, aHouseSignedInto(THE_OPERATORS_HOUSE)));

    expect($said)->toContain(everyOperatorsScreenAMemberIsKeptFrom()[AStacksScreen::Services->value]);
});

it('builds the lock for an operator\'s path on a member\'s session while the lock stands', function (): void {
    $said = whatTheApplicationBuiltFor(aKeychainHoldingBothSessions(), thePathOf(AStacksScreen::Services->value, aHouseSignedInto(A_MEMBERS_HOUSE)), ADeviceThatKnowsYou::refusing());

    expect($said)->toContain(Locked::class);
});
