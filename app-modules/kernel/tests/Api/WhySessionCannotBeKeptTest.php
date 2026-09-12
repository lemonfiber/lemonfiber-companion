<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function count;
use function expect;
use function it;

use Modules\Kernel\Api\WhySessionCannotBeKept;

it('N4-R6 — only one of the two refusals is worth trying again', function (): void {
    // The distinction an operator acts on. A store that would not open might
    // open next time; a device with no store will not grow one, and telling
    // somebody to try again there is telling them to do nothing twice.
    expect(WhySessionCannotBeKept::StoreWouldNotOpen->mayBeWorthRetrying())->toBeTrue();
    expect(WhySessionCannotBeKept::DeviceHasNoSecureStorage->mayBeWorthRetrying())->toBeFalse();
});

it('N4-R6 — there are two refusals, because the platform reports two', function (): void {
    // A third case was written and removed: "no screen lock is set" is a real
    // situation with a clear remedy, and `SecureStorageStatus` gives only
    // `Unavailable` or `Failed`. A case nothing can produce is a branch no
    // adapter reaches and no test kills — and the first screen to render it
    // would show a remedy for a state the app cannot detect.
    expect(WhySessionCannotBeKept::cases())->toBe([
        WhySessionCannotBeKept::DeviceHasNoSecureStorage,
        WhySessionCannotBeKept::StoreWouldNotOpen,
    ]);
});

it('is a decision each case answers for itself', function (): void {
    $retrying = [];

    foreach (WhySessionCannotBeKept::cases() as $case) {
        $retrying[$case->mayBeWorthRetrying() ? 'yes' : 'no'][] = $case;
    }

    expect(count($retrying))->toBe(2);
});
