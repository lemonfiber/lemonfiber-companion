<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function expect;
use function it;

use Lemonfiber\Sdk\Envelope\Envelope;
use Modules\Sdk\Api\QualityIsUnreadable;
use Modules\Sdk\Api\WhatIsChosen;
use Tests\Support\WhatTheContractAccepts;

/**
 * A `quality` envelope holding the choices given, and nothing else unusual.
 *
 * @param list<mixed> $choices
 *
 * @return Envelope<mixed>
 */
function qualityChoosing(array $choices): Envelope
{
    return new Envelope(1, 'quality', ['choices' => $choices, 'disposition' => 'shown', 'customised' => false]);
}

/**
 * One preset in force, with any field replaced.
 *
 * @return array<string, mixed>
 */
function aChoiceInForce(string $preset = 'Balanced'): array
{
    return [
        'scope' => 'everything',
        'preset' => $preset,
        'means' => 'On a TV',
        'resolution' => '1080p',
        'size_per_hour' => '~2 GB',
        'transcoding' => 'Plays directly',
        'needs_transcoding_here' => false,
    ];
}

it('refuses a choice whose word is only spaces, before anything is built from it', function (): void {
    expect(static fn(): mixed => WhatIsChosen::in(qualityChoosing([aChoiceInForce(' ')])))
        ->toThrow(QualityIsUnreadable::class, 'Entry 0 of `choices` in the quality envelope has no readable `preset`');
});

it('refuses a choice whose word is empty, before anything is built from it', function (): void {
    expect(static fn(): mixed => WhatIsChosen::in(qualityChoosing([aChoiceInForce(''), aChoiceInForce()])))
        ->toThrow(QualityIsUnreadable::class, 'Entry 0 of `choices`');
});

it('names the entry that could not be read by where it sits in the list', function (): void {
    expect(static fn(): mixed => WhatIsChosen::in(qualityChoosing([aChoiceInForce(), aChoiceInForce(), 'Maximum'])))
        ->toThrow(QualityIsUnreadable::class, 'Entry 2 of `choices`')
        ->and(static fn(): mixed => WhatIsChosen::in(qualityChoosing([aChoiceInForce(), aChoiceInForce(), aChoiceInForce(' ')])))
        ->toThrow(QualityIsUnreadable::class, 'Entry 2 of `choices`');
});

it('spoils a payload the contract would accept in one place only', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('QualityEnvelope', ['api_version' => 1, 'kind' => 'quality', 'data' => ['choices' => [aChoiceInForce()], 'disposition' => 'shown', 'customised' => false]]))->toBe([]);
});
