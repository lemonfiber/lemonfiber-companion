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
use Modules\Kernel\Api\WhyNothingWasShared;
use Modules\Kernel\Api\WhyTheKeySignedNothing;
use Modules\Kernel\Api\WhyThisPhoneHoldsNoKey;
use Modules\Operator\Internal\Screens\TheKeysAppsHold;
use Modules\Operator\Internal\ViewModels\AKeyAsShown;
use Modules\Operator\Internal\ViewModels\APhoneKeyAsShown;
use Modules\Stacks\Api\AStacksScreen;
use Modules\Wayfinding\Internal\TheMenu;
use Modules\Wayfinding\Internal\WhereInTheMenu;
use Tests\Support\APhoneThatMints;
use Tests\Support\AroundThePhone;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\APhoneKeyInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AShareSheetThatWasOffered;
use Tests\Support\Fakes\AStackThatInvites;
use Tests\Support\Fakes\AStackThatKeepsKeys;
use Tests\Support\Fakes\AStackThatKeepsPhoneKeys;
use Tests\Support\Fakes\AStackThatMintsKeys;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\PhoneKeysNamesKeptInMemory;
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

function theKeysScreen(AStackThatKeepsKeys $keeping, ?AKeychainInMemory $keychain = null, bool $signedIn = true, ?AStackThatKeepsPhoneKeys $phoneKeys = null, ?APhoneThatMints $minting = null, int $now = 1_791_710_000): TheKeysAppsHold
{
    $stack = theStackWhoseKeysAreRead();
    $keychain ??= AKeychainInMemory::working();
    $minting ??= APhoneThatMints::onAnAndroidPhone();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    }

    $screen = new TheKeysAppsHold(
        $keeping,
        $phoneKeys ?? AStackThatKeepsPhoneKeys::listing(twoPhoneKeys()),
        $keychain,
        FrozenClock::at(Instant::atEpochSeconds($now)),
        AroundThePhone::holding(StacksInMemory::holding($stack)),
        new AppsSettingsThatOpen(),
        AroundThePhone::listening(),
        $minting->minting(),
        $minting->sheet,
    );
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
        aKeysLine('stacks.mint.start'),
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

    expect($screen->revoking)->toBeNull();
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

