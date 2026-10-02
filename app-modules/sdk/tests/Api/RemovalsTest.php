<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function array_diff_key;
use function expect;
use function implode;
use function it;

use Lemonfiber\Sdk\Envelope\Envelope;
use Modules\Kernel\Api\ARemoval;
use Modules\Kernel\Api\RemovalSaysNothing;
use Modules\Sdk\Api\RemovalIsUnreadable;
use Modules\Sdk\Api\Removals;

use function sprintf;

use Tests\Support\WhatTheContractAccepts;

/**
 * A `removal` envelope holding whatever the case under test is about.
 *
 * @return Envelope<mixed>
 */
function removalSaying(mixed $data): Envelope
{
    return new Envelope(1, 'removal', $data);
}

/**
 * What taking Anna out would cost, with nobody taken out.
 *
 * @return array<string, mixed>
 */
function aPlainRemoval(): array
{
    return [
        'rehearsed' => false,
        'name' => 'Anna',
        'confirmed' => false,
        'requests' => 2,
        'asks-through-the-request-service' => true,
        'revoked' => 'nothing',
        'findings' => [],
    ];
}

/**
 * Everything a payload says, as one line.
 *
 * @param array<string, mixed> $data
 */
function everythingTheRemovalCarries(array $data): string
{
    $removal = Removals::in(removalSaying($data));
    $findings = [];

    foreach ($removal->findings() as $finding) {
        $findings[] = $finding;
    }

    return sprintf(
        '%s|%s|%d|%s|%s|%s',
        $removal->who()->name(),
        $removal->wasCarriedOut() ? 'carried out' : 'described',
        $removal->requests(),
        $removal->asksThroughTheRequestService() ? 'asks' : 'does not ask',
        $removal->revoked()->value,
        implode(',', $findings),
    );
}

it('reads what taking somebody out would cost, and what it did', function (): void {
    $done = [...aPlainRemoval(), 'confirmed' => true, 'revoked' => 'media-server-only', 'asks-through-the-request-service' => false, 'findings' => ['The request service did not answer']];

    expect(everythingTheRemovalCarries(aPlainRemoval()))->toBe('Anna|described|2|asks|nothing|')
        ->and(everythingTheRemovalCarries($done))->toBe('Anna|carried out|2|does not ask|media-server-only|The request service did not answer');
});

it('reads how far it reached as the stack wrote it, whatever confirmed says beside it', function (): void {
    expect(everythingTheRemovalCarries([...aPlainRemoval(), 'revoked' => 'everywhere']))->toBe('Anna|described|2|asks|everywhere|');
});

it('refuses a payload that is not a table', function (): void {
    expect(fn(): ARemoval => Removals::in(removalSaying('a removal')))->toThrow(RemovalIsUnreadable::class, 'no readable `data`');
});

it('refuses a missing or wrongly typed field, naming it', function (): void {
    $spoiled = [
        'name' => ' ',
        'confirmed' => 'yes',
        'requests' => '2',
        'asks-through-the-request-service' => 'no',
        'revoked' => ' ',
        'findings' => 'none',
    ];

    foreach ($spoiled as $field => $value) {
        expect(fn(): ARemoval => Removals::in(removalSaying(array_diff_key(aPlainRemoval(), [$field => true]))))
            ->toThrow(RemovalIsUnreadable::class, sprintf('no readable `%s`', $field))
            ->and(fn(): ARemoval => Removals::in(removalSaying([...aPlainRemoval(), $field => $value])))
            ->toThrow(RemovalIsUnreadable::class, sprintf('no readable `%s`', $field));
    }
});

it('refuses findings that are not each a sentence', function (): void {
    foreach ([['It went', ' '], ['It went', 3]] as $findings) {
        expect(fn(): ARemoval => Removals::in(removalSaying([...aPlainRemoval(), 'findings' => $findings])))
            ->toThrow(RemovalIsUnreadable::class, 'no readable `findings`');
    }
});

it('refuses a reach it has no case for, never reading the nearest one', function (): void {
    expect(fn(): ARemoval => Removals::in(removalSaying([...aPlainRemoval(), 'revoked' => 'halfway'])))
        ->toThrow(RemovalIsUnreadable::class, 'says `revoked` is `halfway`, and this app reads `everywhere`, `media-server-only`, `nothing`');
});

it('refuses requests below none, as the kernel would', function (): void {
    expect(fn(): ARemoval => Removals::in(removalSaying([...aPlainRemoval(), 'requests' => -1])))->toThrow(RemovalSaysNothing::class, 'its `requests` at -1');
});

it('reads only payloads the contract would accept as a removal', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('RemovalEnvelope', ['api_version' => 1, 'kind' => 'removal', 'data' => aPlainRemoval()]))->toBe([]);
});
