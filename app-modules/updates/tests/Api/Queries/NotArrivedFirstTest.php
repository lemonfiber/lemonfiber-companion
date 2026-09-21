<?php

declare(strict_types=1);

use Modules\Kernel\Api\HowAServiceTookIt;
use Modules\Kernel\Api\HowItEnded;
use Modules\Kernel\Api\HowServicesTookIt;
use Modules\Kernel\Api\HowToUndoIt;
use Modules\Kernel\Api\ServiceId;
use Modules\Updates\Api\Queries\NotArrivedFirst;

/** One service's share of an applied update. */
function howItWentFor(string $service, HowItEnded $ending): HowAServiceTookIt
{
    return HowAServiceTookIt::of(ServiceId::called($service), $ending, HowToUndoIt::Rollback);
}

/**
 * The order a reading comes out in, as names a case can compare.
 *
 * @return list<string>
 */
function readInOrder(HowServicesTookIt $went): array
{
    $named = [];

    foreach ($went as $took) {
        $named[] = $took->service()->named();
    }

    return $named;
}

it('N2-R18 — puts what did not arrive above what did', function (): void {
    $went = HowServicesTookIt::these(
        howItWentFor('jellyfin', HowItEnded::Updated),
        howItWentFor('sonarr', HowItEnded::NotStarted),
        howItWentFor('radarr', HowItEnded::Updated),
        howItWentFor('prowlarr', HowItEnded::NotReached),
    );

    expect(readInOrder(new NotArrivedFirst()->over($went)))
        ->toBe(['sonarr', 'prowlarr', 'jellyfin', 'radarr']);
});

it('N2-R18 — does not rank a network against a service against a machine', function (): void {
    // The decision this query refuses to make. All three are failures and the
    // stack's order between them is kept, because which of them matters most
    // is a judgement about a situation this app cannot see.
    $went = HowServicesTookIt::these(
        howItWentFor('prowlarr', HowItEnded::NotReached),
        howItWentFor('sonarr', HowItEnded::NotFetched),
        howItWentFor('radarr', HowItEnded::NotStarted),
    );

    expect(readInOrder(new NotArrivedFirst()->over($went)))
        ->toBe(['prowlarr', 'sonarr', 'radarr']);
});

it('keeps the order equals arrived in', function (): void {
    // The order the stack reports is the order it applied the update, which is
    // information: a service that failed because the one before it failed reads
    // differently the other way round.
    $went = HowServicesTookIt::these(
        howItWentFor('jellyfin', HowItEnded::Updated),
        howItWentFor('sonarr', HowItEnded::Updated),
        howItWentFor('radarr', HowItEnded::Updated),
    );

    expect(readInOrder(new NotArrivedFirst()->over($went)))
        ->toBe(['jellyfin', 'sonarr', 'radarr']);
});

it('leaves a reading with nothing in it alone', function (): void {
    expect(new NotArrivedFirst()->over(HowServicesTookIt::none())->isEmpty())->toBeTrue();
});
