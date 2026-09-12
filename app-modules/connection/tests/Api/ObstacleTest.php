<?php

declare(strict_types=1);

namespace Modules\Connection\Tests\Api;

use function array_map;
use function array_unique;
use function count;
use function expect;
use function it;

use Modules\Connection\Api\Obstacle;
use Modules\Kernel\Api\Severity;
use Modules\Kernel\Api\Standing;

it('is the three N1-R10 refuses to collapse', function (): void {
    // Pinned rather than counted. Adding a fourth is a decision — the lock is
    // the one that keeps being proposed and keeps belonging elsewhere — and it
    // should be made against a failing test rather than noticed later on a
    // screen that now has an unlabelled state.
    expect(Obstacle::cases())->toBe([
        Obstacle::DeviceHasNoNetwork,
        Obstacle::StackDidNotAnswer,
        Obstacle::CredentialWasRefused,
    ]);
});

it('names each one differently in the identifier an operator searches for', function (): void {
    $codes = array_map(
        static fn(Obstacle $obstacle): string => $obstacle->code()->shown(),
        Obstacle::cases(),
    );

    // The point of the whole enum, asserted on the one field that outlives a
    // screen: two of these sharing a code would put the same answer in front of
    // somebody whose phone is in flight mode and somebody whose stack is off.
    expect(count(array_unique($codes)))->toBe(count($codes));

    expect($codes)->toBe([
        'COMPANION-NO-NETWORK',
        'COMPANION-NO-ANSWER',
        'COMPANION-CREDENTIAL-REFUSED',
    ]);
});

it('calls no network a warning and the other two errors', function (): void {
    // Nothing is broken when a phone is somewhere without a signal; it will
    // leave. The other two mean something that is supposed to work does not,
    // and `Severity::demandsAttention` is what a screen reads off this.
    expect(Obstacle::DeviceHasNoNetwork->severity())->toBe(Severity::Warning);
    expect(Obstacle::StackDidNotAnswer->severity())->toBe(Severity::Error);
    expect(Obstacle::CredentialWasRefused->severity())->toBe(Severity::Error);

    expect(Obstacle::DeviceHasNoNetwork->severity()->demandsAttention())->toBeFalse();
});

it('offers a button only where the app can press it', function (): void {
    // Turning on Wi-Fi and waking a machine happen somewhere this application
    // cannot reach. Pairing again is a thing it does — `N1-R20` names it as the
    // remedy for exactly this case.
    expect(Obstacle::DeviceHasNoNetwork->standing())->toBe(Standing::Guided);
    expect(Obstacle::StackDidNotAnswer->standing())->toBe(Standing::Guided);
    expect(Obstacle::CredentialWasRefused->standing())->toBe(Standing::Actionable);

    expect(Obstacle::CredentialWasRefused->standing()->offersAButton())->toBeTrue();
    expect(Obstacle::StackDidNotAnswer->standing()->offersAButton())->toBeFalse();
});
