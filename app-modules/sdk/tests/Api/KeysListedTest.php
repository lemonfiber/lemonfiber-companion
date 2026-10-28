<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function expect;
use function it;

use Lemonfiber\Sdk\Envelope\Envelope;
use Modules\Kernel\Api\TheKeysForApps;
use Modules\Sdk\Api\KeysAreUnreadable;
use Modules\Sdk\Api\KeysListed;

/**
 * A key as a stack lists it, with one change made to it.
 *
 * @param array<string, mixed> $change
 *
 * @return array<string, mixed>
 */
function aKeyListedWith(array $change = []): array
{
    return [...['name' => 'den', 'scope' => 'act', 'purpose' => 'home-assistant', 'state' => 'active', 'minted' => '2026-10-01T09:00:00', 'used' => null, 'member_minted' => false, 'revoked' => null], ...$change];
}

/**
 * A `keys` envelope listing these keys under this sentence on purposes.
 *
 * `keys` is stood in for and not judged: each case spoils one field the contract fixes, to show the reader refuses it by its position, and the payloads a stack does send are judged in `KeepingKeysForAppsContractTest`.
 *
 * @param list<mixed> $keys
 *
 * @return Envelope<mixed>
 */
function keysSaying(array $keys, string $purposes = 'A purpose is what whoever minted the key said.'): Envelope
{
    return new Envelope(1, 'keys', ['purposes' => $purposes, 'rehearsed' => false, 'revoked' => null, 'keys' => $keys]);
}

it('refuses a key it cannot read by its position, first or later', function (): void {
    expect(static fn(): TheKeysForApps => KeysListed::in(keysSaying([aKeyListedWith(), aKeyListedWith(['name' => ' '])])))
        ->toThrow(KeysAreUnreadable::class, 'Entry 1 of `keys` in the keys envelope has no readable `name`.')
        ->and(static fn(): TheKeysForApps => KeysListed::in(keysSaying([aKeyListedWith(), aKeyListedWith(), 'den'])))
        ->toThrow(KeysAreUnreadable::class, 'Entry 2 of `keys`');
});

it('refuses a member\'s key that names no member', function (): void {
    expect(static fn(): TheKeysForApps => KeysListed::in(keysSaying([aKeyListedWith(['scope' => 'member'])])))
        ->toThrow(KeysAreUnreadable::class, 'Entry 0 of `keys` in the keys envelope has no readable `scope`.');
});

it('refuses a blank sentence on what a purpose is worth', function (): void {
    expect(static fn(): TheKeysForApps => KeysListed::in(keysSaying([aKeyListedWith()], ' ')))
        ->toThrow(KeysAreUnreadable::class, 'The keys envelope has no readable `purposes`.');
});
