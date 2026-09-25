<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\AnOriginIsUnnamed;
use Modules\Kernel\Api\WhatItReplaced;
use Modules\Kernel\Api\WhoPutItThere;

use function sprintf;

/** One line carried out of an arm. */
final readonly class WhichOriginArm
{
    public function __construct(public string $said) {}
}

/** Which arm an origin takes, and what it hands over. */
function theOriginArm(WhoPutItThere $origin): string
{
    return $origin->whichever(
        bundled: static fn(): WhichOriginArm => new WhichOriginArm('bundled'),
        operator: static fn(): WhichOriginArm => new WhichOriginArm('operator'),
        plugin: static fn(string $named): WhichOriginArm => new WhichOriginArm(sprintf('plugin:%s', $named)),
        unknown: static fn(string $why): WhichOriginArm => new WhichOriginArm(sprintf('unknown:%s', $why)),
        overridden: static fn(string $named, WhatItReplaced $replaced): WhichOriginArm => new WhichOriginArm(sprintf('overridden:%s from %s', $named, theOriginArm($replaced->from()))),
        orphaned: static fn(string $named): WhichOriginArm => new WhichOriginArm(sprintf('orphaned:%s', $named)),
    )->said;
}

it('takes the arm it was built as, trimmed, and hands each what it holds', function (): void {
    expect(theOriginArm(WhoPutItThere::bundled()))->toBe('bundled')
        ->and(theOriginArm(WhoPutItThere::operator()))->toBe('operator')
        ->and(theOriginArm(WhoPutItThere::plugin(' plex ')))->toBe('plugin:plex')
        ->and(theOriginArm(WhoPutItThere::unknown(' rebuilt ')))->toBe('unknown:rebuilt')
        ->and(theOriginArm(WhoPutItThere::overridden(' plex ', WhatItReplaced::nothingSet(WhoPutItThere::operator()))))->toBe('overridden:plex from operator')
        ->and(theOriginArm(WhoPutItThere::orphaned(' plex ')))->toBe('orphaned:plex');
});

it('refuses a plugin with no name on every arm that names one', function (): void {
    expect(fn(): WhoPutItThere => WhoPutItThere::plugin(' '))->toThrow(AnOriginIsUnnamed::class)
        ->and(fn(): WhoPutItThere => WhoPutItThere::overridden(' ', WhatItReplaced::nothingSet(WhoPutItThere::bundled())))->toThrow(AnOriginIsUnnamed::class)
        ->and(fn(): WhoPutItThere => WhoPutItThere::orphaned(''))->toThrow(AnOriginIsUnnamed::class)
        ->and(fn(): WhoPutItThere => WhoPutItThere::unknown(' '))->toThrow(AnOriginIsUnnamed::class);
});
