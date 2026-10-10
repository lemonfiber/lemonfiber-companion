<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_map;
use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\APrivilegedShape;
use Modules\Kernel\Api\AShapeTaken;
use Modules\Kernel\Api\PluginLines;
use Modules\Kernel\Api\PluginSaysNothing;
use Modules\Kernel\Api\TheShapesTaken;

use function sprintf;

/** A service taking the egress guard's shape, approved as the stack spells it. */
function aGuardOf(string $service): AShapeTaken
{
    return AShapeTaken::by($service, APrivilegedShape::EgressGuard, PluginLines::under('grants', 'NET_ADMIN'), PluginLines::under('devices', '/dev/net/tun'), sprintf('egress-guard@%s', $service));
}

/**
 * Each service of these, by name.
 *
 * @return list<string>
 */
function theServicesOf(TheShapesTaken $taken): array
{
    return array_map(static fn(AShapeTaken $one): string => $one->service(), iterator_to_array($taken, preserve_keys: false));
}

it('keeps the service, the shape, what it is granted and given, and what approving it is written as', function (): void {
    $guard = aGuardOf('gluetun');

    expect($guard->service())->toBe('gluetun')
        ->and($guard->shape())->toBe(APrivilegedShape::EgressGuard)
        ->and(iterator_to_array($guard->grants(), preserve_keys: false))->toBe(['NET_ADMIN'])
        ->and(iterator_to_array($guard->devices(), preserve_keys: false))->toBe(['/dev/net/tun'])
        ->and($guard->approval())->toBe('egress-guard@gluetun')
        ->and(APrivilegedShape::EgressGuard->neededFor())->toBe('plugins.shape.egress-guard');
});

it('refuses a shape taken by no service, or with no approval to give', function (): void {
    expect(static fn(): AShapeTaken => AShapeTaken::by(' ', APrivilegedShape::EgressGuard, PluginLines::none(), PluginLines::none(), 'egress-guard@gluetun'))
        ->toThrow(PluginSaysNothing::class, '`service`')
        ->and(static fn(): AShapeTaken => AShapeTaken::by('gluetun', APrivilegedShape::EgressGuard, PluginLines::none(), PluginLines::none(), ''))
        ->toThrow(PluginSaysNothing::class, '`approval`');
});

it('lists each approval in the stack\'s order, and leaves out exactly the services an approval names', function (): void {
    $taken = TheShapesTaken::these(aGuardOf('gluetun'), aGuardOf('wireguard'));

    expect(iterator_to_array($taken->approvals(), preserve_keys: false))->toBe(['egress-guard@gluetun', 'egress-guard@wireguard'])
        ->and(theServicesOf($taken->leftOutOf(PluginLines::under('approved', 'egress-guard@wireguard', 'library@hooks.example.com'))))->toBe(['gluetun'])
        ->and(theServicesOf($taken->leftOutOf(PluginLines::none())))->toBe(['gluetun', 'wireguard'])
        ->and($taken->leftOutOf(PluginLines::under('approved', 'egress-guard@gluetun', 'egress-guard@wireguard'))->isEmpty())->toBeTrue()
        ->and($taken->isEmpty())->toBeFalse()
        ->and(TheShapesTaken::these()->isEmpty())->toBeTrue();
});
