<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function expect;
use function implode;
use function it;

use Lemonfiber\Sdk\Envelope\Envelope;
use Modules\Kernel\Api\ADirectionHeld;
use Modules\Kernel\Api\TheLineAsChanged;
use Modules\Sdk\Api\BandwidthIsUnreadable;
use Modules\Sdk\Api\WhatTheLineCameTo;

use function sprintf;

use Tests\Support\TheWordCarriedOut;
use Tests\Support\WhatTheContractAccepts;

/**
 * A rehearsal of a change, with one client that answered and one that did not.
 *
 * @return array<string, mixed>
 */
function aRehearsedChangeToTheLine(): array
{
    return [
        'rehearsed' => true,
        'applied' => false,
        'restraint' => 'limited',
        'means' => 'The stack would take half the line',
        'cautions' => [],
        'untouched' => [],
        'down' => ['limit' => ['as' => 'share', 'at' => 50], 'resolved' => ['is' => 'at', 'bytes_per_second' => 6_250_000], 'says' => 'Half of 100 Mbit/s'],
        'up' => ['limit' => ['as' => 'share', 'at' => 25], 'resolved' => ['is' => 'at', 'bytes_per_second' => 625_000], 'says' => 'A quarter of 20 Mbit/s'],
        'ratio' => 'Seeding back at a quarter slows the ratio',
        'respite' => ['standing' => 'none'],
        'clients' => [
            ['client' => 'qbittorrent', 'pulling' => 'fetching', 'answer' => ['answered' => 'held', 'down' => ['verdict' => 'holding', 'asked' => 6_250_000, 'accepted' => 6_250_000, 'moving' => 4_000_000], 'up' => ['verdict' => 'overrunning', 'asked' => 625_000, 'accepted' => 625_000, 'moving' => 900_000]]],
            ['client' => 'sabnzbd', 'answer' => ['answered' => 'silent', 'said' => 'connection refused']],
        ],
    ];
}

/** One direction, as a line. */
function aDirectionSaid(ADirectionHeld $held): string
{
    $rates = [];

    foreach ($held as $rate) {
        $rates[] = sprintf('%s=%d', $rate->which()->value, $rate->bytesASecond());
    }

    return sprintf('%s [%s]', $held->verdict()->value, implode(' ', $rates));
}

/** Everything a report says beyond the line itself, as one line. */
function everythingTheChangeSays(TheLineAsChanged $report): string
{
    $clients = [];

    foreach ($report as $client) {
        $clients[] = sprintf('%s(%s):%s', $client->client(), $client->pulling()->value, $client->either(
            answered: static fn(ADirectionHeld $down, ADirectionHeld $up): TheWordCarriedOut => new TheWordCarriedOut(sprintf('%s|%s', aDirectionSaid($down), aDirectionSaid($up))),
            silent: static fn(string $said): TheWordCarriedOut => new TheWordCarriedOut(sprintf('silent %s', $said)),
        )->said);
    }

    return sprintf(
        '%s|%s|%s|%s|%s',
        $report->did()->value,
        $report->line()->means(),
        $report->lift()->either(
            none: static fn(): TheWordCarriedOut => new TheWordCarriedOut('no lift'),
            inForce: static fn(int $seconds): TheWordCarriedOut => new TheWordCarriedOut(sprintf('lifted %d', $seconds)),
            expired: static fn(int $seconds): TheWordCarriedOut => new TheWordCarriedOut(sprintf('ran out %d', $seconds)),
        )->said,
        $report->lift()->says(),
        implode(',', $clients),
    );
}

/** @param array<mixed> $data */
function theChangeReadFrom(array $data): TheLineAsChanged
{
    return WhatTheLineCameTo::in(new Envelope(1, 'bandwidth', $data));
}

it('reads a rehearsal: the line it would leave, each client\'s answer, and one that did not answer', function (): void {
    expect(everythingTheChangeSays(theChangeReadFrom(aRehearsedChangeToTheLine())))
        ->toBe('rehearsed|The stack would take half the line|no lift||qbittorrent(fetching):holding [asked=6250000 accepted=6250000 moving=4000000]|overrunning [asked=625000 accepted=625000 moving=900000],sabnzbd(unsaid):silent connection refused');
});

it('reads a change that was applied, with a lift in force or run out, and figures a client did not give', function (array $respite, string $said): void {
    $applied = [
        ...aRehearsedChangeToTheLine(),
        'rehearsed' => false,
        'applied' => true,
        'respite' => $respite,
        'respite_says' => 'No limits until 21:10',
        'clients' => [['client' => 'qbittorrent', 'pulling' => 'stopped', 'answer' => ['answered' => 'held', 'down' => ['verdict' => 'unasked'], 'up' => ['verdict' => 'nothing-to-limit', 'asked' => null]]]],
    ];

    expect(everythingTheChangeSays(theChangeReadFrom($applied)))
        ->toBe(sprintf('applied|The stack would take half the line|%s|No limits until 21:10|qbittorrent(stopped):unasked []|nothing-to-limit []', $said));
})->with([
    'in force' => [['standing' => 'in-force', 'seconds' => 600], 'lifted 600'],
    'run out' => [['standing' => 'expired', 'seconds' => 30], 'ran out 30'],
]);

