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
        ->and(array_filter(is_array($lines) ? $lines : [], static fn(mixed $line): bool => is_string($line) && preg_match($word, mb_strtolower($line)) === 1))->toBe([]);
})->with(['en', 'nl'])->with([
    '/lemonfiber/', '/stack/', '/server/', '/certifica/', '/sessi/', '/\bapi\b/', '/machine/', '/ad+res/', '/versi/', '/pair|kop+el/',
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
