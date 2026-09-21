<?php

declare(strict_types=1);

use Modules\Device\Api\SystemEntropy;
use Modules\Kernel\Api\Entropy;
use Modules\Kernel\Api\Nonce;
use Tests\Support\Fakes\SequencedEntropy;

// The Entropy contract, run against the adapter and against the fake.
//
// The promise both must keep is uniqueness and width, and neither is something
// a caller can check for itself: a nonce that repeats looks exactly like one
// that does not, and a short one looks exactly like a long one until somebody
// is searching them. So the contract is the only place either is asserted, and
// it is asserted of the fake too — a fake that answered a short value would be
// exercising a `Nonce` the application never builds.
//
// What is deliberately not here is unpredictability. A counter is perfectly
// predictable and that is the whole reason the fake exists; asserting
// randomness would fail it, and weakening the assertion until it passed would
// be asserting nothing of the adapter either.
//
// Nor whether `nonce()` answers a `Nonce`, which is the signature rather than
// a promise. What a source can be wrong about is the value it hands over, and
// both ways one goes wrong — a repeat and a short one — are asked below.

const HANDED_OUT = 50;

/** Enough draws that a shared prefix would show, few enough to stay instant. */
const A_HANDFUL = 8;

/** How much of the front of a nonce a padded counter would leave unchanged. */
const A_PREFIX = 16;

/** @return array<string, callable(): Entropy> */
function sources(): array
{
    return [
        'SystemEntropy' => static fn(): Entropy => new SystemEntropy(),
        'SequencedEntropy' => SequencedEntropy::counting(...),
    ];
}

foreach (sources() as $name => $build) {
    it(sprintf('%s never answers the same nonce twice', $name), function () use ($build): void {
        $source = $build();
        $seen = [];

        for ($i = 0; $i < HANDED_OUT; $i++) {
            $seen[] = $source->nonce()->shown();
        }

        expect(array_unique($seen))->toHaveCount(HANDED_OUT);
    });

    it(sprintf('%s answers a nonce wide enough not to be searched', $name), function () use ($build): void {
        expect(mb_strlen($build()->nonce()->shown()))->toBeGreaterThanOrEqual(Nonce::SHORTEST);
    });
}

// Beyond the contract: what each one promises that the other does not.

it('SystemEntropy varies across the whole value, not just the end of it', function (): void {
    // Not a test of the operating system's generator, which is not this
    // suite's to check. What it refuses is an adapter quietly turned into a
    // counter, which the shared contract above cannot see: a counter answers a
    // different value every time and is exactly as wide, so it passes every
    // assertion up there. What it cannot do is vary its leading characters — a
    // padded counter shares all but the last few — so that is what is asked.
    $source = new SystemEntropy();
    $leading = [];

    for ($i = 0; $i < A_HANDFUL; $i++) {
        $leading[] = mb_substr($source->nonce()->shown(), 0, A_PREFIX);
    }

    expect(array_unique($leading))->toHaveCount(A_HANDFUL);
});

it('SequencedEntropy answers in the order a test can predict', function (): void {
    $source = SequencedEntropy::counting();

    expect($source->nonce()->shown())->toEndWith('1')
        ->and($source->nonce()->shown())->toEndWith('2')
        ->and($source->answered())->toBe(2);
});
