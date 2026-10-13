<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AKeyForAnApp;
use Modules\Kernel\Api\AKeysName;
use Modules\Kernel\Api\AMomentAsWritten;
use Modules\Kernel\Api\APhoneKey;
use Modules\Kernel\Api\APhoneKeysName;
use Modules\Kernel\Api\ARefusalInItsWords;
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
use Modules\Kernel\Api\ThePhoneKeys;
use Modules\Kernel\Api\WhatAKeyAdmits;
use Modules\Kernel\Api\WhatAKeyIsFor;
use Modules\Kernel\Api\WhatTheRefusalNamed;
use Modules\Kernel\Api\WhereAKeyStands;
use Modules\Kernel\Api\WhereAPhoneKeyLives;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\TheKeysAppsHold;
use Modules\Operator\Internal\ViewModels\AKeyAsShown;
use Modules\Operator\Internal\ViewModels\APhoneKeyAsShown;
use Modules\Stacks\Api\AStacksScreen;
use Modules\Wayfinding\Internal\TheMenu;
use Modules\Wayfinding\Internal\WhereInTheMenu;
use Tests\Support\AroundThePhone;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AStackThatKeepsKeys;
use Tests\Support\Fakes\AStackThatKeepsPhoneKeys;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\TheDevicePresses;
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

/** A phone key standing in the phone's trusted environment, and one already revoked. */
function twoPhoneKeys(): ThePhoneKeys
{
    return ThePhoneKeys::of(
        APhoneKey::standing(APhoneKeysName::of('Galaxy A51 · 7f3a'), WhereAPhoneKeyLives::TrustedEnvironment, AMomentAsWritten::of('2026-10-01T09:00:00Z'), AMomentAsWritten::of('2026-10-11T08:30:00Z')),
        APhoneKey::revoked(APhoneKeysName::of('old phone · 0001'), WhereAPhoneKeyLives::StrongBox, AMomentAsWritten::of('2026-09-01T09:00:00Z'), AMomentAsWritten::of(''), AMomentAsWritten::of('2026-10-02T09:00:00Z')),
    );
}

function theKeysScreen(AStackThatKeepsKeys $keeping, ?AKeychainInMemory $keychain = null, bool $signedIn = true, ?AStackThatKeepsPhoneKeys $phoneKeys = null): TheKeysAppsHold
{
    $stack = theStackWhoseKeysAreRead();
    $keychain ??= AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    }

    $screen = new TheKeysAppsHold($keeping, $phoneKeys ?? AStackThatKeepsPhoneKeys::listing(twoPhoneKeys()), $keychain, FrozenClock::at(Instant::atEpochSeconds(1_791_710_000)), AroundThePhone::holding(StacksInMemory::holding($stack)), new AppsSettingsThatOpen(), AroundThePhone::listening());
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

it('says when a key was made only where the stack wrote a moment it can read', function (): void {
    $unread = TheKeysForApps::of(
        WHAT_A_PURPOSE_IS_WORTH,
        AKeyForAnApp::listed(AKeysName::of('den'), WhatAKeyAdmits::reading(), WhatAKeyIsFor::SomethingElse, WhereAKeyStands::Active, AMomentAsWritten::of(''), AMomentAsWritten::of(''), memberMinted: false),
    );
    $made = trim(aKeysLine('stacks.keys.minted', ['when' => '']));
    $saysWhenMade = static fn(array $drawn): bool => array_any($drawn, static fn(mixed $line): bool => is_string($line) && str_starts_with($line, $made));

    expect($saysWhenMade(WhatTheDeviceWouldDraw::by(theKeysScreen(AStackThatKeepsKeys::listing($unread)))->said()))->toBeFalse()
        ->and($saysWhenMade(WhatTheDeviceWouldDraw::by(theKeysScreen(AStackThatKeepsKeys::listing(threeKeysForApps())))->said()))->toBeTrue();
});

