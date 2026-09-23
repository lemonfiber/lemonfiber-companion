<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_unique;
use function expect;
use function it;

use Modules\Kernel\Api\HowFarItGoesBack;

use function sprintf;

it('N11-R2 — the three the contract names, spelled as it spells them', function (): void {
    // The contract names them in the sentence describing the field rather than
    // in a closed type, so nothing generated pins them. This does: a word the
    // stack sends that has no case here is refused at the reading, and that
    // refusal is only as good as these three being the right three.
    $said = [];

    foreach (HowFarItGoesBack::cases() as $reversal) {
        $said[] = $reversal->value;
    }

    expect($said)->toBe(['whole', 'partial', 'none']);
});

it('N11-R2 — cannot be put back is a word of its own, not the absence of one', function (): void {
    // The requirement is that an irreversible change says so rather than
    // omitting the field. Three distinct words is what that looks like on a
    // screen; two, with `none` drawn as a blank, is the omission.
    $keys = [];

    foreach (HowFarItGoesBack::cases() as $reversal) {
        $keys[] = $reversal->saidOnTheScreen();
    }

    expect(array_unique($keys))->toHaveCount(3);
});

it('L7 — every reversal names a line, built from the case', function (): void {
    foreach (HowFarItGoesBack::cases() as $reversal) {
        expect($reversal->saidOnTheScreen())
            ->toBe(sprintf('stacks.reversal.%s', $reversal->value), $reversal->name);
    }
});
