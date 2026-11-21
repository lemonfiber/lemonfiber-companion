<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\ADownloadItWouldInterrupt;
use Modules\Kernel\Api\HowADownloadComes;
use Modules\Kernel\Api\InFlightSaysNothing;

use function sprintf;

it('names one still coming down inside a stop with its client and how far along it is, as the stack gave them', function (): void {
    $download = ADownloadItWouldInterrupt::inFlight('A film', HowADownloadComes::Usenet, 40);

    expect($download->name)->toBe('A film')
        ->and($download->by)->toBe(HowADownloadComes::Usenet)
        ->and($download->progress)->toBe(40)
        ->and(ADownloadItWouldInterrupt::inFlight('A show', HowADownloadComes::Torrent, 0)->progress)->toBe(0)
        ->and(ADownloadItWouldInterrupt::inFlight('A show', HowADownloadComes::Torrent, 100)->progress)->toBe(100);
});

it('refuses one inside a stop with a blank name, or a share outside none to all of it', function (string $name, int $progress, string $field): void {
    expect(static fn(): ADownloadItWouldInterrupt => ADownloadItWouldInterrupt::inFlight($name, HowADownloadComes::Torrent, $progress))
        ->toThrow(InFlightSaysNothing::class, sprintf('`%s`', $field));
})->with([
    'a blank name' => [' ', 40, 'name'],
    'below none' => ['A film', -1, 'progress'],
    'past all of it' => ['A film', 101, 'progress'],
]);
