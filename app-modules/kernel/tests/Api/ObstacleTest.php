<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_intersect;
use function array_map;
use function array_unique;
use function count;
use function expect;
use function it;

use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ObstacleIsNotOne;
use Modules\Kernel\Api\Severity;
use Modules\Kernel\Api\Standing;
use Modules\Kernel\Api\TheVersionsSpoken;

use function sprintf;

it('is the eleven an operator must be able to tell apart', function (): void {
    // Pinned rather than counted. Adding one is a decision — the lock keeps
    // being proposed and keeps belonging elsewhere, while the permission case asked for
    // the permission case by name — and it should be made against a failing
    // test rather than noticed later on a screen with an unlabelled state.
    expect(KindOfObstacle::cases())->toBe([
        KindOfObstacle::DeviceHasNoNetwork,
        KindOfObstacle::LocalNetworkIsNotPermitted,
        KindOfObstacle::StackDidNotAnswer,
        KindOfObstacle::StackIsNotTheOnePaired,
        KindOfObstacle::CredentialWasRefused,
        KindOfObstacle::NotForThisAccount,
        KindOfObstacle::TooManyAttempts,
        KindOfObstacle::MediaServerDidNotAnswer,
        KindOfObstacle::AddressIsNotTheStacks,
        KindOfObstacle::VersionsDisagree,
        KindOfObstacle::StackIsBusy,
    ]);
});

it('G4-R6 — names each one differently in the identifier an operator searches for', function (): void {
    // Every error kind carries a stable identifier, and this test
    // is what makes *stable* mean something: the codes are written out, so a
    // rename is a failing test rather than a search that stops finding the page
    // somebody wrote about the error last year. The uniqueness check below is
    // the other half — an identifier two kinds share identifies neither.
    $codes = array_map(
        static fn(KindOfObstacle $obstacle): string => $obstacle->code()->shown(),
        KindOfObstacle::cases(),
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
        'COMPANION-NOT-FOR-THIS-ACCOUNT',
        'COMPANION-TOO-MANY-ATTEMPTS',
        'COMPANION-MEDIA-SERVER-UNCONFIRMED',
        'COMPANION-ADDRESS-NOT-THE-STACKS',
        'COMPANION-VERSIONS-DISAGREE',
        'COMPANION-STACK-BUSY',
    ]);
});

it('calls a condition that clears itself a warning, and a fault an error', function (): void {
    // Nothing is broken when a phone is somewhere without a signal; it will
    // leave. Nothing is broken either when a door has stopped listening after
    // too many wrong passwords — not the stack, not the app, not the password —
    // and that condition clears itself too. The others mean something that is
    // supposed to work does not, and `Severity::demandsAttention` is what a
    // screen reads off this.
    expect(KindOfObstacle::TooManyAttempts->severity())->toBe(Severity::Warning);
    expect(KindOfObstacle::DeviceHasNoNetwork->severity())->toBe(Severity::Warning);
    expect(KindOfObstacle::LocalNetworkIsNotPermitted->severity())->toBe(Severity::Error);
    expect(KindOfObstacle::StackDidNotAnswer->severity())->toBe(Severity::Error);
    expect(KindOfObstacle::CredentialWasRefused->severity())->toBe(Severity::Error);

    // The sharpest of the three that are not faults: the refusal is correct.
    // A member who may not ask for a thing is not looking at something broken,
    // and demanding attention for it would raise an alarm about the rules
    // working as written.
    expect(KindOfObstacle::NotForThisAccount->severity())->toBe(Severity::Warning);
    expect(KindOfObstacle::NotForThisAccount->severity()->demandsAttention())->toBeFalse();

    // The only one that may mean somebody else is answering, which is a
    // consequence outside the machine rather than something being broken.
    expect(KindOfObstacle::StackIsNotTheOnePaired->severity())->toBe(Severity::Critical);
    expect(KindOfObstacle::StackIsNotTheOnePaired->severity()->demandsAttention())->toBeTrue();

    expect(KindOfObstacle::DeviceHasNoNetwork->severity()->demandsAttention())->toBeFalse();

    // A media server restarting clears itself and says nothing against the
    // account; an address the stack does not listen on is something that is
    // supposed to work and does not.
    expect(KindOfObstacle::MediaServerDidNotAnswer->severity())->toBe(Severity::Warning);
    expect(KindOfObstacle::AddressIsNotTheStacks->severity())->toBe(Severity::Error);

    // Other work holding the stack is the stack working, and clears itself;
    // two versions that disagree stay that way until one side is updated.
    expect(KindOfObstacle::StackIsBusy->severity())->toBe(Severity::Warning);
    expect(KindOfObstacle::VersionsDisagree->severity())->toBe(Severity::Error);
});

