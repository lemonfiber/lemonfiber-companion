<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\AKeyForAnApp;
use Modules\Kernel\Api\AKeysName;
use Modules\Kernel\Api\AMomentAsWritten;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\KeySaysNothing;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\TheKeysForApps;
use Modules\Kernel\Api\WhatAKeyAdmits;
use Modules\Kernel\Api\WhatAKeyIsFor;
use Modules\Kernel\Api\WhatTheRefusalNamed;
use Modules\Kernel\Api\WhatWasFoundOfTheKeys;
use Modules\Kernel\Api\WhereAKeyStands;

use function sprintf;

use Tests\Support\TheWordCarriedOut;

/** What a scope says, as the core writes it. */
function theScopeOf(WhatAKeyAdmits $admits): string
{
    return $admits->either(
        reading: static fn(): TheWordCarriedOut => new TheWordCarriedOut('read'),
        acting: static fn(): TheWordCarriedOut => new TheWordCarriedOut('act'),
        member: static fn(string $member): TheWordCarriedOut => new TheWordCarriedOut(sprintf('member:%s', $member)),
    )->said;
}

/** A key standing as given. */
function aKeyStanding(WhereAKeyStands $stands): AKeyForAnApp
{
    return AKeyForAnApp::listed(AKeysName::of('den'), WhatAKeyAdmits::reading(), WhatAKeyIsFor::SomethingElse, $stands, AMomentAsWritten::of('2026-10-01T09:00:00Z'), AMomentAsWritten::of(''), memberMinted: false);
}

it('keeps everything it was listed with', function (): void {
    $minted = AMomentAsWritten::of('2026-10-01T09:00:00Z');
    $used = AMomentAsWritten::of('2026-10-11T08:30:00Z');
    $admits = WhatAKeyAdmits::aMembersOwn('Sanne');
    $key = AKeyForAnApp::listed(AKeysName::of('sanne-assistant'), $admits, WhatAKeyIsFor::AnAssistant, WhereAKeyStands::Waiting, $minted, $used, memberMinted: true);

    expect([$key->name()->shown(), $key->admits(), $key->isFor(), $key->stands(), $key->minted(), $key->used(), $key->wasMintedByAMember()])
        ->toBe(['sanne-assistant', $admits, WhatAKeyIsFor::AnAssistant, WhereAKeyStands::Waiting, $minted, $used, true]);
});

it('says each scope as the core writes it', function (): void {
    expect([theScopeOf(WhatAKeyAdmits::reading()), theScopeOf(WhatAKeyAdmits::acting()), theScopeOf(WhatAKeyAdmits::aMembersOwn('Sanne'))])
        ->toBe(['read', 'act', 'member:Sanne']);
});

it('refuses a blank name, and a member scope naming nobody', function (string $blank): void {
    expect(fn(): AKeysName => AKeysName::of($blank))->toThrow(KeySaysNothing::class, 'needs a name that says something')
        ->and(fn(): WhatAKeyAdmits => WhatAKeyAdmits::aMembersOwn($blank))->toThrow(KeySaysNothing::class, 'needs a member that says something');
})->with(['', '  ']);

it('offers revoking every key but one already revoked', function (WhereAKeyStands $stands): void {
    expect(aKeyStanding($stands)->stands()->mayBeRevoked())->toBe($stands !== WhereAKeyStands::Revoked);
})->with(WhereAKeyStands::cases());

it('keeps the listing in the stack\'s order with its sentence on purposes', function (): void {
    $first = aKeyStanding(WhereAKeyStands::Active);
    $second = aKeyStanding(WhereAKeyStands::Orphaned);
    $keys = TheKeysForApps::of('A purpose is what the minter said.', $first, $second);

    expect($keys->purposes())->toBe('A purpose is what the minter said.')
        ->and(iterator_to_array($keys, preserve_keys: false))->toBe([$first, $second])
        ->and($keys)->toHaveCount(2);
});

it('answers with the listing, a refused revoke in the stack\'s words, or with what stood in the way', function (): void {
    $keys = TheKeysForApps::of('A purpose is what the minter said.');
    $why = Obstacle::of(KindOfObstacle::StackDidNotAnswer);
    $refusal = ARefusalInItsWords::said('No key is called that.', '', WhatTheRefusalNamed::nothing());
    $found = static fn(TheKeysForApps $listed): TheWordCarriedOut => new TheWordCarriedOut($listed === $keys ? 'found' : 'another listing');
    $refused = static fn(ARefusalInItsWords $said): TheWordCarriedOut => new TheWordCarriedOut($said === $refusal ? 'refused' : 'another refusal');
    $met = static fn(Obstacle $obstacle): TheWordCarriedOut => new TheWordCarriedOut($obstacle === $why ? 'met' : 'another obstacle');

    expect(WhatWasFoundOfTheKeys::found($keys)->either($found, $refused, $met)->said)->toBe('found')
        ->and(WhatWasFoundOfTheKeys::refused($refusal)->either($found, $refused, $met)->said)->toBe('refused')
        ->and(WhatWasFoundOfTheKeys::met($why)->either($found, $refused, $met)->said)->toBe('met');
});
