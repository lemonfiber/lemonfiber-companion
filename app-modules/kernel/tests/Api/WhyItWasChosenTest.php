<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\ASettlementSaysNothing;
use Modules\Kernel\Api\Capability;
use Modules\Kernel\Api\WhyItWasChosen;

/** What the fold said, carried out as a word. */
function whatItGave(WhyItWasChosen $why): string
{
    return $why->saying(
        stated: static fn(string $said): Capability => Capability::called($said),
        unstated: static fn(): Capability => Capability::called('nobody said'),
    )->named();
}

it('carries the words somebody gave', function (): void {
    expect(whatItGave(WhyItWasChosen::stated('sabnzbd was already wired')))->toBe('sabnzbd was already wired');
});

it('trims a reason that arrived padded', function (): void {
    expect(whatItGave(WhyItWasChosen::stated("  sabnzbd was already wired \n")))->toBe('sabnzbd was already wired');
});

it('says nobody gave one, which is an answer rather than a gap', function (): void {
    // The core may answer a settlement with no reason at all, so this is an
    // ordinary case and a screen has a sentence for it. What it must not be is
    // the same value as a reason nobody has read yet.
    expect(whatItGave(WhyItWasChosen::unstated()))->toBe('nobody said');
});

it('refuses a reason that claims to be one and says nothing', function (): void {
    // A blank is not turned into `unstated()`. Those are different claims: an
    // absent field is the core saying nobody explained, and a blank one is the
    // core sending a broken value. Quietly converting the second would hide a
    // fault in the thing this app exists to render faithfully.
    expect(static fn(): WhyItWasChosen => WhyItWasChosen::stated("  \n "))
        ->toThrow(ASettlementSaysNothing::class);
});
