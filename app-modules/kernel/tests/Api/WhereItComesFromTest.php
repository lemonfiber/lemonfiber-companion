<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\ImageDigest;
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

it('names no digest until one is given, and keeps every other word when it is', function (): void {
    $pinned = sonarrsOrigin()->pinnedAt(ImageDigest::of('sha256:abc'));

    expect(sonarrsOrigin()->digest())->toBe('')
        ->and($pinned->digest())->toBe('sha256:abc')
        ->and($pinned->service()->named())->toBe('sonarr')
        ->and($pinned->name())->toBe('Sonarr')
        ->and($pinned->image())->toBe('lscr.io/linuxserver/sonarr')
        ->and($pinned->pinned())->toBe('4.0.15')
        ->and($pinned->upstream())->toBe('https://github.com/Sonarr/Sonarr')
        ->and($pinned->licence())->toBe('GPL-3.0-only');
});

it('refuses a digest given blank', function (): void {
    expect(fn(): WhereItComesFrom => sonarrsOrigin()->pinnedAt(ImageDigest::of('  ')))
        ->toThrow(OriginSaysNothing::class, '`digest`');
});

it('holds a digest as the stack spelled it', function (): void {
    expect(ImageDigest::of('sha256:abc')->said())->toBe('sha256:abc');
});
