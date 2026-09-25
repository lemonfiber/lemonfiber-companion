<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_keys;
use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\ACredentialHeld;
use Modules\Kernel\Api\CredentialSaysNothing;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Remarks;
use Modules\Kernel\Api\TheCredentialsHeld;
use Modules\Kernel\Api\WhatTheStoreProtects;
use Modules\Kernel\Api\WhatUsesIt;
use Modules\Kernel\Api\WhatWasFoundOfTheCredentials;
use Modules\Kernel\Api\WhereACredentialStands;
use Modules\Kernel\Api\WhoMadeACredential;

use function sprintf;

/** What the store protects against, as every case here says it. */
function aStoreThatProtects(): WhatTheStoreProtects
{
    return WhatTheStoreProtects::said('Plain files', Remarks::of('Other accounts'), Remarks::of('You'));
}

/** A credential by this name, for reading an order off. */
function aCredentialCalled(string $name): ACredentialHeld
{
    return ACredentialHeld::described($name, WhereACredentialStands::Active, WhoMadeACredential::Operator, WhatUsesIt::of(), '');
}

/** One line carried out of an arm. */
final readonly class WhichArmTheCredentialsTook
{
    public function __construct(public string $said) {}
}

it('keeps what the store is, what it protects against, and what it does not', function (): void {
    $against = Remarks::of('Other accounts');
    $notAgainst = Remarks::of('You');
    $store = WhatTheStoreProtects::said('Plain files', $against, $notAgainst);

    expect([$store->summary(), $store->against(), $store->notAgainst()])->toBe(['Plain files', $against, $notAgainst]);
});

it('refuses a store that will not say what it is', function (): void {
    expect(fn(): WhatTheStoreProtects => WhatTheStoreProtects::said(' ', Remarks::of(), Remarks::of()))->toThrow(CredentialSaysNothing::class, '`summary`');
});

it('keeps every credential in the stack\'s order, with the store beside them', function (): void {
    $store = aStoreThatProtects();
    $held = TheCredentialsHeld::of($store, ...['first' => aCredentialCalled('Indexer API key'), 'second' => aCredentialCalled('Usenet password')]);
    $names = [];

    foreach ($held as $credential) {
        $names[] = $credential->name();
    }

    expect($names)->toBe(['Indexer API key', 'Usenet password'])
        ->and(array_keys(iterator_to_array($held, preserve_keys: true)))->toBe([0, 1])
        ->and($held)->toHaveCount(2)
        ->and($held->protection())->toBe($store)
        ->and(TheCredentialsHeld::of($store))->toHaveCount(0);
});

it('a stack that could not be asked never holds an empty list', function (): void {
    $fold = static fn(WhatWasFoundOfTheCredentials $answer): string => $answer->either(
        found: static fn(TheCredentialsHeld $held): WhichArmTheCredentialsTook => new WhichArmTheCredentialsTook(sprintf('found:%d', $held->count())),
        met: static fn(Obstacle $why): WhichArmTheCredentialsTook => new WhichArmTheCredentialsTook(sprintf('met:%s', $why->value)),
    )->said;

    expect($fold(WhatWasFoundOfTheCredentials::found(TheCredentialsHeld::of(aStoreThatProtects()))))->toBe('found:0')
        ->and($fold(WhatWasFoundOfTheCredentials::met(Obstacle::StackDidNotAnswer)))->toBe(sprintf('met:%s', Obstacle::StackDidNotAnswer->value));
});
