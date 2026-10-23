<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function expect;
use function it;
use function iterator_to_array;

use Lemonfiber\Sdk\Envelope\Envelope;
use Modules\Sdk\Api\QualityIsUnreadable;
use Modules\Sdk\Api\WhatIsChosen;
use Tests\Support\WhatTheContractAccepts;

/**
 * A `quality` envelope holding the choices given, and nothing else unusual, with any field replaced.
 *
 * @param list<mixed>  $choices
 * @param array<mixed> $changed
 *
 * @return Envelope<mixed>
 */
function qualityChoosing(array $choices, array $changed = []): Envelope
{
    return new Envelope(1, 'quality', [
        'choices' => $choices,
        'disposition' => 'shown',
        'customised' => false,
        'offered' => whatAChoiceIsMadeFrom(),
        ...$changed,
    ]);
}

/**
 * What the stack offers a choice from, with any list replaced.
 *
 * @param  array<mixed> $changed
 * @return array<mixed>
 */
function whatAChoiceIsMadeFrom(array $changed = []): array
{
    return ['presets' => ['720p', '1080p'], 'formats' => ['mp3', 'flac'], 'media_types' => ['tv', 'movies', 'music'], ...$changed];
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
    expect(WhatTheContractAccepts::complaintsAbout('QualityEnvelope', ['api_version' => 1, 'kind' => 'quality', 'data' => ['choices' => [aChoiceInForce()], 'disposition' => 'shown', 'customised' => false, 'rehearsed' => false, 'offered' => whatAChoiceIsMadeFrom()]]))->toBe([]);
});

it('reads what a choice may be made from, in the stack\'s order', function (): void {
    $offered = WhatIsChosen::in(qualityChoosing([aChoiceInForce()]))->offered();

    expect([iterator_to_array($offered->presets(), preserve_keys: false), iterator_to_array($offered->formats(), preserve_keys: false), iterator_to_array($offered->kinds(), preserve_keys: false)])
        ->toBe([['720p', '1080p'], ['mp3', 'flac'], ['tv', 'movies', 'music']]);
});

it('refuses what is offered where it is missing, or a list of it is not a list of words', function (array $changed, string $said): void {
    expect(static fn(): mixed => WhatIsChosen::in(qualityChoosing([aChoiceInForce()], $changed)))
        ->toThrow(QualityIsUnreadable::class, $said);
})->with([
    'nothing offered' => [['offered' => null], 'no readable `offered`'],
    'no presets' => [['offered' => whatAChoiceIsMadeFrom(['presets' => null])], 'no readable `presets`'],
    'no formats' => [['offered' => whatAChoiceIsMadeFrom(['formats' => 'flac'])], 'no readable `formats`'],
    'no kinds' => [['offered' => whatAChoiceIsMadeFrom(['media_types' => null])], 'no readable `media_types`'],
    'a blank preset' => [['offered' => whatAChoiceIsMadeFrom(['presets' => ['720p', ' ']])], 'Entry 1 of `offered` in the quality envelope has no readable `presets`'],
    'a format that is not text' => [['offered' => whatAChoiceIsMadeFrom(['formats' => [3]])], 'Entry 0 of `offered` in the quality envelope has no readable `formats`'],
]);

it('reads what a choice changes where the stack says it, and nothing where it does not', function (): void {
    expect(WhatIsChosen::in(qualityChoosing([aChoiceInForce()], ['changes' => 'What is fetched from now on']))->changes())->toBe('What is fetched from now on')
        ->and(WhatIsChosen::in(qualityChoosing([aChoiceInForce()], ['changes' => null]))->changes())->toBe('')
        ->and(WhatIsChosen::in(qualityChoosing([aChoiceInForce()]))->changes())->toBe('')
        ->and(static fn(): mixed => WhatIsChosen::in(qualityChoosing([aChoiceInForce()], ['changes' => 3])))
        ->toThrow(QualityIsUnreadable::class, 'no readable `changes`');
});
