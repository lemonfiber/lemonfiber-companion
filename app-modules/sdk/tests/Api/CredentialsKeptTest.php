<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function array_diff_key;
use function expect;
use function implode;
use function it;
use function iterator_to_array;

use Lemonfiber\Sdk\Envelope\Envelope;
use Modules\Kernel\Api\TheCredentialsHeld;
use Modules\Sdk\Api\CredentialsIsUnreadable;
use Modules\Sdk\Api\CredentialsKept;

use function sprintf;

use Tests\Support\WhatTheContractAccepts;

/**
 * A `credentials` envelope holding whatever the case under test is about.
 *
 * @return Envelope<mixed>
 */
function credentialsSaying(mixed $data): Envelope
{
    return new Envelope(1, 'credentials', $data);
}

/**
 * One credential with nothing optional said.
 *
 * @return array<string, mixed>
 */
function aPlainCredential(): array
{
    return [
        'name' => 'Indexer API key',
        'state' => 'active',
        'origin' => 'operator',
        'consumers' => ['sonarr'],
        'from' => ['origin' => 'bundled'],
        'location' => '/srv/secrets/indexer',
        'setting' => 'indexer.api_key',
    ];
}

/**
 * What the store protects against, as every payload here says it.
 *
 * @return array<string, mixed>
 */
function aPlainProtection(): array
{
    return ['summary' => 'Plain files', 'against' => ['Other accounts'], 'not_against' => ['You']];
}

/**
 * A payload holding these credentials.
 *
 * @param list<mixed>          $held
 * @param array<string, mixed> $protection
 * @return array<string, mixed>
 */
function aPayloadHolding(array $held, array $protection = []): array
{
    return ['held' => $held, 'protection' => $protection === [] ? aPlainProtection() : $protection];
}

/**
 * Everything a payload says, as lines.
 *
 * @param array<string, mixed> $data
 */
function theCredentialsRead(array $data): string
{
    $held = CredentialsKept::in(credentialsSaying($data));
    $lines = [sprintf(
        '%s|%s|%s',
        $held->protection()->summary(),
        implode(',', iterator_to_array($held->protection()->against(), preserve_keys: false)),
        implode(',', iterator_to_array($held->protection()->notAgainst(), preserve_keys: false)),
    )];

    foreach ($held as $credential) {
        $lines[] = sprintf(
            '%s|%s|%s|%s|%s',
            $credential->name(),
            $credential->state()->value,
            $credential->origin()->value,
            implode(',', iterator_to_array($credential->consumers(), preserve_keys: false)),
            $credential->advisory(),
        );
    }

    return implode("\n", $lines);
}

it('stands in for a stack with a payload the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('CredentialsEnvelope', ['api_version' => 1, 'kind' => 'credentials', 'data' => aPayloadHolding([aPlainCredential()])]))->toBe([]);
});

it('reads every credential in the stack\'s order, with what uses it and what the store protects against', function (): void {
    $second = [...aPlainCredential(), 'name' => 'Usenet password', 'state' => 'invalid', 'origin' => 'service', 'consumers' => ['sabnzbd', 'nzbget'], 'advisory' => 'Refused on Tuesday'];

    expect(theCredentialsRead(aPayloadHolding([aPlainCredential(), $second])))->toBe(
        "Plain files|Other accounts|You\nIndexer API key|active|operator|sonarr|\nUsenet password|invalid|service|sabnzbd,nzbget|Refused on Tuesday",
    );
});

it('reads a credential nothing uses, and an advisory absent or null as empty', function (): void {
    expect(theCredentialsRead(aPayloadHolding([[...aPlainCredential(), 'consumers' => [], 'advisory' => null]])))->toContain("\nIndexer API key|active|operator||")
        ->and(theCredentialsRead(aPayloadHolding([])))->toBe('Plain files|Other accounts|You');
});

it('reads every state and every origin the contract names', function (string $state, string $origin): void {
    expect(theCredentialsRead(aPayloadHolding([[...aPlainCredential(), 'state' => $state, 'origin' => $origin]])))->toContain(sprintf('|%s|%s|', $state, $origin));
})->with([
    ['absent', 'operator'], ['stale', 'service'], ['rotating', 'lemonfiber'], ['superseded', 'operator'],
]);

