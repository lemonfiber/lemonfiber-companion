<?php

declare(strict_types=1);

use Modules\Household\Internal\ViewModels\WhatAMemberIsTold;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\WhatTheHouseholdWasTold;
use Tests\TestCase;

// The catalogue is read, so the application is booted.
uses(TestCase::class);

it('tells a member every obstacle as the household is told it', function (KindOfObstacle $kind): void {
    $why = Obstacle::of($kind);

    expect(WhatAMemberIsTold::met($why))->toBe($why->saidToTheHousehold())
        ->and(WhatAMemberIsTold::remedy($why))->toBe($why->remedyForTheHousehold());
})->with(array_values(array_filter(KindOfObstacle::cases(), static fn(KindOfObstacle $kind): bool => $kind !== KindOfObstacle::VersionsDisagree)));

it('never names the software, the machine or how it is reached to a member, in either language', function (string $locale, string $word): void {
    $lines = trans('household.stood_in_the_way', [], $locale);

    expect($lines)->toBeArray()
        ->and(array_filter(is_array($lines) ? $lines : [], static fn(mixed $line): bool => is_string($line) && str_contains(mb_strtolower($line), $word)))->toBe([]);
})->with([
    ['en', 'lemonfiber'], ['en', 'stack'], ['en', 'server'], ['en', 'certificate'], ['en', 'session'], ['en', 'api'],
    ['en', 'machine'], ['en', 'address'], ['en', 'version'], ['en', 'pair'],
    ['nl', 'lemonfiber'], ['nl', 'stack'], ['nl', 'server'], ['nl', 'certificaat'], ['nl', 'sessie'], ['nl', 'api'],
    ['nl', 'machine'], ['nl', 'adres'], ['nl', 'versie'], ['nl', 'koppel'],
]);

it('tells a member the core\'s own sentence and remedy wherever it wrote them, a house too old included', function (KindOfObstacle $kind): void {
    $why = Obstacle::of($kind)->withWhatTheHouseholdWasTold(WhatTheHouseholdWasTold::said('Your library is not answering right now.', 'Try again in a little while'));

    expect(WhatAMemberIsTold::met($why))->toBe('Your library is not answering right now.')
        ->and(WhatAMemberIsTold::remedy($why))->toBe('Try again in a little while')
        ->and(WhatAMemberIsTold::isInTheStacksWords($why))->toBeTrue()
        ->and(WhatAMemberIsTold::isInTheStacksWords(Obstacle::of($kind)))->toBeFalse();
})->with([
    'the household could not be asked' => [KindOfObstacle::MediaServerDidNotAnswer],
    'a house too old' => [KindOfObstacle::NotOnThisStack],
]);
