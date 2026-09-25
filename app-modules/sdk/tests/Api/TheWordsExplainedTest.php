<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function array_diff_key;
use function expect;
use function implode;
use function it;

use Lemonfiber\Sdk\Envelope\Envelope;
use Modules\Kernel\Api\AWordInUse;
use Modules\Kernel\Api\TheGlossary;
use Modules\Sdk\Api\GlossaryIsUnreadable;
use Modules\Sdk\Api\TheWordsExplained;

use function sprintf;

use Tests\Support\WhatTheContractAccepts;

/**
 * A `glossary` envelope holding whatever the case under test is about.
 *
 * @return Envelope<mixed>
 */
function glossarySaying(mixed $data): Envelope
{
    return new Envelope(1, 'glossary', $data);
}

/**
 * One word with everything said.
 *
 * @return array<string, mixed>
 */
function aWordInFull(): array
{
    return ['word' => 'seed', 'short' => 'Sharing a finished download', 'deep' => 'Uploading pieces to peers', 'also_called' => ['sharing'], 'forms' => ['seeding', 'seeded']];
}

/**
 * Every word the glossary read, as one line.
 *
 * @param list<mixed> $words
 */
function theGlossaryRead(array $words): string
{
    $said = [];

    foreach (TheWordsExplained::in(glossarySaying(['words' => $words])) as $word) {
        $said[] = sprintf('%s|%s|%s|%s', $word->word(), $word->short(), $word->deep(), implode(',', [...$word->alsoCalled()]));
    }

    return implode(' / ', $said);
}

it('stands in for a stack with a payload the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('GlossaryEnvelope', ['api_version' => 1, 'kind' => 'glossary', 'data' => ['words' => [aWordInFull()]]]))->toBe([]);
});

it('reads every word in order, with both glosses and every other name', function (): void {
    expect(theGlossaryRead([aWordInFull(), ['word' => 'pin', 'short' => 'Held at', 'also_called' => ['lock', 'hold'], 'forms' => []]]))
        ->toBe('seed|Sharing a finished download|Uploading pieces to peers|sharing / pin|Held at||lock,hold');
});

it('reads a longer gloss that is null or absent as none', function (): void {
    expect(theGlossaryRead([[...aWordInFull(), 'deep' => null]]))->toBe('seed|Sharing a finished download||sharing')
        ->and(theGlossaryRead([array_diff_key(aWordInFull(), ['deep' => true])]))->toBe('seed|Sharing a finished download||sharing');
});

it('reads an empty glossary as one with no words', function (): void {
    expect(TheWordsExplained::in(glossarySaying(['words' => []])))->toBeInstanceOf(TheGlossary::class)->toHaveCount(0);
});

it('refuses a payload with no data, or with no list of words', function (mixed $data, string $field): void {
    expect(fn(): TheGlossary => TheWordsExplained::in(glossarySaying($data)))->toThrow(GlossaryIsUnreadable::class, sprintf('`%s`', $field));
})->with([
    ['nothing', 'data'],
    [[], 'words'],
    [['words' => 'pin'], 'words'],
    [['words' => ['a' => aWordInFull()]], 'words'],
]);

it('refuses a word that is not one, naming its position and the field', function (mixed $word, string $field): void {
    expect(fn(): string => theGlossaryRead([aWordInFull(), $word]))->toThrow(GlossaryIsUnreadable::class, sprintf('Word 1 of the glossary has no readable `%s`', $field));
})->with([
    ['pin', 'word'],
    [array_diff_key(aWordInFull(), ['word' => true]), 'word'],
    [[...aWordInFull(), 'word' => ' '], 'word'],
    [[...aWordInFull(), 'short' => 7], 'short'],
    [[...aWordInFull(), 'deep' => ' '], 'deep'],
    [[...aWordInFull(), 'deep' => false], 'deep'],
    [array_diff_key(aWordInFull(), ['also_called' => true]), 'also_called'],
    [[...aWordInFull(), 'also_called' => 'sharing'], 'also_called'],
    [[...aWordInFull(), 'also_called' => ['a' => 'sharing']], 'also_called'],
    [[...aWordInFull(), 'also_called' => ['sharing', ' ']], 'also_called'],
    [[...aWordInFull(), 'also_called' => [7]], 'also_called'],
    [array_diff_key(aWordInFull(), ['forms' => true]), 'forms'],
    [[...aWordInFull(), 'forms' => 'seeding'], 'forms'],
    [[...aWordInFull(), 'forms' => ['seeding', '']], 'forms'],
]);

it('finds a word by every form lemonfiber writes it in, and not by one it does not', function (): void {
    $glossary = TheWordsExplained::in(glossarySaying(['words' => [aWordInFull()]]));

    expect($glossary->explaining(AWordInUse::named('seeding')))->toHaveCount(1)
        ->and($glossary->explaining(AWordInUse::named('Seeded')))->toHaveCount(1)
        ->and($glossary->explaining(AWordInUse::named('seeder')))->toHaveCount(0);
});
