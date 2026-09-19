<?php

declare(strict_types=1);

use Modules\Kernel\Api\Services;
use Modules\Sdk\Api\UpkeepIsUnreadable;
use Modules\Sdk\Internal\Changes;

/**
 * One change an update would make, with this case's field changed.
 *
 * @param  array<mixed> $differently
 * @return array<mixed>
 */
function oneChangeAnUpdateWouldMake(array $differently = []): array
{
    return ['service' => 'jellyfin', 'refused' => false, 'irreversible' => false, ...$differently];
}

/**
 * What a stack reports an update would change.
 *
 * @param  array<mixed> $changes
 * @return array<mixed>
 */
function whatAStackReportsItWouldChange(array $changes): array
{
    return ['changes' => $changes];
}

/**
 * The services one reading names.
 *
 * @return list<string>
 */
function theServicesNamedBy(Services $services): array
{
    $named = [];

    foreach ($services as $service) {
        $named[] = $service->named();
    }

    return $named;
}

// Both readings are asked of this class directly rather than only through
// `Standings`. Through the adapter the two run in order, so the second one's
// refusals are unreachable — the first has already thrown on the same row.
// That ordering is the adapter's and not this class's promise: each reading
// stands on its own, and either may be the only one somebody calls.

it('names every service a change is going to touch', function (): void {
    $changes = whatAStackReportsItWouldChange([
        oneChangeAnUpdateWouldMake(),
        oneChangeAnUpdateWouldMake(['service' => 'sonarr']),
    ]);

    expect(theServicesNamedBy(Changes::in($changes)))->toBe(['jellyfin', 'sonarr']);
});

it('N2-R22 — names only the services nothing puts back', function (): void {
    $changes = whatAStackReportsItWouldChange([
        oneChangeAnUpdateWouldMake(),
        oneChangeAnUpdateWouldMake(['service' => 'sonarr', 'irreversible' => true]),
    ]);

    expect(theServicesNamedBy(Changes::permanentIn($changes)))->toBe(['sonarr']);
});

it('leaves a refused change out of both readings', function (): void {
    $changes = whatAStackReportsItWouldChange([
        oneChangeAnUpdateWouldMake(['refused' => true, 'irreversible' => true]),
    ]);

    expect(theServicesNamedBy(Changes::in($changes)))->toBe([])
        ->and(theServicesNamedBy(Changes::permanentIn($changes)))->toBe([]);
});

it('refuses a change that is not a shape, whichever reading meets it', function (): void {
    // Asserted of each entry point rather than of the pair. Through the
    // adapter only the first can ever raise this, which is exactly why the
    // second's is worth holding to: the guard that never fires in production
    // is the one that quietly stops being written.
    $changes = whatAStackReportsItWouldChange(['a string where a change belongs']);

    expect(fn(): object => Changes::in($changes))
        ->toThrow(UpkeepIsUnreadable::class)
        ->and(fn(): object => Changes::permanentIn($changes))
        ->toThrow(UpkeepIsUnreadable::class);
});

it('names which change it could not read, counting from where it sat', function (): void {
    // The position is the only thing a refusal carries that shortens the
    // search, so it is asserted rather than assumed. Both readings walk the
    // list themselves, and a walk that lost count would report the first row
    // for every row — which reads as one broken change rather than as the
    // fourth one.
    $changes = whatAStackReportsItWouldChange([
        oneChangeAnUpdateWouldMake(),
        oneChangeAnUpdateWouldMake(['service' => 'sonarr']),
        'a string where a change belongs',
    ]);

    expect(fn(): object => Changes::in($changes))
        ->toThrow(UpkeepIsUnreadable::class, 'Change 3 in the update')
        ->and(fn(): object => Changes::permanentIn($changes))
        ->toThrow(UpkeepIsUnreadable::class, 'Change 3 in the update');
});

it('refuses a payload that lists no changes at all', function (): void {
    expect(fn(): object => Changes::in([]))
        ->toThrow(UpkeepIsUnreadable::class, 'changes')
        ->and(fn(): object => Changes::permanentIn([]))
        ->toThrow(UpkeepIsUnreadable::class, 'changes');
});

it('refuses a change list that is not a list', function (): void {
    expect(fn(): object => Changes::in(['changes' => 'nothing to report']))
        ->toThrow(UpkeepIsUnreadable::class, 'changes')
        ->and(fn(): object => Changes::permanentIn(['changes' => 'nothing to report']))
        ->toThrow(UpkeepIsUnreadable::class, 'changes');
});
