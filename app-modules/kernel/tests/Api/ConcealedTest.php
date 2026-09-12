<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use Attribute;

use function expect;
use function it;

use Modules\Kernel\Api\Concealed;
use ReflectionClass;

it('N4-R18 — goes on a class and only on a class', function (): void {
    // An attribute's target is the whole of its shape, and getting it wrong is
    // silent: `TARGET_ALL` would let this be written above a method or a
    // property, where the rule that reads it looks at classes and would never
    // see it. Somebody would have declared a screen concealed, watched the gate
    // pass, and shipped a screen the platform still snapshots.
    $attributes = new ReflectionClass(Concealed::class)->getAttributes(Attribute::class);

    expect($attributes)->toHaveCount(1)
        ->and($attributes[0]->newInstance()->flags)->toBe(Attribute::TARGET_CLASS);
});

it('N4-R18 — carries nothing, because which screens is the whole question', function (): void {
    // Deliberately empty. A parameter here — a reason, a severity, a list of
    // what to hide — would be a second place to get the answer wrong, and the
    // native call it feeds takes none of that: capture is blocked or it is not.
    expect(new ReflectionClass(Concealed::class)->getConstructor())->toBeNull();
});
