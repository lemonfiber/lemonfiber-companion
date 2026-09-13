<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\StackIsNotNamed;
use Modules\Kernel\Api\StackName;

it('refuses a stack an operator cannot tell from another', function (): void {
    // A name is the only thing an operator has to tell two stacks apart by —
    // "192.168.1.42" and "192.168.1.43" are not two names, which is why the
    // address is not allowed to stand in for one (`N1-R15`).
    expect(fn(): StackName => StackName::of('   '))
        ->toThrow(StackIsNotNamed::class, 'no name');
});

it('refuses a name that was never typed at all', function (): void {
    expect(fn(): StackName => StackName::of(''))
        ->toThrow(StackIsNotNamed::class);
});

it('takes the name as the operator typed it, less the accident', function (): void {
    // Somebody typing into a phone produces a trailing space often enough that
    // treating `"The loft "` and `"The loft"` as two names would be reported as
    // the app having lost a stack.
    expect(StackName::of('  The loft  ')->shown())->toBe('The loft');
});

it('keeps the spacing inside a name the operator meant', function (): void {
    expect(StackName::of("My mum's house")->shown())->toBe("My mum's house");
});
