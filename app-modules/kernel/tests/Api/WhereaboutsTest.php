<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\Whereabouts;
use Modules\Kernel\Api\WhereaboutsIsNowhere;

it('N1-R44 — keeps the screen it was given', function (): void {
    expect(Whereabouts::onTheScreen('stack.readings')->screen())->toBe('stack.readings');
});

it('N1-R44 — refuses a blank screen rather than defaulting to one', function (): void {
    // Every plausible default is the failure the requirement describes.
    // "Nowhere" restores nothing, and "the first screen" is the bounce-to-login
    // that N1-R44 exists to forbid wearing a different name. The blank string
    // is the one that would really happen — a screen naming itself from a value
    // that was not set yet — and it reads as a screen until somebody is
    // returned to it.
    expect(fn(): Whereabouts => Whereabouts::onTheScreen('  '))
        ->toThrow(WhereaboutsIsNowhere::class);
});

it('N1-R44 — does not treat surrounding space as a different screen', function (): void {
    expect(Whereabouts::onTheScreen(' stack.readings ')->is(Whereabouts::onTheScreen('stack.readings')))
        ->toBeTrue();
});

it('N1-R44 — two screens are not the same place', function (): void {
    expect(Whereabouts::onTheScreen('stack.readings')->is(Whereabouts::onTheScreen('stack.settings')))
        ->toBeFalse();
});