it('asks before revoking, saying the key stops at once and cannot be brought back, and keeps it when told to', function (): void {
    $keeping = AStackThatKeepsKeys::listing(threeKeysForApps());
    $screen = theKeysScreen($keeping);

    TheDevicePresses::on($screen, aKeysLine('stacks.keys.revoke_named', ['name' => 'den']));
    $asked = WhatTheDeviceWouldDraw::by($screen)->said();
    $screen->keepIt();

    expect($asked)->toContain(aKeysLine('stacks.keys.revoke_confirm', ['name' => 'den']), aKeysLine('stacks.keys.keep_it'))
        ->and($keeping->revoked())->toBe([])
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->not->toContain(aKeysLine('stacks.keys.revoke_confirm', ['name' => 'den']));
});

it('revokes the key once confirmed and shows the listing the stack answers with', function (): void {
    $keeping = AStackThatKeepsKeys::listing(threeKeysForApps());
    $screen = theKeysScreen($keeping);

    TheDevicePresses::on($screen, aKeysLine('stacks.keys.revoke_named', ['name' => 'den']));
    TheDevicePresses::on($screen, aKeysLine('stacks.keys.revoke_named', ['name' => 'den']));
    $screen->revoke();

    expect($keeping->revoked())->toBe(['den'])
        ->and($screen->answer()->keys[0]->standsSaid)->toBe(WhereAKeyStands::Revoked->saidOnTheScreen())
        ->and($screen->answer()->keys[0]->mayBeRevoked)->toBeFalse();
});

it('revokes the key the operator pressed whatever the stack named it, quotes and all', function (string $named): void {
    $keeping = AStackThatKeepsKeys::listing(TheKeysForApps::of(
        WHAT_A_PURPOSE_IS_WORTH,
        AKeyForAnApp::listed(AKeysName::of('den'), WhatAKeyAdmits::acting(), WhatAKeyIsFor::HomeAssistant, WhereAKeyStands::Active, AMomentAsWritten::of(''), AMomentAsWritten::of(''), memberMinted: false),
        AKeyForAnApp::listed(AKeysName::of($named), WhatAKeyAdmits::reading(), WhatAKeyIsFor::SomethingElse, WhereAKeyStands::Active, AMomentAsWritten::of(''), AMomentAsWritten::of(''), memberMinted: false),
    ));
    $screen = theKeysScreen($keeping);

    TheDevicePresses::on($screen, aKeysLine('stacks.keys.revoke_named', ['name' => $named]));
    TheDevicePresses::on($screen, aKeysLine('stacks.keys.revoke_named', ['name' => $named]));

    expect($keeping->revoked())->toBe([$named]);
})->with([
    'a quote that would close the argument' => ["den', 'den"],
    'double quotes and a backslash' => ['the "den" \\ reader'],
    'markup' => ['<b>den</b> & co'],
]);

it('asks nothing for a row it does not have or a key that cannot be revoked', function (): void {
    $screen = theKeysScreen(AStackThatKeepsKeys::listing(threeKeysForApps()));

    $screen->askToRevoke(2);
    $screen->askToRevoke(3);

    expect($screen->revoking)->toBe('');
});

it('offers no revoke for a key already revoked', function (): void {
    $screen = theKeysScreen(AStackThatKeepsKeys::listing(threeKeysForApps()));

    expect(array_map(static fn(AKeyAsShown $key): bool => $key->mayBeRevoked, $screen->answer()->keys))->toBe([true, true, false])
        ->and(WhatTheDeviceWouldDraw::by($screen)->offers())->not->toContain(aKeysLine('stacks.keys.revoke_named', ['name' => 'old-reader']));
});

