<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function expect;
use function it;

use Lemonfiber\Sdk\Envelope\Envelope;
use Modules\Kernel\Api\AFill;
use Modules\Sdk\Api\SubstitutionIsUnreadable;
use Modules\Sdk\Api\Substitutions;

/**
 * A `substitution` envelope whose choice has these fields changed.
 *
 * `substitution` is stood in for and not judged: each case spoils one row the contract fixes, to show the reader refuses it by its position, and the payloads a stack does send are judged in `ChoosingAFillerContractTest`.
 *
 * @param array<string, mixed> $choice
 *
 * @return Envelope<mixed>
 */
function aChoiceSaying(array $choice): Envelope
{
    return new Envelope(1, 'substitution', [
        'agreement' => 'a-reading',
        'applied' => false,
        'rehearsed' => false,
        'substitution' => [...['asked_by' => ['seerr'], 'capability' => 'media-server', 'leaves_unfilled' => [], 'now' => 'plex', 'was' => null], ...$choice],
    ]);
}

it('refuses a row it cannot read by its position, first or later, in every list', function (): void {
    $loss = ['by' => 'tdarr', 'capability' => 'transcoder'];
    $refused = [
        'Row 1 of `asked_by` in the substitution envelope has no readable `service`.' => ['asked_by' => ['seerr', ' ']],
        'Row 2 of `asked_by` in the substitution envelope' => ['asked_by' => ['seerr', 'bazarr', 3]],
        'Row 1 of `leaves_unfilled` in the substitution envelope has no readable `by`.' => ['leaves_unfilled' => [$loss, 'tdarr']],
        'Row 2 of `leaves_unfilled` in the substitution envelope has no readable `capability`.' => ['leaves_unfilled' => [$loss, $loss, [...$loss, 'capability' => ' ']]],
    ];

    foreach ($refused as $message => $choice) {
        expect(static fn(): AFill => Substitutions::fillIn(aChoiceSaying($choice)))->toThrow(SubstitutionIsUnreadable::class, $message, $message);
    }
});
