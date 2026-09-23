<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\OriginSaysNothing;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\WhereItComesFrom;

use function sprintf;

/**
 * Sonarr's origin, with any one word replaced.
 *
 * @param array<string, string> $instead
 */
function sonarrsOrigin(array $instead = []): WhereItComesFrom
{
    $words = [...[
        'name' => 'Sonarr',
        'image' => 'lscr.io/linuxserver/sonarr',
        'pinned' => '4.0.15',
        'upstream' => 'https://github.com/Sonarr/Sonarr',
        'licence' => 'GPL-3.0-only',
    ], ...$instead];

    return WhereItComesFrom::declared(
        ServiceId::called('sonarr'),
        $words['name'],
        $words['image'],
        $words['pinned'],
        $words['upstream'],
        $words['licence'],
    );
}

it('N11-R6 — carries its image, its pin, its upstream and its licence, each apart', function (): void {
    $origin = sonarrsOrigin();

    expect($origin->service()->named())->toBe('sonarr')
        ->and($origin->name())->toBe('Sonarr')
        ->and($origin->image())->toBe('lscr.io/linuxserver/sonarr')
        ->and($origin->pinned())->toBe('4.0.15')
        ->and($origin->upstream())->toBe('https://github.com/Sonarr/Sonarr')
        ->and($origin->licence())->toBe('GPL-3.0-only');
});

it('N11-R7 — refuses an origin with any word blank, naming which', function (string $field): void {
    // A licence missing from one row reads as a licence nobody checked, and the
    // other three are the same argument about a different question.
    expect(fn(): WhereItComesFrom => sonarrsOrigin([$field => '  ']))
        ->toThrow(OriginSaysNothing::class, sprintf('`%s`', $field));
})->with(['name', 'image', 'pinned', 'upstream', 'licence']);
