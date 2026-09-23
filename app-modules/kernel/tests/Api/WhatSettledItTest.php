<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\Capability;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Services;
use Modules\Kernel\Api\WhatSettledIt;
use Modules\Kernel\Api\WhoSettledIt;
use Modules\Kernel\Api\WhyItWasChosen;

/**
 * What an arm handed out, carried out of `whichever()` in one piece.
 *
 * The fold answers an object, which is what stops a reader returning a bare
 * `null` for the arms it did not think about. {@see WhatTheRefusalSaid} is the
 * same device one type over.
 */
final readonly class WhatTheSettlementSaid
{
    public function __construct(
        public string $arm,
        public Services $services,
        public ?WhoSettledIt $whose = null,
        public ?WhyItWasChosen $why = null,
    ) {}
}

/** Everything the union handed a reader, whichever arm it took. */
function handedOut(WhatSettledIt $settled): WhatTheSettlementSaid
{
    return $settled->whichever(
        outright: static fn(): WhatTheSettlementSaid => new WhatTheSettlementSaid('outright', Services::none()),
        each: static fn(): WhatTheSettlementSaid => new WhatTheSettlementSaid('each', Services::none()),
        contested: static fn(Services $claimants): WhatTheSettlementSaid => new WhatTheSettlementSaid('contested', $claimants),
        chosen: static fn(Services $over, WhoSettledIt $whose, WhyItWasChosen $why): WhatTheSettlementSaid => new WhatTheSettlementSaid('chosen', $over, $whose, $why),
        unfilled: static fn(): WhatTheSettlementSaid => new WhatTheSettlementSaid('unfilled', Services::none()),
    );
}

/** What a reason said, or the words for nobody having given one. */
function reasonIn(?WhyItWasChosen $why): string
{
    if (! $why instanceof WhyItWasChosen) {
        return 'no settlement';
    }

    return $why->saying(
        stated: static fn(string $said): Capability => Capability::called($said),
        unstated: static fn(): Capability => Capability::called('nobody said'),
    )->named();
}

/** @return list<string> */
function theClaimantsNamed(Services $services): array
{
    $named = [];

    foreach (iterator_to_array($services, preserve_keys: false) as $service) {
        $named[] = $service->named();
    }

    return $named;
}

it('takes a reader to the arm the core sent', function (): void {
    expect(handedOut(WhatSettledIt::outright())->arm)->toBe('outright')
        ->and(handedOut(WhatSettledIt::each())->arm)->toBe('each')
        ->and(handedOut(WhatSettledIt::contested(Services::none()))->arm)->toBe('contested')
        ->and(handedOut(WhatSettledIt::chosen(Services::none(), WhoSettledIt::Stack, WhyItWasChosen::unstated()))->arm)->toBe('chosen')
        ->and(handedOut(WhatSettledIt::unfilled())->arm)->toBe('unfilled');
});

it('hands a contest its claimants, in the order they arrived', function (): void {
    // Unordered on purpose. There is no grading here to sort by, so any order
    // imposed would be an opinion about which service ought to win — which is
    // the prohibition on settling one arriving through the back door of a sort.
    $said = handedOut(WhatSettledIt::contested(Services::these(
        ServiceId::called('sabnzbd'),
        ServiceId::called('qbittorrent'),
    )));

    expect(theClaimantsNamed($said->services))->toBe(['sabnzbd', 'qbittorrent']);
});

it('says who settled a chosen capability rather than leaving it to be assumed', function (): void {
    // The arm cannot be entered without being handed this, so there
    // is no reading of a settled capability that does not know whose it was.
    expect(handedOut(WhatSettledIt::chosen(Services::none(), WhoSettledIt::Operator, WhyItWasChosen::unstated()))->whose)
        ->toBe(WhoSettledIt::Operator);
});

it('does not report the stack as the operator', function (): void {
    // The failure the distinction exists to prevent, written as a test rather
    // than trusted to the type: a stack's own default shown as a recorded decision is how an
    // operator stops looking for a choice they never made.
    expect(handedOut(WhatSettledIt::chosen(Services::none(), WhoSettledIt::Stack, WhyItWasChosen::unstated()))->whose)
        ->toBe(WhoSettledIt::Stack);
});

it('carries what a choice was settled over', function (): void {
    $said = handedOut(WhatSettledIt::chosen(
        Services::these(ServiceId::called('transmission')),
        WhoSettledIt::Operator,
        WhyItWasChosen::unstated(),
    ));

    expect(theClaimantsNamed($said->services))->toBe(['transmission']);
});

it('carries the reason where there is one', function (): void {
    $said = handedOut(WhatSettledIt::chosen(
        Services::none(),
        WhoSettledIt::Operator,
        WhyItWasChosen::stated('it was already wired'),
    ));

    expect(reasonIn($said->why))->toBe('it was already wired');
});

it('says nobody gave a reason rather than handing out an empty one', function (): void {
    // The two are different claims and reach a screen as different sentences.
    // A nullable string would make them the same value, told apart by a check
    // whoever reads it can forget.
    $said = handedOut(WhatSettledIt::chosen(Services::none(), WhoSettledIt::Stack, WhyItWasChosen::unstated()));

    expect(reasonIn($said->why))->toBe('nobody said');
});

it('gives the arms that name nothing an empty set rather than a guess', function (): void {
    // The four arms that carry no services hand out `Services::none()`, which
    // `Services` documents as a decision somebody wrote rather than a gap.
    expect(theClaimantsNamed(handedOut(WhatSettledIt::outright())->services))->toBe([])
        ->and(theClaimantsNamed(handedOut(WhatSettledIt::each())->services))->toBe([])
        ->and(theClaimantsNamed(handedOut(WhatSettledIt::unfilled())->services))->toBe([]);
});
