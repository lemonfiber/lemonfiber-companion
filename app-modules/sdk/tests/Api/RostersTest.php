<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function expect;
use function it;

use Lemonfiber\Sdk\Envelope\Envelope;
use Modules\Kernel\Api\HowAServiceRuns;
use Modules\Kernel\Api\HowMuchItMatters;
use Modules\Kernel\Api\HowTheStackIsRunning;
use Modules\Sdk\Api\RosterIsUnreadable;
use Modules\Sdk\Api\Rosters;

/**
 * A `status` envelope holding whatever the case under test is about.
 *
 * Built by hand rather than fetched, for {@see anAcknowledgementSaying()}'s
 * reason: what is being tested is what happens when the wire says something the
 * contract does not allow, which a client honouring the contract could not
 * produce.
 *
 * @param array<mixed> $data
 *
 * @return Envelope<mixed>
 */
function aRosterSaying(array $data): Envelope
{
    return new Envelope(1, 'status', $data);
}

/**
 * One service, complete, with whatever this case is about changed.
 *
 * @param  array<mixed> $differently
 * @return array<mixed>
 */
function aServiceSaying(array $differently = []): array
{
    return [...[
        'id' => 'sonarr',
        'name' => 'Sonarr',
        'profile' => 'downloads',
        'state' => 'running',
        'criticality' => 'important',
        'depends_on' => [],
    ], ...$differently];
}

/**
 * What the one service in a listing exited with, whichever arm it takes.
 *
 * The empty string is *it said nothing*, which is the fact and not a stand-in
 * for one: a service that is running has no exit code at all, and `0` is the
 * code for one that ended well.
 *
 * The carrier is anonymous because this file's namespace is one the
 * architecture rules resolve classes from, and a second named class here maps
 * to no file of its own.
 *
 * @param array<mixed> $differently
 */
function whatTheServiceExitedWith(array $differently): string
{
    $said = '';

    foreach (Rosters::in(aRosterSaying($differently)) as $daemon) {
        $said = $daemon->exit(
            said: static fn(int $code): object => new readonly class ((string) $code) {
                public function __construct(public string $said) {}
            },
            unstated: static fn(): object => new readonly class ('') {
                public function __construct(public string $said) {}
            },
        )->said;
    }

    return $said;
}

/**
 * A whole listing of one service, with whatever this case is about changed.
 *
 * @param  array<mixed> $differently
 * @return array<mixed>
 */
function aRosterOf(array $differently = []): array
{
    return [
        'condition' => 'active',
        'forms' => ['downloads'],
        'services' => [aServiceSaying($differently)],
    ];
}

it('reads what the stack says it all amounts to', function (): void {
    $daemons = Rosters::in(aRosterSaying(aRosterOf()));

    expect($daemons->running())->toBe(HowTheStackIsRunning::Active);
    expect($daemons->count())->toBe(1);
    expect($daemons->forms()->count())->toBe(1);
});

it('reads a form nothing in it is running', function (): void {
    // The forms come from the stack's own list rather than from the rows, so a
    // form with everything stopped survives the reading. It is the one form an
    // operator opens the app to start.
    $daemons = Rosters::in(aRosterSaying([
        'condition' => 'inactive',
        'forms' => ['media', 'downloads'],
        'services' => [],
    ]));

    expect($daemons->forms()->count())->toBe(2);
    expect($daemons->count())->toBe(0);
});

it('reads a service that ended by the constructor that says so', function (): void {
    expect(whatTheServiceExitedWith(aRosterOf(['state' => 'failed', 'exit' => 137])))->toBe('137');
});

it('reads a service that is still going as one that said nothing', function (): void {
    // The other arm, and the one that must not be a number: a service running
    // now has no exit code, and a zero here would read as one that ended well.
    expect(whatTheServiceExitedWith(aRosterOf()))->toBe('');
});

it('reads `exit: null` as a service that did not end', function (): void {
    // Absent and null are the same fact said two ways, and the contract allows
    // both. Neither is a service that exited with nothing.
    $daemons = Rosters::in(aRosterSaying(aRosterOf(['exit' => null])));

    foreach ($daemons as $daemon) {
        expect($daemon->runs())->toBe(HowAServiceRuns::Running);
    }

    expect($daemons->count())->toBe(1);
});

it('refuses an `exit` that is neither absent, null, nor a number', function (): void {
    // Refused rather than read as still running, which is the reading that
    // turns a service that died into one nobody looks at.
    expect(fn(): object => Rosters::in(aRosterSaying(aRosterOf(['exit' => 'oom']))))
        ->toThrow(RosterIsUnreadable::class, 'exit');
});

