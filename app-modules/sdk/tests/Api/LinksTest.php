<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function expect;
use function it;

use Lemonfiber\Sdk\Envelope\Envelope;
use Modules\Kernel\Api\TheLinks;
use Modules\Sdk\Api\Links;
use Modules\Sdk\Api\LinksAreUnreadable;

/**
 * A `wiring` envelope with these links and these capabilities nothing fills.
 *
 * `wiring` is stood in for and not judged: each case spoils one row the contract fixes, to show the reader refuses it by its position, and the payloads a stack does send are judged in `LinkingContractTest`.
 *
 * @param list<mixed> $wired
 * @param list<mixed> $unfilled
 *
 * @return Envelope<mixed>
 */
function aWiringSaying(array $wired, array $unfilled): Envelope
{
    return new Envelope(1, 'wiring', ['wired' => $wired, 'unfilled' => $unfilled]);
}

it('refuses a row it cannot read by its position, first or later, in every list', function (): void {
    $link = ['by' => 'seerr', 'reaches' => ['how' => 'by-name', 'service' => 'tdarr', 'why' => 'It is named in its settings.']];
    $loss = ['by' => 'seerr', 'capability' => 'transcoder'];
    $asked = ['how' => 'asked', 'capability' => 'download-client', 'services' => ['qbittorrent'], 'settled' => ['settled' => 'outright'], 'origins' => ['qbittorrent' => ['origin' => 'bundled']]];
    $refused = [
        'Row 1 of `wired` in the wiring envelope has no readable `reaches`.' => aWiringSaying([$link, 'seerr'], []),
        'Row 2 of `wired` in the wiring envelope has no readable `by`.' => aWiringSaying([$link, $link, [...$link, 'by' => ' ']], []),
        'Row 1 of `wired` in the wiring envelope has no readable `origins`.' => aWiringSaying([$link, [...$link, 'reaches' => [...$asked, 'origins' => [' ' => ['origin' => 'bundled']]]]], []),
        'Row 2 of `wired` in the wiring envelope has no readable `origins`.' => aWiringSaying([$link, $link, [...$link, 'reaches' => [...$asked, 'origins' => [['origin' => 'bundled']]]]], []),
        'Row 1 of `unfilled` in the wiring envelope has no readable `by`.' => aWiringSaying([], [$loss, 'seerr']),
        'Row 2 of `unfilled` in the wiring envelope has no readable `capability`.' => aWiringSaying([], [$loss, $loss, [...$loss, 'capability' => ' ']]),
    ];

    foreach ($refused as $message => $wiring) {
        expect(static fn(): TheLinks => Links::in($wiring))->toThrow(LinksAreUnreadable::class, $message, $message);
    }
});