it('offers a button only where the app can press it', function (): void {
    // Turning on Wi-Fi and waking a machine happen somewhere this application
    // cannot reach. Pairing again is a thing it does — it is named as the
    // remedy for exactly that case — and the app is obliged to offer the
    // way to grant a refused permission, which is a button by definition.
    expect(KindOfObstacle::DeviceHasNoNetwork->standing())->toBe(Standing::Guided);
    expect(KindOfObstacle::StackDidNotAnswer->standing())->toBe(Standing::Guided);
    expect(KindOfObstacle::LocalNetworkIsNotPermitted->standing())->toBe(Standing::Actionable);
    expect(KindOfObstacle::CredentialWasRefused->standing())->toBe(Standing::Actionable);

    // Entitlement is the household operator's to give, somewhere this
    // application cannot reach. A button would either do nothing or promise a
    // member something the app cannot deliver.
    expect(KindOfObstacle::NotForThisAccount->standing())->toBe(Standing::Guided);
    expect(KindOfObstacle::StackIsNotTheOnePaired->standing())->toBe(Standing::Actionable);

    // Waiting for the household's media server happens somewhere this app cannot
    // reach; pairing again is a thing it does.
    expect(KindOfObstacle::MediaServerDidNotAnswer->standing())->toBe(Standing::Guided);
    expect(KindOfObstacle::AddressIsNotTheStacks->standing())->toBe(Standing::Actionable);

    // Updating either side, and waiting for other work, happen where this
    // app cannot act.
    expect(KindOfObstacle::VersionsDisagree->standing())->toBe(Standing::Guided);
    expect(KindOfObstacle::StackIsBusy->standing())->toBe(Standing::Guided);

    expect(KindOfObstacle::CredentialWasRefused->standing()->offersAButton())->toBeTrue();
    expect(KindOfObstacle::StackDidNotAnswer->standing()->offersAButton())->toBeFalse();
});

it('N1-R10 — gives each one its own sentence and its own advice', function (): void {
    // Derived from the case rather than spelled, so a case added here has both
    // by existing and cannot be given a sentence at one call site that
    // disagrees with another's. Two of these sharing a key would be the
    // collapse that is refused, rebuilt in the catalogue after the enum had
    // refused it.
    $said = array_map(static fn(KindOfObstacle $why): string => $why->said(), KindOfObstacle::cases());
    $remedies = array_map(static fn(KindOfObstacle $why): string => $why->remedy(), KindOfObstacle::cases());

    expect(count(array_unique($said)))->toBe(count($said))
        ->and(count(array_unique($remedies)))->toBe(count($remedies));

    // What happened and what to do about it are not the same sentence, which
    // is the other half of what is asked for.
    expect(array_intersect($said, $remedies))->toBe([]);

    expect(KindOfObstacle::DeviceHasNoNetwork->said())->toBe('connection.no_network')
        ->and(KindOfObstacle::DeviceHasNoNetwork->remedy())->toBe('connection.no_network_action');
});

it('meets a kind with nothing beyond itself, and answers for it as the kind does', function (): void {
    $met = Obstacle::of(KindOfObstacle::StackIsBusy);

    expect($met->kind())->toBe(KindOfObstacle::StackIsBusy)
        ->and($met->is(KindOfObstacle::StackIsBusy))->toBeTrue()
        ->and($met->is(KindOfObstacle::StackDidNotAnswer))->toBeFalse()
        ->and([$met->said(), $met->remedy()])->toBe(['connection.busy', 'connection.busy_action'])
        ->and($met->code()->shown())->toBe('COMPANION-STACK-BUSY')
        ->and($met->severity())->toBe(Severity::Warning)
        ->and($met->standing())->toBe(Standing::Guided)
        ->and($met->meansWeAreSignedOut())->toBeFalse()
        ->and(Obstacle::of(KindOfObstacle::CredentialWasRefused)->meansWeAreSignedOut())->toBeTrue();
});

it('names no versions for a kind that carries none', function (): void {
    expect(static fn(): TheVersionsSpoken => Obstacle::of(KindOfObstacle::StackIsBusy)->versionsSpoken())
        ->toThrow(ObstacleIsNotOne::class, '`StackIsBusy` names no versions');
});

it('refuses a version mismatch met without its versions', function (): void {
    expect(static fn(): Obstacle => Obstacle::of(KindOfObstacle::VersionsDisagree))
        ->toThrow(ObstacleIsNotOne::class, '`VersionsDisagree`');
});

it('names both versions, and has whichever side is older updated', function (): void {
    $newer = Obstacle::versionsDisagree(TheVersionsSpoken::between(2, 1));
    $older = Obstacle::versionsDisagree(TheVersionsSpoken::between(1, 2));

    expect($newer->kind())->toBe(KindOfObstacle::VersionsDisagree)
        ->and($newer->said())->toBe('connection.version_mismatch')
        ->and([$newer->versionsSpoken()->answered(), $newer->versionsSpoken()->spoken()])->toBe([2, 1])
        ->and($newer->remedy())->toBe('connection.version_mismatch_action')
        ->and($older->said())->toBe('connection.version_mismatch')
        ->and([$older->versionsSpoken()->answered(), $older->versionsSpoken()->spoken()])->toBe([1, 2])
        ->and($older->remedy())->toBe('connection.version_mismatch_older_action');
});

it('refuses two versions that do not disagree', function (int $answered, int $spoken): void {
    expect(static fn(): TheVersionsSpoken => TheVersionsSpoken::between($answered, $spoken))
        ->toThrow(ObstacleIsNotOne::class, sprintf('Versions %d and %d', $answered, $spoken));
})->with([
    'the same' => [1, 1],
    'an answer below nought' => [-1, 1],
    'a reading below nought' => [1, -1],
]);

it('says which side is newer', function (): void {
    expect(TheVersionsSpoken::between(3, 2)->isTheStackNewer())->toBeTrue()
        ->and(TheVersionsSpoken::between(2, 3)->isTheStackNewer())->toBeFalse()
        ->and([TheVersionsSpoken::between(3, 2)->answered(), TheVersionsSpoken::between(3, 2)->spoken()])->toBe([3, 2]);
});