it('says an app key revoke the stack refused in its words, beside the keys as they were', function (): void {
    $screen = theKeysScreen(AStackThatKeepsKeys::refusingToRevoke(threeKeysForApps(), ARefusalInItsWords::said('No key is called that.', 'There is nothing to revoke.', WhatTheRefusalNamed::nothing())));
    WhatTheDeviceWouldDraw::by($screen);
    $screen->askToRevoke(0);
    $screen->revoke();
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();
    $unread = theKeysScreen(AStackThatKeepsKeys::refusingToRevoke(threeKeysForApps(), ARefusalInItsWords::said('No key is called that.', '', WhatTheRefusalNamed::nothing())));
    $unread->askToRevoke(0);
    $unread->answered = null;
    $unread->revoke();

    expect($drawn)->toContain(aKeysLine('stacks.keys.not_revoked'), 'No key is called that.', 'There is nothing to revoke.', 'den')
        ->and($unread->answered?->keys)->toBe([])
        ->and($unread->answered?->refused?->said)->toBe('No key is called that.');
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

    expect($screen->revokingAPhoneKey)->toBeNull()
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

/** The screen with the form for a key open, its name typed. */
function theFormForAKey(APhoneThatMints $minting, int $now = 1_791_925_200, bool $signedIn = true): TheKeysAppsHold
{
    $screen = theKeysScreen(AStackThatKeepsKeys::listing(threeKeysForApps()), signedIn: $signedIn, minting: $minting, now: $now);
    $screen->startMinting();
    $screen->mintName = 'Home Assistant';

    return $screen;
}

/** A key made from this phone for the stack, so the next asks for face or fingerprint alone. */
function aKeyMadeFrom(APhoneThatMints $minting): TheKeysAppsHold
{
    $screen = theFormForAKey($minting);
    $screen->password = 'the operator password';
    $screen->mint();

    return $screen;
}

it('offers making a key where this phone can keep one, asking for the password the first time', function (): void {
    $screen = theKeysScreen(AStackThatKeepsKeys::listing(threeKeysForApps()));
    $offered = WhatTheDeviceWouldDraw::by($screen)->said();
    $screen->startMinting();
    $form = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($offered)->toContain(aKeysLine('stacks.mint.start'))
        ->and($form)->toContain(aKeysLine('stacks.mint.heading'), aKeysLine('stacks.mint.first_from_this_phone'), aKeysLine('stacks.mint.admits.read'), aKeysLine('stacks.keys.purpose.mcp'))
        ->and($screen->mayMint())->toBeFalse();
});

it('does not offer making a key where this phone cannot keep one, and says why and where a key is made', function (WhyThisPhoneHoldsNoKey $why, string $said): void {
    $screen = theKeysScreen(AStackThatKeepsKeys::listing(threeKeysForApps()), minting: APhoneThatMints::onAnAndroidPhone(APhoneKeyInMemory::keepingNone($why)));
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();
    $screen->startMinting();

    expect($drawn)->toContain(aKeysLine($said), aKeysLine('stacks.keys.made_at_the_terminal'))
        ->and($drawn)->not->toContain(aKeysLine('stacks.mint.start'))
        ->and($screen->minting)->toBeFalse();
})->with([
    'no hardware' => [WhyThisPhoneHoldsNoKey::NoHardware, 'stacks.mint.no_hardware'],
    'no face or fingerprint' => [WhyThisPhoneHoldsNoKey::NoBiometric, 'stacks.mint.no_biometric'],
    'only in software' => [WhyThisPhoneHoldsNoKey::InSoftware, 'stacks.mint.in_software'],
    'nothing vouching' => [WhyThisPhoneHoldsNoKey::Unattested, 'stacks.mint.unattested'],
    'something else' => [WhyThisPhoneHoldsNoKey::Failed, 'stacks.mint.could_not_make'],
]);

it('registers this phone once with the password, proves the mint with its key, and shows the link to collect it', function (): void {
    $minting = APhoneThatMints::onAnAndroidPhone();
    $screen = aKeyMadeFrom($minting);
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($minting->stack->claimed())->toBe(['Home Assistant'])
        ->and($minting->stack->hasRegistered('Galaxy A51 · 7f3a'))->toBeTrue()
        ->and($screen->password)->toBe('')
        ->and($screen->minting)->toBeFalse()
        ->and($drawn)->toContain(
            aKeysLine('stacks.mint.claim_for', ['name' => 'Home Assistant']),
            AStackThatMintsKeys::LINK,
            trans_choice('stacks.mint.lapses', 10),
            aKeysLine('stacks.mint.once'),
        );
});

it('asks for face or fingerprint alone once this phone is registered, and marks this phone in the list', function (): void {
    $minting = APhoneThatMints::onAnAndroidPhone();
    aKeyMadeFrom($minting);
    $screen = theFormForAKey($minting);
    $form = WhatTheDeviceWouldDraw::onTheSecondFrame($screen)->said();
    $screen->mint();

    expect($form)->toContain(aKeysLine('stacks.mint.with_face_or_fingerprint'), aKeysLine('stacks.phone_keys.this_phone'))
        ->and($form)->not->toContain(aKeysLine('stacks.mint.first_from_this_phone'))
        ->and($minting->stack->claimed())->toBe(['Home Assistant', 'Home Assistant'])
        ->and(array_map(static fn(APhoneKeyAsShown $key): bool => $key->isThisPhone, $screen->phoneKeys()->keys ?? []))->toBe([true, false]);
});

it('makes a key for one member of the household, chosen from those who joined', function (): void {
    $minting = APhoneThatMints::onAnAndroidPhone();
    $screen = theFormForAKey($minting);
    $screen->password = 'the operator password';
    $screen->admitting('member');
    $waiting = $screen->mayMint();
    $screen->admittingMember(1);
    $screen->admittingMember(7);
    $screen->mint();

    expect($screen->mintMembers)->toBe(['ada', 'cy'])
        ->and($waiting)->toBeFalse()
        ->and($screen->mintMember)->toBe('cy')
        ->and($minting->stack->claimed())->toBe(['Home Assistant']);
});

it('keeps what it admits and what it is for where a choice names nothing', function (): void {
    $screen = theFormForAKey(APhoneThatMints::onAnAndroidPhone());
    $screen->admitting('act');
    $screen->admitting('everything');
    $screen->meantFor('mcp');
    $screen->meantFor('a toaster');

    expect($screen->mintAdmits)->toBe('act')
        ->and($screen->mintFor)->toBe('mcp')
        ->and($screen->admitsAMembersOwn())->toBeFalse()
        ->and($screen->mintMembers)->toBe([])
        ->and($screen->whatAKeyMayBeFor())->toBe(['home-assistant', 'mcp', 'other']);
});

it('asks whether this phone can keep a key once, however often the screen asks why not', function (): void {
    $phone = APhoneKeyInMemory::onAnAndroidPhone();
    $screen = theKeysScreen(AStackThatKeepsKeys::listing(threeKeysForApps()), minting: APhoneThatMints::onAnAndroidPhone($phone));
    $screen->mintingIsNotOffered();
    $screen->mintingIsNotOffered();

    expect($phone->timesAskedWhere())->toBe(1);
});

it('does not make a key with only spaces for its name, its member or the password, and names it as typed less the spaces around it', function (string $field): void {
    $minting = APhoneThatMints::onAnAndroidPhone();
    $screen = theFormForAKey($minting);
    $screen->password = 'the operator password';
    $screen->admitting('member');
    $screen->admittingMember(0);
    match ($field) {
        'name' => $screen->mintName = '  ',
        'member' => $screen->mintMember = '  ',
        default => $screen->password = '  ',
    };
    $blank = $screen->mayMint();
    $screen->mintName = '  Home Assistant  ';
    $screen->mintMember = 'ada';
    $screen->password = 'the operator password';
    $screen->mint();

    expect($blank)->toBeFalse()
        ->and($minting->stack->claimed())->toBe(['Home Assistant']);
})->with(['a name' => ['name'], 'a member' => ['member'], 'a password' => ['password']]);

it('says how many whole minutes the link still collects the key for, rounding a part minute up, and none once it lapsed or where none is shown', function (int $before, int $minutes): void {
    $screen = theFormForAKey(APhoneThatMints::onAnAndroidPhone(), now: 1_791_925_800 - $before);
    $none = $screen->minutesTheLinkLasts();
    $screen->password = 'the operator password';
    $screen->mint();

    expect($screen->minutesTheLinkLasts())->toBe($minutes)
        ->and($none)->toBe(0);
})->with([
    'ten minutes exactly' => [600, 10],
    'a second past ten' => [601, 11],
    'a second' => [1, 1],
    'lapsed two minutes ago' => [-120, 0],
]);

it('says nobody could be read where the household could not be asked', function (): void {
    $screen = theKeysScreen(AStackThatKeepsKeys::listing(threeKeysForApps()), minting: APhoneThatMints::onAnAndroidPhone(household: AStackThatInvites::answering()->readingAs(Obstacle::of(KindOfObstacle::StackDidNotAnswer))));
    $screen->startMinting();
    $screen->admitting('member');

    expect($screen->mintMembers)->toBe([])
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(aKeysLine('stacks.mint.no_members'));
});

it('sends nothing where face or fingerprint was not given, and keeps the form as it was', function (): void {
    $minting = APhoneThatMints::onAnAndroidPhone();
    aKeyMadeFrom($minting);
    $minting->phone->declining(WhyTheKeySignedNothing::Cancelled);
    $screen = theFormForAKey($minting);
    $screen->mint();

    expect($minting->stack->claimed())->toBe(['Home Assistant'])
        ->and($screen->minting)->toBeTrue()
        ->and($screen->mintName)->toBe('Home Assistant')
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(aKeysLine('stacks.mint.cancelled'));
});

it('throws this phone\'s key away where it no longer works or the stack no longer knows it, so the password is asked again', function (WhyTheKeySignedNothing|string $what, string $said): void {
    $minting = APhoneThatMints::onAnAndroidPhone();
    aKeyMadeFrom($minting);

    if ($what instanceof WhyTheKeySignedNothing) {
        $minting->phone->declining($what);
    }

    if (is_string($what)) {
        $minting->stack->revoke($what);
    }

    $screen = theFormForAKey($minting);
    $screen->mint();

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(aKeysLine($said))
        ->and($screen->needsThePassword())->toBeTrue()
        ->and($minting->names->keepsAnythingOf(theStackWhoseKeysAreRead()->id()))->toBeFalse();
})->with([
    'a new face or fingerprint' => [WhyTheKeySignedNothing::Gone, 'stacks.mint.gone'],
    'revoked on the stack' => ['Galaxy A51 · 7f3a', 'stacks.mint.unregistered'],
]);

it('throws this phone\'s new key away where the phone would not keep the name it was registered under', function (): void {
    $minting = APhoneThatMints::onAnAndroidPhone(names: PhoneKeysNamesKeptInMemory::refusing());
    $screen = aKeyMadeFrom($minting);

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(aKeysLine('stacks.mint.failed'))
        ->and($minting->stack->claimed())->toBe([])
        ->and($minting->phone->keepsAnythingOf(theStackWhoseKeysAreRead()->id()))->toBeFalse();
});

it('says something went wrong where the phone could not sign', function (): void {
    $minting = APhoneThatMints::onAnAndroidPhone();
    aKeyMadeFrom($minting);
    $minting->phone->declining(WhyTheKeySignedNothing::Failed);
    $screen = theFormForAKey($minting);
    $screen->mint();

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(aKeysLine('stacks.mint.failed'));
});

it('says a refused mint in the stack\'s words, and a stack it could not ask as what stood in the way', function (): void {
    $refusing = APhoneThatMints::onAnAndroidPhone(stack: AStackThatMintsKeys::refusing(ARefusalInItsWords::said('Serve the stack so another machine can reach it.', 'A claim is collected from another machine.', WhatTheRefusalNamed::nothing())));
    $refused = aKeyMadeFrom($refusing);
    $unreached = aKeyMadeFrom(APhoneThatMints::onAnAndroidPhone(stack: AStackThatMintsKeys::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))));

    expect(WhatTheDeviceWouldDraw::by($refused)->said())->toContain(aKeysLine('stacks.mint.no_key_made'), 'Serve the stack so another machine can reach it.')
        ->and(WhatTheDeviceWouldDraw::by($unreached)->said())->toContain(aKeysLine('stacks.mint.no_key_made'))
        ->and($unreached->minted?->met?->cameBack())->toBeFalse()
        ->and($refusing->names->keepsAnythingOf(theStackWhoseKeysAreRead()->id()))->toBeFalse();
});