it('refuses a listing with no condition at all', function (): void {
    // Read before any row is, and refused rather than worked out from the rows:
    // a second opinion assembled on a phone would disagree with the machine the
    // first time lemonfiber changed how it weighs a degraded service.
    expect(fn(): object => Rosters::in(aRosterSaying(['forms' => [], 'services' => []])))
        ->toThrow(RosterIsUnreadable::class, 'condition');
});

it('refuses a condition that is not a word', function (): void {
    expect(fn(): object => Rosters::in(aRosterSaying(['condition' => 41, 'forms' => [], 'services' => []])))
        ->toThrow(RosterIsUnreadable::class, 'condition');
});

it('refuses a condition this build has not heard of', function (): void {
    expect(fn(): object => Rosters::in(aRosterSaying(['condition' => 'brilliant', 'forms' => [], 'services' => []])))
        ->toThrow(RosterIsUnreadable::class, 'brilliant');
});

it('names what it does read, in the words the enum has', function (): void {
    // The accepted list comes from `cases()` rather than from a sentence, so a
    // word added to the contract cannot leave the message describing the
    // vocabulary of the build before it.
    expect(fn(): object => Rosters::in(aRosterSaying(['condition' => 'brilliant', 'forms' => [], 'services' => []])))
        ->toThrow(RosterIsUnreadable::class, HowTheStackIsRunning::Degraded->value);
});

it('refuses a state this build has not heard of', function (): void {
    expect(fn(): object => Rosters::in(aRosterSaying(aRosterOf(['state' => 'thinking']))))
        ->toThrow(RosterIsUnreadable::class, 'thinking');
});

it('refuses a criticality this build has not heard of', function (): void {
    // How much a service matters decides how loudly a stop is asked about, so
    // an unrecognised word must not become the quietest one.
    expect(fn(): object => Rosters::in(aRosterSaying(aRosterOf(['criticality' => 'whatever']))))
        ->toThrow(RosterIsUnreadable::class, HowMuchItMatters::Critical->value);
});

it('refuses a service with no id', function (): void {
    $row = aServiceSaying();
    unset($row['id']);

    expect(fn(): object => Rosters::in(aRosterSaying([
        'condition' => 'active',
        'forms' => [],
        'services' => [$row],
    ])))->toThrow(RosterIsUnreadable::class, 'id');
});

it('names which service it could not read', function (): void {
    // The position is only knowable here, and a refusal saying *service 1* can
    // be acted on where one saying *a service* leaves somebody reading forty.
    expect(fn(): object => Rosters::in(aRosterSaying([
        'condition' => 'active',
        'forms' => [],
        'services' => [aServiceSaying(), aServiceSaying(['name' => '  '])],
    ])))->toThrow(RosterIsUnreadable::class, 'Service 1');
});

it('refuses a row that is not a service at all', function (): void {
    expect(fn(): object => Rosters::in(aRosterSaying([
        'condition' => 'active',
        'forms' => [],
        'services' => ['a sentence where a service belongs'],
    ])))->toThrow(RosterIsUnreadable::class, 'Service 0');
});

it('refuses a form that is not a name', function (): void {
    expect(fn(): object => Rosters::in(aRosterSaying([
        'condition' => 'active',
        'forms' => ['media', ''],
        'services' => [],
    ])))->toThrow(RosterIsUnreadable::class, 'Form 1');
});

it('refuses a dependency that is not a name', function (): void {
    // What leans on a service is the sentence `N2-R8` puts in front of stopping
    // it, so a name that cannot be read would understate what a stop disturbs.
    expect(fn(): object => Rosters::in(aRosterSaying(aRosterOf(['depends_on' => [41]]))))
        ->toThrow(RosterIsUnreadable::class, 'depends_on');
});

it('reads what leans on a service', function (): void {
    $daemons = Rosters::in(aRosterSaying(aRosterOf(['depends_on' => ['jellyfin', 'prowlarr']])));

    foreach ($daemons as $daemon) {
        expect($daemon->whatLeansOnIt()->count())->toBe(2);
    }

    expect($daemons->count())->toBe(1);
});

it('refuses a listing with no services field at all', function (): void {
    expect(fn(): object => Rosters::in(aRosterSaying(['condition' => 'active', 'forms' => []])))
        ->toThrow(RosterIsUnreadable::class, 'services');
});

it('refuses a payload that is not a shape at all', function (): void {
    expect(fn(): object => Rosters::in(new Envelope(1, 'status', 'a sentence where a payload belongs')))
        ->toThrow(RosterIsUnreadable::class, 'data');
});