it('says a stack with no keys has none, and shows what stood in the way of a revoke', function (): void {
    $none = WhatTheDeviceWouldDraw::by(theKeysScreen(AStackThatKeepsKeys::listing(TheKeysForApps::of(WHAT_A_PURPOSE_IS_WORTH))))->said();
    $refused = theKeysScreen(AStackThatKeepsKeys::refusingToRevoke(threeKeysForApps(), Obstacle::of(KindOfObstacle::StackDidNotAnswer)));
    TheDevicePresses::on($refused, aKeysLine('stacks.keys.revoke_named', ['name' => 'den']));
    TheDevicePresses::on($refused, aKeysLine('stacks.keys.revoke_named', ['name' => 'den']));

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

it('lists the phone keys beside the keys for apps, on a frame of their own, with where each lives', function (): void {
    $screen = theKeysScreen(AStackThatKeepsKeys::listing(threeKeysForApps()));
    $first = WhatTheDeviceWouldDraw::by($screen);
    $drawn = WhatTheDeviceWouldDraw::onTheSecondFrame($screen)->said();

    expect($first->said())->not->toContain('Galaxy A51 · 7f3a')
        ->and($drawn)->toContain(
            aKeysLine('stacks.phone_keys.heading'),
            'Galaxy A51 · 7f3a',
            aKeysLine(WhereAPhoneKeyLives::TrustedEnvironment->saidOnTheScreen()),
            'old phone · 0001',
            aKeysLine('stacks.phone_keys.revoked'),
        )
        ->and(array_map(static fn(APhoneKeyAsShown $key): bool => $key->mayBeRevoked, $screen->phoneKeys()->keys ?? []))->toBe([true, false]);
});

it('asks before revoking a phone key, saying the phone can no longer make keys with it, and revokes it once confirmed', function (): void {
    $phoneKeys = AStackThatKeepsPhoneKeys::listing(twoPhoneKeys());
    $screen = theKeysScreen(AStackThatKeepsKeys::listing(threeKeysForApps()), phoneKeys: $phoneKeys);
    WhatTheDeviceWouldDraw::onTheSecondFrame($screen);

    TheDevicePresses::on($screen, aKeysLine('stacks.keys.revoke_named', ['name' => 'Galaxy A51 · 7f3a']));
    $asked = WhatTheDeviceWouldDraw::by($screen)->said();
    $screen->keepThePhoneKey();
    $kept = $phoneKeys->revoked();
    $screen->askToRevokeAPhoneKey(0);
    $screen->revokeThePhoneKey();

    expect($asked)->toContain(aKeysLine('stacks.phone_keys.revoke_confirm', ['name' => 'Galaxy A51 · 7f3a']))
        ->and($kept)->toBe([])
        ->and($phoneKeys->revoked())->toBe(['Galaxy A51 · 7f3a'])
        ->and($screen->phoneKeys()?->keys[0]->mayBeRevoked)->toBeFalse();
});

it('asks nothing for a phone key row it does not have, one already revoked, or a confirm with nothing asked', function (): void {
    $phoneKeys = AStackThatKeepsPhoneKeys::listing(twoPhoneKeys());
    $screen = theKeysScreen(AStackThatKeepsKeys::listing(threeKeysForApps()), phoneKeys: $phoneKeys);
    WhatTheDeviceWouldDraw::onTheSecondFrame($screen);

    $screen->askToRevokeAPhoneKey(1);
    $screen->askToRevokeAPhoneKey(2);
    $screen->revokeThePhoneKey();

    expect($screen->revokingAPhoneKey)->toBe('')
        ->and($phoneKeys->revoked())->toBe([]);
});

it('says a phone key revoke the stack refused in its words, beside the keys as they were', function (): void {
    $screen = theKeysScreen(AStackThatKeepsKeys::listing(threeKeysForApps()), phoneKeys: AStackThatKeepsPhoneKeys::refusingToRevoke(twoPhoneKeys(), ARefusalInItsWords::said('No phone key is called that.', '', WhatTheRefusalNamed::nothing())));
    WhatTheDeviceWouldDraw::onTheSecondFrame($screen);

    $screen->askToRevokeAPhoneKey(0);
    $screen->revokeThePhoneKey();
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($drawn)->toContain(aKeysLine('stacks.phone_keys.not_revoked'), 'No phone key is called that.', 'Galaxy A51 · 7f3a');
});

it('says the phone keys could not be read where the stack could not be asked, and asks both again when told to', function (): void {
    $screen = theKeysScreen(AStackThatKeepsKeys::listing(threeKeysForApps()), phoneKeys: AStackThatKeepsPhoneKeys::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)));
    $drawn = WhatTheDeviceWouldDraw::onTheSecondFrame($screen)->said();
    $screen->again();

    expect($drawn)->toContain(aKeysLine('stacks.phone_keys.unread'))
        ->and($screen->phoneKeysFound)->toBeNull()
        ->and($screen->answered)->toBeNull();
});

it('says the session has ended for the phone keys where the device holds none', function (): void {
    $screen = theKeysScreen(AStackThatKeepsKeys::listing(threeKeysForApps()), signedIn: false);

    expect($screen->phoneKeys()?->went->isSignedIn)->toBeFalse();
});