it('says the session has ended where the device holds none, and sends nothing without a name', function (): void {
    $minting = APhoneThatMints::onAnAndroidPhone();
    $screen = theFormForAKey($minting, signedIn: false);
    $screen->password = 'the operator password';
    $screen->mint();
    $unnamed = theFormForAKey($minting);
    $unnamed->mintName = ' ';
    $unnamed->mint();

    expect($screen->minted?->said)->toBe('connection.session_has_ended')
        ->and($unnamed->minted)->toBeNull()
        ->and($minting->stack->claimed())->toBe([]);
});

it('passes the link on through the share sheet, and says where it could not', function (): void {
    $passing = APhoneThatMints::onAnAndroidPhone();
    $passed = aKeyMadeFrom($passing);
    $passed->passOnTheLink();
    $refused = aKeyMadeFrom(APhoneThatMints::onAnAndroidPhone(sheet: AShareSheetThatWasOffered::refusing(WhyNothingWasShared::TheDeviceWouldNotOffer)));
    $refused->passOnTheLink();
    $nothing = theFormForAKey(APhoneThatMints::onAnAndroidPhone());
    $nothing->passOnTheLink();

    expect($passing->sheet->handed()?->text())->toBe(AStackThatMintsKeys::LINK)
        ->and($passing->sheet->handed()?->named())->toBe('Home Assistant')
        ->and($passed->linkPassedOn)->toBe('stacks.mint.passed_on')
        ->and($refused->linkPassedOn)->toBe('stacks.mint.not_passed_on')
        ->and($nothing->linkPassedOn)->toBe('');
});

it('lets the link go once the screen comes back to the front, and says a lapsed link collects nothing', function (): void {
    $screen = aKeyMadeFrom(APhoneThatMints::onAnAndroidPhone());
    $screen->onResume();
    $lapsed = theFormForAKey(APhoneThatMints::onAnAndroidPhone(), now: 1_791_926_000);
    $lapsed->password = 'the operator password';
    $lapsed->mint();
    $form = theFormForAKey(APhoneThatMints::onAnAndroidPhone());
    $form->onResume();

    expect($screen->minted)->toBeNull()
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->not->toContain(AStackThatMintsKeys::LINK)
        ->and(WhatTheDeviceWouldDraw::by($lapsed)->said())->toContain(aKeysLine('stacks.mint.lapsed'))
        ->and(WhatTheDeviceWouldDraw::by($lapsed)->said())->not->toContain(AStackThatMintsKeys::LINK)
        ->and($form->minting)->toBeTrue();
});

it('puts the form away when told to', function (): void {
    $screen = theFormForAKey(APhoneThatMints::onAnAndroidPhone());
    $screen->password = 'typed';
    $screen->stopMinting();

    expect($screen->minting)->toBeFalse()
        ->and($screen->password)->toBe('');
});
