<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_map;
use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\AWord;
use Modules\Kernel\Api\LookingFor;
use Modules\Kernel\Api\TheGlossary;

/**
 * The words a glossary holds, by name, in order.
 *
 * @return list<string>
 */
function theWordsIn(TheGlossary $glossary): array
{
    return array_map(static fn(AWord $word): string => $word->word(), iterator_to_array($glossary, preserve_keys: false));
}

/** Three words, one with another name. */
function aSmallGlossary(): TheGlossary
{
    return TheGlossary::of(
        AWord::explained('pin', 'The version a service is held at', ''),
        AWord::explained('seeding', 'Sharing a finished download', '', 'sharing'),
        AWord::explained('stack', 'Everything lemonfiber runs on a machine', ''),
    );
}

it('holds its words in the order given, as a list however they are handed', function (): void {
    $glossary = TheGlossary::of(...['b' => AWord::explained('b', 'B', ''), 'a' => AWord::explained('a', 'A', '')]);

    expect(theWordsIn($glossary))->toBe(['b', 'a'])
        ->and(iterator_to_array($glossary, preserve_keys: true))->toHaveKeys([0, 1])
        ->and($glossary)->toHaveCount(2);
});

it('keeps every word where nothing is looked for', function (): void {
    expect(theWordsIn(aSmallGlossary()->matching(LookingFor::nothing())))->toBe(['pin', 'seeding', 'stack'])
        ->and(theWordsIn(aSmallGlossary()->matching(LookingFor::text('  '))))->toBe(['pin', 'seeding', 'stack']);
});

it('keeps the words a search finds by name or other name, in order, as a list', function (): void {
    $found = aSmallGlossary()->matching(LookingFor::text('s'));

    expect(theWordsIn($found))->toBe(['seeding', 'stack'])
        ->and(iterator_to_array($found, preserve_keys: true))->toHaveKeys([0, 1])
        ->and(theWordsIn(aSmallGlossary()->matching(LookingFor::text('sharing'))))->toBe(['seeding'])
        ->and(aSmallGlossary()->matching(LookingFor::text('nothing like it')))->toHaveCount(0);
});
