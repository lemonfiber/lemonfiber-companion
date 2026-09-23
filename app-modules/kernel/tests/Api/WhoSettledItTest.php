<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_map;
use function expect;
use function it;

use Modules\Kernel\Api\WhoSettledIt;

it('spells both cases the way the contract spells them', function (): void {
    expect(array_map(
        static fn(WhoSettledIt $case): string => $case->value,
        WhoSettledIt::cases(),
    ))->toBe(['stack', 'operator']);
});

it('keeps the stack and the operator apart', function (): void {
    // The requirement is this distinction and nothing else. A type with one
    // case, or
    // two cases that compared equal, would let a settlement the stack applied
    // be shown as a decision somebody made — after which an operator stops
    // looking for the choice they never made.
    expect(WhoSettledIt::Stack)->not->toBe(WhoSettledIt::Operator);
});
