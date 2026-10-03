<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function expect;
use function it;

use Lemonfiber\Sdk\Envelope\Envelope;
use Modules\Kernel\Api\ADownloadLetGo;
use Modules\Kernel\Api\WhatLettingItGoCosts;
use Modules\Sdk\Api\StopSeedingIsUnreadable;
use Modules\Sdk\Api\WhatLettingItGoComesTo;

use function sprintf;

use Tests\Support\WhatTheContractAccepts;

/**
 * A `stop-seeding` envelope offering to let one download go, with the download changed where a case says.
 *
 * @param  array<mixed> $download
 * @return Envelope<mixed>
 */
function anOfferToLetGoSaying(array $download = [], mixed $goes = 'The copy in the downloads tree goes with it'): Envelope
{
    return new Envelope(1, 'stop-seeding', [
        'agreement' => 'stop-seeding-show-season1-4000',
        'rehearsed' => false,
        'download' => [
            'bytes' => 4_000,
            'consequence' => 'Your ratio on it stops growing',
            'name' => 'Show.Season1',
            'standing' => ['standing' => 'seeding', 'ratio' => 80],
            ...$download,
        ],
        'goes' => $goes,
    ]);
}

/**
 * A `stop-seeding` envelope reporting what became of the download, with the report changed where a case says.
 *
 * @param  array<mixed> $gone
 * @return Envelope<mixed>
 */
function aReportOfLettingGoSaying(array $gone): Envelope
{
    return new Envelope(1, 'stop-seeding', [
        'agreement' => 'stop-seeding-show-season1-4000',
        'rehearsed' => false,
        'download' => ['bytes' => 4_000, 'name' => 'Show.Season1', 'standing' => ['standing' => 'seeding', 'ratio' => 80]],
        'goes' => 'The copy in the downloads tree goes with it',
        'gone' => ['name' => 'Show.Season1', 'bytes' => 4_000, 'rehearsed' => false, ...$gone],
    ]);
}

it('stands in for a stack with an offer and a report the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('StopSeedingEnvelope', ['api_version' => 1, 'kind' => 'stop-seeding', 'data' => anOfferToLetGoSaying()->data]))->toBe([])
        ->and(WhatTheContractAccepts::complaintsAbout('StopSeedingEnvelope', ['api_version' => 1, 'kind' => 'stop-seeding', 'data' => aReportOfLettingGoSaying([])->data]))->toBe([]);
});

it('refuses an offer with a field left blank or below nothing, naming where it sits', function (array $download, mixed $goes, string $where): void {
    expect(static fn(): WhatLettingItGoCosts => WhatLettingItGoComesTo::offerIn(anOfferToLetGoSaying($download, $goes)))
        ->toThrow(StopSeedingIsUnreadable::class, sprintf('The stop-seeding envelope has no readable `%s`.', $where));
})->with([
    'what goes, blank' => [[], ' ', 'goes'],
    'what goes, empty' => [[], '', 'goes'],
    'the name, blank' => [['name' => ' '], 'It goes', 'download.name'],
    'the size, below nothing' => [['bytes' => -1], 'It goes', 'download.bytes'],
    'the ratio, below nothing' => [['standing' => ['standing' => 'seeding', 'ratio' => -1]], 'It goes', 'download.standing.ratio'],
    'the standing, blank' => [['standing' => ['standing' => ' ']], 'It goes', 'download.standing.standing'],
]);

it('refuses a report with a field left blank or below nothing, naming where it sits', function (array $gone, string $where): void {
    expect(static fn(): ADownloadLetGo => WhatLettingItGoComesTo::goneIn(aReportOfLettingGoSaying($gone)))
        ->toThrow(StopSeedingIsUnreadable::class, sprintf('The stop-seeding envelope has no readable `%s`.', $where));
})->with([
    'the name, blank' => [['name' => ' '], 'gone.name'],
    'the size, below nothing' => [['bytes' => -1], 'gone.bytes'],
    'whether it was rehearsed, not a yes or no' => [['rehearsed' => 'no'], 'gone.rehearsed'],
]);

it('reads a size of nothing as nothing, not as a refusal', function (): void {
    expect(WhatLettingItGoComesTo::goneIn(aReportOfLettingGoSaying(['bytes' => 0]))->bytes())->toBe(0);
});
