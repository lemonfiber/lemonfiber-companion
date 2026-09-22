<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\WhatAReleaseDelivers;

use function sprintf;

/**
 * The two arms, and the blank that is not a third.
 *
 * A release the stack described and a release it said nothing about are
 * different answers, and the whole of this type is that a screen cannot draw
 * them alike. What it must not become is a third state — a row holding an
 * empty sentence, which is what a blank arriving on the wire would make if it
 * were carried rather than read as silence.
 */

/** One arm's name, so the fold can be asserted on rather than counted. */
final readonly class WhichArmItWas
{
    public function __construct(public string $said) {}
}

/**
 * Which arm a value reaches, and what it hands over.
 */
function whichArmADeliversReached(WhatAReleaseDelivers $delivers): string
{
    return $delivers->either(
        said: static fn(string $prose): WhichArmItWas => new WhichArmItWas(sprintf('said:%s', $prose)),
        saidNothing: static fn(): WhichArmItWas => new WhichArmItWas('silent'),
    )->said;
}

it('hands the stack\'s own words to the arm that has them', function (): void {
    expect(whichArmADeliversReached(WhatAReleaseDelivers::said('Adds series search.')))
        ->toBe('said:Adds series search.');
});

it('reaches the silent arm where the stack said nothing', function (): void {
    expect(whichArmADeliversReached(WhatAReleaseDelivers::saidNothing()))->toBe('silent');
});

it('reads a blank as silence rather than carrying an empty sentence', function (): void {
    // The case that would otherwise reach a screen. A field present and empty
    // is the stack having said nothing, and a row drawing it would show a gap
    // where a sentence belongs — which reads as a screen that failed to load
    // something rather than as a release with nothing to report.
    expect(whichArmADeliversReached(WhatAReleaseDelivers::said('')))->toBe('silent')
        ->and(whichArmADeliversReached(WhatAReleaseDelivers::said('   ')))->toBe('silent');
});

it('trims what it carries, so a row is not indented by the wire', function (): void {
    expect(whichArmADeliversReached(WhatAReleaseDelivers::said('  Adds series search.  ')))
        ->toBe('said:Adds series search.');
});