it('never reads a value revealed beside the list', function (): void {
    $data = [...aPayloadHolding([aPlainCredential()]), 'revealed' => ['name' => 'Indexer API key', 'value' => 'hunter2-never-drawn', 'warning' => 'Shown once']];

    expect(theCredentialsRead($data))->not->toContain('hunter2-never-drawn');
});

it('refuses a payload with no data', function (): void {
    expect(fn(): TheCredentialsHeld => CredentialsKept::in(credentialsSaying('nothing')))->toThrow(CredentialsIsUnreadable::class, '`data`');
});

it('refuses a list or a store that is missing or not what the contract says', function (mixed $data, string $field): void {
    expect(fn(): TheCredentialsHeld => CredentialsKept::in(credentialsSaying($data)))->toThrow(CredentialsIsUnreadable::class, sprintf('no readable `%s`', $field));
})->with([
    [['protection' => ['summary' => 'Plain files', 'against' => [], 'not_against' => []]], 'held'],
    [['held' => 'none', 'protection' => ['summary' => 'Plain files', 'against' => [], 'not_against' => []]], 'held'],
    [['held' => []], 'protection'],
    [['held' => [], 'protection' => 'files'], 'protection'],
    [['held' => [], 'protection' => ['against' => [], 'not_against' => []]], 'summary'],
    [['held' => [], 'protection' => ['summary' => ' ', 'against' => [], 'not_against' => []]], 'summary'],
    [['held' => [], 'protection' => ['summary' => 7, 'against' => [], 'not_against' => []]], 'summary'],
    [['held' => [], 'protection' => ['summary' => 'Plain files', 'not_against' => []]], 'against'],
    [['held' => [], 'protection' => ['summary' => 'Plain files', 'against' => 'all', 'not_against' => []]], 'against'],
    [['held' => [], 'protection' => ['summary' => 'Plain files', 'against' => [' '], 'not_against' => []]], 'against'],
    [['held' => [], 'protection' => ['summary' => 'Plain files', 'against' => [7], 'not_against' => []]], 'against'],
    [['held' => [], 'protection' => ['summary' => 'Plain files', 'against' => []]], 'not_against'],
    [['held' => [], 'protection' => ['summary' => 'Plain files', 'against' => [], 'not_against' => ['']]], 'not_against'],
]);

it('refuses an entry that is not a credential, by its position', function (): void {
    expect(fn(): TheCredentialsHeld => CredentialsKept::in(credentialsSaying(aPayloadHolding([aPlainCredential(), 'a key']))))
        ->toThrow(CredentialsIsUnreadable::class, 'Entry 1 of `held` in the credentials envelope is not a credential');
});

it('refuses a field of a credential that is missing, blank or not what the contract says, by its position', function (string $field, mixed $said): void {
    $broken = $said === 'absent'
        ? array_diff_key(aPlainCredential(), [$field => true])
        : [...aPlainCredential(), $field => $said];

    expect(fn(): TheCredentialsHeld => CredentialsKept::in(credentialsSaying(aPayloadHolding([aPlainCredential(), $broken]))))
        ->toThrow(CredentialsIsUnreadable::class, sprintf('Entry 1 of `held` in the credentials envelope has no readable `%s`', $field));
})->with([
    ['name', 'absent'], ['name', ' '], ['name', 7],
    ['state', 'absent'], ['origin', ''],
    ['consumers', 'absent'], ['consumers', 'sonarr'], ['consumers', [' ']], ['consumers', [7]],
    ['advisory', ' '], ['advisory', false],
]);

it('refuses a word it has no case for, naming the field, the entry and every word it reads', function (string $field, string $said, string $accepts): void {
    expect(fn(): TheCredentialsHeld => CredentialsKept::in(credentialsSaying(aPayloadHolding([aPlainCredential(), [...aPlainCredential(), $field => $said]]))))
        ->toThrow(CredentialsIsUnreadable::class, sprintf('Entry 1 of `held` in the credentials envelope says `%s` is `%s`, and this app reads %s.', $field, $said, $accepts));
})->with([
    ['state', 'expired', '`absent`, `active`, `stale`, `invalid`, `rotating`, `superseded`'],
    ['origin', 'plugin', '`operator`, `service`, `lemonfiber`'],
]);
