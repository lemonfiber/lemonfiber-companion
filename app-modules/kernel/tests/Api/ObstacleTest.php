<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_intersect;
use function array_map;
use function array_unique;
use function count;
use function expect;
use function it;

use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Severity;
use Modules\Kernel\Api\Standing;

it('is the six an operator must be able to tell apart', function (): void {
    // Pinned rather than counted. Adding one is a decision — the lock keeps
    // being proposed and keeps belonging elsewhere, while `N4-R17` asked for
    // the permission case by name — and it should be made against a failing
    // test rather than noticed later on a screen with an unlabelled state.
    expect(Obstacle::cases())->toBe([
        Obstacle::DeviceHasNoNetwork,
        Obstacle::LocalNetworkIsNotPermitted,
        Obstacle::StackDidNotAnswer,
        Obstacle::StackIsNotTheOnePaired,
        Obstacle::CredentialWasRefused,
        Obstacle::TooManyAttempts,
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
        'COMPANION-LOCAL-NETWORK-REFUSED',
        'COMPANION-NO-ANSWER',
        'COMPANION-CERTIFICATE-CHANGED',
        'COMPANION-CREDENTIAL-REFUSED',
        'COMPANION-TOO-MANY-ATTEMPTS',
    ]);
});

it('calls a condition that clears itself a warning, and a fault an error', function (): void {
    // Nothing is broken when a phone is somewhere without a signal; it will
    // leave. Nothing is broken either when a door has stopped listening after
    // too many wrong passwords — not the stack, not the app, not the password —
    // and that condition clears itself too. The others mean something that is
    // supposed to work does not, and `Severity::demandsAttention` is what a
    // screen reads off this.
    expect(Obstacle::TooManyAttempts->severity())->toBe(Severity::Warning);
    expect(Obstacle::DeviceHasNoNetwork->severity())->toBe(Severity::Warning);
    expect(Obstacle::LocalNetworkIsNotPermitted->severity())->toBe(Severity::Error);
    expect(Obstacle::StackDidNotAnswer->severity())->toBe(Severity::Error);
    expect(Obstacle::CredentialWasRefused->severity())->toBe(Severity::Error);

    // The only one that may mean somebody else is answering, which is a
    // consequence outside the machine rather than something being broken.
    expect(Obstacle::StackIsNotTheOnePaired->severity())->toBe(Severity::Critical);
    expect(Obstacle::StackIsNotTheOnePaired->severity()->demandsAttention())->toBeTrue();

    expect(Obstacle::DeviceHasNoNetwork->severity()->demandsAttention())->toBeFalse();
});

it('offers a button only where the app can press it', function (): void {
    // Turning on Wi-Fi and waking a machine happen somewhere this application
    // cannot reach. Pairing again is a thing it does — `N1-R20` names it as the
    // remedy for exactly that case — and `N4-R17` obliges the app to offer the
    // way to grant a refused permission, which is a button by definition.
    expect(Obstacle::DeviceHasNoNetwork->standing())->toBe(Standing::Guided);
    expect(Obstacle::StackDidNotAnswer->standing())->toBe(Standing::Guided);
    expect(Obstacle::LocalNetworkIsNotPermitted->standing())->toBe(Standing::Actionable);
    expect(Obstacle::CredentialWasRefused->standing())->toBe(Standing::Actionable);
    expect(Obstacle::StackIsNotTheOnePaired->standing())->toBe(Standing::Actionable);

    expect(Obstacle::CredentialWasRefused->standing()->offersAButton())->toBeTrue();
    expect(Obstacle::StackDidNotAnswer->standing()->offersAButton())->toBeFalse();
});

it('N1-R10 — gives each one its own sentence and its own advice', function (): void {
    // Derived from the case rather than spelled, so a case added here has both
    // by existing and cannot be given a sentence at one call site that
    // disagrees with another's. Two of these sharing a key would be the
    // collapse `N1-R10` refuses, rebuilt in the catalogue after the enum had
    // refused it.
    $said = array_map(static fn(Obstacle $why): string => $why->said(), Obstacle::cases());
    $remedies = array_map(static fn(Obstacle $why): string => $why->remedy(), Obstacle::cases());

    expect(count(array_unique($said)))->toBe(count($said))
        ->and(count(array_unique($remedies)))->toBe(count($remedies));

    // What happened and what to do about it are not the same sentence, which
    // is the other half of what `N1-R10` asks for.
    expect(array_intersect($said, $remedies))->toBe([]);

    expect(Obstacle::DeviceHasNoNetwork->said())->toBe('connection.no_network')
        ->and(Obstacle::DeviceHasNoNetwork->remedy())->toBe('connection.no_network_action');
});