it('reads a run that wrote nothing as a reading', function (): void {
    expect(theChangeReadFrom([...aRehearsedChangeToTheLine(), 'rehearsed' => false, 'clients' => []])->did()->value)->toBe('only-read');
});

it('refuses a report it cannot read, naming where', function (array $changed, string $where): void {
    expect(static fn(): TheLineAsChanged => theChangeReadFrom([...aRehearsedChangeToTheLine(), ...$changed]))
        ->toThrow(BandwidthIsUnreadable::class, sprintf('`%s`', $where));
})->with([
    'no clients' => [['clients' => null], 'clients'],
    'a client that is not one' => [['clients' => ['qbittorrent']], 'clients.0'],
    'a client named nothing' => [['clients' => [['client' => ' ', 'answer' => ['answered' => 'silent', 'said' => 'no']]]], 'clients.0.client'],
    'a client with no answer' => [['clients' => [['client' => 'qbittorrent']]], 'clients.0.answer'],
    'an answer of no shape' => [['clients' => [['client' => 'qbittorrent', 'answer' => []]]], 'clients.0.answer.answered'],
    'an answer of a shape it does not read' => [['clients' => [['client' => 'qbittorrent', 'answer' => ['answered' => 'shrugged']]]], 'clients.0.answer.answered'],
    'a silence saying nothing' => [['clients' => [['client' => 'qbittorrent', 'answer' => ['answered' => 'silent', 'said' => ' ']]]], 'clients.0.answer.said'],
    'a direction missing' => [['clients' => [['client' => 'qbittorrent', 'answer' => ['answered' => 'held', 'down' => ['verdict' => 'holding']]]]], 'clients.0.answer.up'],
    'a verdict missing' => [['clients' => [['client' => 'qbittorrent', 'answer' => ['answered' => 'held', 'down' => [], 'up' => ['verdict' => 'holding']]]]], 'clients.0.answer.down.verdict'],
    'a verdict it does not read' => [['clients' => [['client' => 'qbittorrent', 'answer' => ['answered' => 'held', 'down' => ['verdict' => 'sulking'], 'up' => ['verdict' => 'holding']]]]], 'clients.0.answer.down.verdict'],
    'a figure that is not a count' => [['clients' => [['client' => 'qbittorrent', 'answer' => ['answered' => 'held', 'down' => ['verdict' => 'holding', 'asked' => '1'], 'up' => ['verdict' => 'holding']]]]], 'clients.0.answer.down.asked'],
    'an accepted figure that is not a count' => [['clients' => [['client' => 'qbittorrent', 'answer' => ['answered' => 'held', 'down' => ['verdict' => 'holding', 'accepted' => true], 'up' => ['verdict' => 'holding']]]]], 'clients.0.answer.down.accepted'],
    'a moving figure that is not a count' => [['clients' => [['client' => 'qbittorrent', 'answer' => ['answered' => 'held', 'down' => ['verdict' => 'holding', 'moving' => 'fast'], 'up' => ['verdict' => 'holding']]]]], 'clients.0.answer.down.moving'],
    'pulling it does not read' => [['clients' => [['client' => 'qbittorrent', 'pulling' => 'seeding', 'answer' => ['answered' => 'silent', 'said' => 'no']]]], 'clients.0.pulling'],
    'pulling that is not words' => [['clients' => [['client' => 'qbittorrent', 'pulling' => 1, 'answer' => ['answered' => 'silent', 'said' => 'no']]]], 'clients.0.pulling'],
    'no rehearsed flag' => [['rehearsed' => 'yes'], 'rehearsed'],
    'no applied flag' => [['applied' => null], 'applied'],
    'no respite' => [['respite' => null], 'respite'],
    'a respite of no standing' => [['respite' => []], 'respite.standing'],
    'a respite standing it does not read' => [['respite' => ['standing' => 'forever']], 'respite.standing'],
    'a lift with no seconds' => [['respite' => ['standing' => 'in-force']], 'respite.seconds'],
    'words for a lift that are not words' => [['respite_says' => 4], 'respite_says'],
]);

it('refuses an envelope whose data is not a payload', function (): void {
    expect(static fn(): TheLineAsChanged => WhatTheLineCameTo::in(new Envelope(1, 'bandwidth', 'nothing')))->toThrow(BandwidthIsUnreadable::class, '`data`');
});

it('judges the payload these cases are built on against the contract', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('BandwidthEnvelope', ['api_version' => 1, 'kind' => 'bandwidth', 'data' => aRehearsedChangeToTheLine()]))->toBe([]);
});
