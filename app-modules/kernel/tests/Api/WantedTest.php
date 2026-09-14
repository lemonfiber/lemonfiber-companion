<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\RequestHasNobodyBehindIt;
use Modules\Kernel\Api\Size;
use Modules\Kernel\Api\Waiting;
use Modules\Kernel\Api\Wanted;

use function sprintf;

/** A request as it arrives, with whatever a case wants to change. */
function aRequest(
    string $by = 'Sam',
    string $forWhat = 'A film nobody has seen',
    ?Size $size = null,
    Waiting $standing = Waiting::ForApproval,
): Wanted {
    return Wanted::of(41, $by, $forWhat, $size ?? Size::unknown(), $standing);
}

/**
 * The size a request carries, folded to one string so every arm is named.
 *
 * Named for this file (`G10`). What each arm *means* is {@see SizeTest}'s
 * question; what this file asks is whether a request hands the size out with
 * its label still on it, which needs the fold and nothing else from it.
 */
function theSizeOn(Wanted $wanted): string
{
    return $wanted->size()->either(
        measured: static fn(int $bytes): Code => Code::of(sprintf('measured-%d', $bytes)),
        guessed: static fn(int $bytes): Code => Code::of(sprintf('guessed-%d', $bytes)),
        unknown: static fn(): Code => Code::of('nobody-knows'),
    )->shown();
}

it('N2-R11 — carries who asked and what for, which is what a decision is made from', function (): void {
    $wanted = aRequest();

    expect($wanted->by())->toBe('Sam')
        ->and($wanted->forWhat())->toBe('A film nobody has seen')
        ->and($wanted->number())->toBe(41);
});

it('takes the words as the stack wrote them, less the space around them', function (): void {
    $wanted = aRequest(by: "  Sam \n", forWhat: '  A film  ');

    expect($wanted->by())->toBe('Sam')
        ->and($wanted->forWhat())->toBe('A film');
});

it('D7-R7 — refuses a request with nobody behind it', function (): void {
    // A decision recorded against nobody cannot reach the requester, which is
    // what a decline is required to do.
    expect(fn(): Wanted => aRequest(by: '   '))
        ->toThrow(RequestHasNobodyBehindIt::class, '41');
});

it('N2-R11 — refuses a request that names nothing asked for', function (): void {
    // A row an operator is being asked to approve, saying nothing about what
    // they would be approving, is a decision nobody can make.
    expect(fn(): Wanted => aRequest(forWhat: '   '))
        ->toThrow(RequestHasNobodyBehindIt::class, 'Sam');
});

it('N2-R11 — says which requests are waiting on the operator', function (): void {
    // Only one of the seven wants a decision. A screen offering to approve
    // something already here would be offering to do nothing.
    $wanting = [];

    foreach (Waiting::cases() as $standing) {
        if ($standing->wantsADecision()) {
            $wanting[] = $standing->value;
        }
    }

    expect($wanting)->toBe(['waiting-for-approval']);
});

it('L7 — every standing names a line, built from the case', function (): void {
    foreach (Waiting::cases() as $standing) {
        expect($standing->saidOnTheScreen())
            ->toBe(sprintf('household.%s', $standing->value), $standing->name);
    }
});

it('carries where it stands, so a screen can tell waiting from arrived', function (): void {
    expect(aRequest(standing: Waiting::Here)->standing())->toBe(Waiting::Here)
        ->and(aRequest()->standing())->toBe(Waiting::ForApproval);
});

it('D7-R3 — hands the size out with its label still on it', function (): void {
    // Read off the request rather than off a `Size` built in the test, because
    // what the requirement is about is what reaches a screen. A request that
    // carried the figure and dropped the word would satisfy every assertion
    // above and fail the one that matters.
    expect(theSizeOn(aRequest(size: Size::guessedAt(4_000_000_000))))
        ->toBe('guessed-4000000000')
        ->and(theSizeOn(aRequest()))->toBe('nobody-knows');
});
