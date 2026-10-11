<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AKeyForAnApp;
use Modules\Kernel\Api\AKeysName;
use Modules\Kernel\Api\AMomentAsWritten;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheKeysForApps;
use Modules\Kernel\Api\WhatAKeyAdmits;
use Modules\Kernel\Api\WhatAKeyIsFor;
use Modules\Kernel\Api\WhereAKeyStands;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\TheKeysAppsHold;
use Modules\Operator\Internal\ViewModels\AKeyAsShown;
use Modules\Stacks\Api\AStacksScreen;
use Modules\Wayfinding\Internal\TheMenu;
use Modules\Wayfinding\Internal\WhereInTheMenu;
use Tests\Support\AroundThePhone;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AStackThatKeepsKeys;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\WhatTheDeviceWouldDraw;

// The keys a stack has minted for apps, and revoking one.

function theStackWhoseKeysAreRead(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('k', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('d', Fingerprint::CHARACTERS)),
    );
}

const WHAT_A_PURPOSE_IS_WORTH = 'A purpose is what whoever minted the key said; this machine cannot check it.';

/** A working key for Home Assistant, a member's key waiting to be collected, and one already revoked. */
function threeKeysForApps(): TheKeysForApps
{
    return TheKeysForApps::of(
        WHAT_A_PURPOSE_IS_WORTH,
        AKeyForAnApp::listed(AKeysName::of('den'), WhatAKeyAdmits::acting(), WhatAKeyIsFor::HomeAssistant, WhereAKeyStands::Active, AMomentAsWritten::of('2026-10-01T09:00:00Z'), AMomentAsWritten::of('2026-10-11T08:30:00Z'), memberMinted: false),
        AKeyForAnApp::listed(AKeysName::of('sanne-assistant'), WhatAKeyAdmits::aMembersOwn('Sanne'), WhatAKeyIsFor::AnAssistant, WhereAKeyStands::Waiting, AMomentAsWritten::of('2026-10-11T09:00:00Z'), AMomentAsWritten::of(''), memberMinted: true),
        AKeyForAnApp::listed(AKeysName::of('old-reader'), WhatAKeyAdmits::reading(), WhatAKeyIsFor::SomethingElse, WhereAKeyStands::Revoked, AMomentAsWritten::of('2026-09-01T09:00:00Z'), AMomentAsWritten::of(''), memberMinted: false),
    );
}

function theKeysScreen(AStackThatKeepsKeys $keeping, ?AKeychainInMemory $keychain = null, bool $signedIn = true): TheKeysAppsHold
{
    $stack = theStackWhoseKeysAreRead();
    $keychain ??= AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    }

    $screen = new TheKeysAppsHold($keeping, $keychain, FrozenClock::at(Instant::atEpochSeconds(1_791_710_000)), AroundThePhone::holding(StacksInMemory::holding($stack)), new AppsSettingsThatOpen(), AroundThePhone::listening());
    $screen->setParams(['stack' => $stack->id()->stored()]);

    return $screen;
}

/** @param array<string, string> $filling */
function aKeysLine(string $key, array $filling = []): string
{
    $line = __($key, $filling);

    return is_string($line) ? $line : '';
}

it('lists each key with what it admits, what it is for, where it stands and who made it, under the stack\'s sentence on purposes', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theKeysScreen(AStackThatKeepsKeys::listing(threeKeysForApps())))->said();

    expect($drawn)->toContain(
        aKeysLine('stacks.keys.heading'),
        WHAT_A_PURPOSE_IS_WORTH,
        'den',
        aKeysLine('stacks.keys.state.active'),
        aKeysLine('stacks.keys.admits.act'),
        aKeysLine('stacks.keys.purpose.home-assistant'),
        'sanne-assistant',
        aKeysLine('stacks.keys.state.waiting'),
        aKeysLine('stacks.keys.admits.member', ['member' => 'Sanne']),
        aKeysLine('stacks.keys.purpose.mcp'),
        aKeysLine('stacks.keys.by_a_member'),
        aKeysLine('stacks.keys.never_used'),
        aKeysLine('stacks.keys.state.revoked'),
        aKeysLine('stacks.keys.admits.read'),
        aKeysLine('stacks.keys.made_at_the_terminal'),
    );
});

it('asks before revoking, saying the key stops at once and cannot be brought back, and keeps it when told to', function (): void {
    $keeping = AStackThatKeepsKeys::listing(threeKeysForApps());
    $screen = theKeysScreen($keeping);

    $screen->askToRevoke('den');
    $asked = WhatTheDeviceWouldDraw::by($screen)->said();
    $screen->keepIt();

    expect($asked)->toContain(aKeysLine('stacks.keys.revoke_confirm', ['name' => 'den']), aKeysLine('stacks.keys.keep_it'))
        ->and($keeping->revoked())->toBe([])
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->not->toContain(aKeysLine('stacks.keys.revoke_confirm', ['name' => 'den']));
});

it('revokes the key once confirmed and shows the listing the stack answers with', function (): void {
    $keeping = AStackThatKeepsKeys::listing(threeKeysForApps());
    $screen = theKeysScreen($keeping);

    $screen->askToRevoke('den');
    $screen->revoke();
    $screen->revoke();

    expect($keeping->revoked())->toBe(['den'])
        ->and($screen->answer()->keys[0]->standsSaid)->toBe(WhereAKeyStands::Revoked->saidOnTheScreen())
        ->and($screen->answer()->keys[0]->mayBeRevoked)->toBeFalse();
});

it('offers no revoke for a key already revoked', function (): void {
    $screen = theKeysScreen(AStackThatKeepsKeys::listing(threeKeysForApps()));

    expect(array_map(static fn(AKeyAsShown $key): bool => $key->mayBeRevoked, $screen->answer()->keys))->toBe([true, true, false])
        ->and(WhatTheDeviceWouldDraw::by($screen)->offers())->not->toContain(aKeysLine('stacks.keys.revoke_named', ['name' => 'old-reader']));
});

it('says a stack with no keys has none, and shows what stood in the way of a revoke', function (): void {
    $none = WhatTheDeviceWouldDraw::by(theKeysScreen(AStackThatKeepsKeys::listing(TheKeysForApps::of(WHAT_A_PURPOSE_IS_WORTH))))->said();
    $refused = theKeysScreen(AStackThatKeepsKeys::refusingToRevoke(threeKeysForApps(), Obstacle::of(KindOfObstacle::StackDidNotAnswer)));
    $refused->askToRevoke('den');
    $refused->revoke();

    expect($none)->toContain(aKeysLine('stacks.keys.none'))
        ->and($refused->answer()->went->cameBack())->toBeFalse();
});

it('offers signing in where the device holds no session, and lets go of one the stack refused', function (): void {
    $keychain = AKeychainInMemory::working();
    $refused = theKeysScreen(AStackThatKeepsKeys::met(Obstacle::of(KindOfObstacle::CredentialWasRefused)), $keychain);

    expect(theKeysScreen(AStackThatKeepsKeys::listing(threeKeysForApps()), signedIn: false)->answer()->went->cameBack())->toBeFalse()
        ->and($refused->answer()->went->cameBack())->toBeFalse()
        ->and($keychain->keepsAnythingOf(theStackWhoseKeysAreRead()->id()))->toBeFalse();
});

it('is reached from the access part of the operator\'s menu, beside the passwords', function (): void {
    expect(TheMenu::AppKeys->screen())->toBe(AStacksScreen::Keys)
        ->and(TheMenu::AppKeys->group())->toBe(WhereInTheMenu::Access);
});
