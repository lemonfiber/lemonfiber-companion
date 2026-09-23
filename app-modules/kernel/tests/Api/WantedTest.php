<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\HowARequestStands;
use Modules\Kernel\Api\RequestHasNobodyBehindIt;
use Modules\Kernel\Api\Size;
use Modules\Kernel\Api\TurnedDown;
use Modules\Kernel\Api\Waiting;
use Modules\Kernel\Api\Wanted;

use function sprintf;

/** One standing carried out of `either()`, since it must hand back an object. */
final readonly class WhatTheStandingWas
{
    public function __construct(public string $said) {}
}

/** A request as it arrives, with whatever a case wants to change. */
function aRequest(
    string $by = 'Sam',
    string $forWhat = 'A film nobody has seen',
    ?Size $size = null,
    Waiting $standing = Waiting::ForApproval,
): Wanted {
    return Wanted::of(41, $by, $forWhat, $size ?? Size::unknown(), HowARequestStands::said($standing));
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

/** The standing a request came back carrying, named so a case can read it. */
function whereItStands(Wanted $wanted): string
{
    return $wanted->standing()->either(
        said: static fn(Waiting $said): WhatTheStandingWas => new WhatTheStandingWas($said->value),
        unnamed: static fn(): WhatTheStandingWas => new WhatTheStandingWas('nobody named it'),
    )->said;
}

it('carries where it stands, so a screen can tell waiting from arrived', function (): void {
    expect(whereItStands(aRequest(standing: Waiting::Here)))->toBe('here')
        ->and(whereItStands(aRequest()))->toBe('waiting-for-approval');
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

/** One word carried out of a refusal arm. */
final readonly class WhatTheRequestSaidAboutBeingRefused
{
    public function __construct(public string $said) {}
}

/** Why a request was refused, or the word for not having been. */
function whyItWasTurnedDown(Wanted $wanted): string
{
    return $wanted->refusal(
        was: static fn(TurnedDown $why): WhatTheRequestSaidAboutBeingRefused
            => new WhatTheRequestSaidAboutBeingRefused($why->reason()),
        wasNot: static fn(): WhatTheRequestSaidAboutBeingRefused
            => new WhatTheRequestSaidAboutBeingRefused('not refused'),
    )->said;
}

it('N3-R7 — a refused request carries the reason that was given', function (): void {
    $wanted = Wanted::turnedDown(
        41,
        'Sam',
        'A film nobody has seen',
        Size::unknown(),
        TurnedDown::because('The disk is nearly full'),
    );

    expect(whyItWasTurnedDown($wanted))->toBe('The disk is nearly full');
});

it('D7-R7 — a refused request is `declined` by construction', function (): void {
    // The standing is not a parameter, which makes two mistakes unspellable at
    // once: a decline carrying no reason, and a reason attached to a request
    // that was never declined — a screen telling somebody why a thing they are
    // still waiting for was refused.
    $wanted = Wanted::turnedDown(41, 'Sam', 'A film', Size::unknown(), TurnedDown::because('No room'));

    expect(whereItStands($wanted))->toBe('declined')
        ->and($wanted->standing()->wasDeclined())->toBeTrue();
});

it('a request that was not refused says so rather than answering with nothing', function (): void {
    $wanted = Wanted::of(41, 'Sam', 'A film', Size::unknown(), HowARequestStands::said(Waiting::ForApproval));

    expect(whyItWasTurnedDown($wanted))->toBe('not refused');
});

it('a refused request is still refused for the reasons every request is', function (): void {
    // `turnedDown()` goes through `of()`, so the blank-title and blank-requester
    // refusals reach it too — which is what keeps a decline from being the one
    // row that can arrive unreadable.
    expect(fn(): Wanted => Wanted::turnedDown(41, '  ', 'A film', Size::unknown(), TurnedDown::because('No')))
        ->toThrow(RequestHasNobodyBehindIt::class);
});
