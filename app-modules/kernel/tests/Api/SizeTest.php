<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Size;

use function sprintf;

/** Which of the three a size is, and the figure if it has one (`G10`). */
function howBig(Size $size): string
{
    return $size->either(
        measured: static fn(int $bytes): Code => Code::of(sprintf('measured-%d', $bytes)),
        guessed: static fn(int $bytes): Code => Code::of(sprintf('guessed-%d', $bytes)),
        unknown: static fn(): Code => Code::of('unknown'),
    )->shown();
}

it('D7-R3 — a measured size hands over the figure and says it was measured', function (): void {
    expect(howBig(Size::measured(1_048_576)))->toBe('measured-1048576');
});

it('D7-R4 — an estimate hands over the figure and says it is one', function (): void {
    // The whole reason there is no `bytes()`. A screen showing a guess as a
    // measurement has to have been handed the distinction and dropped it, which
    // is visible in review in a way a missing call is not.
    expect(howBig(Size::guessedAt(1_048_576)))->toBe('guessed-1048576');
});

it('tells the same figure measured and guessed apart', function (): void {
    // Same number, different answer. A fold that read the figure and not the
    // accuracy would pass both of the tests above and fail this one — which is
    // the failure the label is actually about.
    expect(howBig(Size::measured(512)))->not->toBe(howBig(Size::guessedAt(512)));
});

it('D7-R3 — a size nobody took is neither a measurement nor a guess of zero', function (): void {
    // Not zero. An operator deciding whether to let something onto their disk
    // is entitled to *we do not know*: it is a real answer, and the one that
    // sends them to look rather than to approve. Folding it into a guess would
    // put "0 bytes" beside a request for a nineteen-season procedural.
    expect(howBig(Size::unknown()))->toBe('unknown')
        ->and(howBig(Size::unknown()))->not->toBe(howBig(Size::guessedAt(0)));
});

it('D7-R3 — a size of zero is a figure rather than a missing one', function (): void {
    // The boundary the arm order has to get right. `0` is falsy, and a fold
    // written on truthiness rather than on `null` would send a measured zero to
    // the arm meaning nobody looked.
    expect(howBig(Size::measured(0)))->toBe('measured-0')
        ->and(howBig(Size::guessedAt(0)))->toBe('guessed-0');
});

it('asks nothing of a size nobody took', function (): void {
    // The invariant behind the constructor's optional argument: the unknown
    // state answers neither question, so it supplies neither answer. Both
    // constructions of `unknown()` reach the same arm, which is what makes a
    // flag there a value nothing could ever hold this class to — and the
    // reason it is not written at all rather than written arbitrarily.
    expect(howBig(Size::unknown()))->toBe(howBig(Size::unknown()));
});
