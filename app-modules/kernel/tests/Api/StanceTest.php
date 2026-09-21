<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_map;
use function expect;
use function it;

use Modules\Kernel\Api\Stance;

it('says which stances hold what was asked for', function (): void {
    // All four, because the two questions this type answers are not the same
    // question and the cases do not group the same way. A change that was
    // already what was asked and one that was written both hold it; one that
    // is waiting and one that was refused do not.
    expect(Stance::Unchanged->holdsWhatWasAsked())->toBeTrue()
        ->and(Stance::Applied->holdsWhatWasAsked())->toBeTrue()
        ->and(Stance::Pending->holdsWhatWasAsked())->toBeFalse()
        ->and(Stance::Blocked->holdsWhatWasAsked())->toBeFalse();
});

it('says which stances wrote something', function (): void {
    // The other question, and `Unchanged` is why it is a second method rather
    // than the same one. A setting that already held the value holds what was
    // asked and nothing was written — so a screen reading one answer for both
    // would offer to restart services over a change that did not happen.
    expect(Stance::Applied->wroteSomething())->toBeTrue()
        ->and(Stance::Unchanged->wroteSomething())->toBeFalse()
        ->and(Stance::Pending->wroteSomething())->toBeFalse()
        ->and(Stance::Blocked->wroteSomething())->toBeFalse();
});

it('is the four words the wire has and no others', function (): void {
    // Held to the wire rather than to a list written here. A fifth stance
    // arrives as a case neither `match` has, and both raise rather than
    // guessing — which is what a screen deciding whether to offer a restart
    // needs them to do.
    expect(array_map(static fn(Stance $stance): string => $stance->value, Stance::cases()))
        ->toBe(['unchanged', 'pending', 'blocked', 'applied']);
});
