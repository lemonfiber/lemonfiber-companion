<?php

declare(strict_types=1);

use Modules\Household\Internal\Presenters\HowTheirLanguagesRead;
use Modules\Household\Internal\ViewModels\ALanguageAsShown;
use Modules\Kernel\Api\HearIn;
use Modules\Kernel\Api\ReadIn;
use Modules\Kernel\Api\TheirLanguages;
use Tests\TestCase;

// The catalogue is read, so the application is booted.
uses(TestCase::class);

/**
 * Each language offered, as the word it is kept under, marked where chosen. Named for this file.
 *
 * @param list<ALanguageAsShown> $offered
 *
 * @return list<string>
 */
function theLanguagesItOffers(array $offered): array
{
    return array_map(static fn(ALanguageAsShown $language): string => sprintf('%s%s', $language->word, $language->chosen ? '*' : ''), $offered);
}

it('offers every language to hear and to read, and marks the ones chosen', function (): void {
    $shown = new HowTheirLanguagesRead()->chosen(TheirLanguages::of(HearIn::Dutch, ReadIn::Nothing));

    expect(theLanguagesItOffers($shown->hear))->toBe(['original', 'nl*', 'en'])
        ->and(theLanguagesItOffers($shown->read))->toBe(['none*', 'nl', 'en']);
});

it('says every language in every language, each apart from the others', function (string $locale): void {
    $shown = new HowTheirLanguagesRead()->chosen(TheirLanguages::asTheTitleComes());

    foreach ([$shown->hear, $shown->read] as $offered) {
        $said = [];

        foreach ($offered as $language) {
            $line = __($language->said, locale: $locale);

            expect($line)->not->toBe($language->said);
            $said[] = is_string($line) ? $line : '';
        }

        expect(array_unique($said))->toHaveCount(count($offered));
    }
})->with(['en', 'nl']);
