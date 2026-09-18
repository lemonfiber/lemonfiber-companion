<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\Ability;
use Modules\Kernel\Api\AbilityIsUnnamed;

it('carries the name the API declared', function (): void {
    expect(Ability::of('backups.run')->named())->toBe('backups.run');
    expect(Ability::of('  backups.run  ')->named())->toBe('backups.run');
});

it('refuses a capability with no name', function (): void {
    // An unnamed capability matches nothing, so every later question about it
    // answers "the stack cannot do this" — which is reserved for a stack
    // that genuinely does not have it, and this is a stack that does.
    expect(fn(): Ability => Ability::of('   '))
        ->toThrow(AbilityIsUnnamed::class, 'empty string');
});

it('is the same ability, and is not another', function (): void {
    expect(Ability::of('backups.run')->is(Ability::of('backups.run')))->toBeTrue();
    expect(Ability::of('backups.run')->is(Ability::of('tunnel.rotate')))->toBeFalse();
});
