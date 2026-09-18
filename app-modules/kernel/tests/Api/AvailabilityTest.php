<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function count;
use function expect;
use function it;

use Modules\Kernel\Api\Availability;

it('N1-R30 — only an available capability offers an action', function (): void {
    expect(Availability::Available->offersAnAction())->toBeTrue();
    expect(Availability::Unconfigured->offersAnAction())->toBeFalse();
    expect(Availability::NotPermitted->offersAnAction())->toBeFalse();
});

it('ARCH-R79 — there is no case for a capability the stack does not have', function (): void {
    // Absence is the set not holding it. A fourth case would put "this stack
    // cannot do it" next to "you may not do it" in one list, and a screen would
    // treat them the same because they are the same shape — which the rule
    // refuses by requiring each to be reported as itself.
    expect(Availability::cases())->toBe([
        Availability::Available,
        Availability::Unconfigured,
        Availability::NotPermitted,
    ]);
});

it('is a decision each case answers for itself', function (): void {
    // A case added without a thought about the distinction inherits whichever answer the
    // implementation happens to give it. This cannot say which answer is right;
    // it does say both answers are in use, so a vocabulary that had drifted to
    // one of them fails here.
    $offering = [];

    foreach (Availability::cases() as $case) {
        $offering[$case->offersAnAction() ? 'yes' : 'no'][] = $case;
    }

    expect(count($offering))->toBe(2);
});
