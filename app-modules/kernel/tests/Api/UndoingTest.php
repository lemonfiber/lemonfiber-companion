<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_map;
use function expect;
use function it;

use Modules\Kernel\Api\Undoing;

it('is the wire boolean given a name and a word', function (): void {
    // The contract sends `reversible: bool`. It arrives here as a case because
    // N2-R4 asks the app to *state* whether a repair can be undone, and a
    // boolean has nothing to state — `false` renders as an unticked box where
    // a sentence about permanence belongs.
    expect(array_map(
        static fn(Undoing $undoing): string => $undoing->value,
        Undoing::cases(),
    ))->toBe(['permanent', 'possible']);
});

it('declares permanent first, like every other enum here', function (): void {
    // Worst first, the way `Conclusion` and `Overall` are, so a reader meets
    // the case that needs saying before the one that does not.
    expect(Undoing::cases())->toBe([Undoing::Permanent, Undoing::Possible]);
});
