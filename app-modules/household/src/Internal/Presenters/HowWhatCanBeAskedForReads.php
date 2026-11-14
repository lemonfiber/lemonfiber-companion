<?php

declare(strict_types=1);

namespace Modules\Household\Internal\Presenters;

use function count;

use Modules\Household\Internal\ViewModels\AChoiceAsShown;
use Modules\Household\Internal\ViewModels\WhatCanBeSearchedAsShown;
use Modules\Kernel\Api\KindOfTitle;
use Modules\Kernel\Api\WhatCanBeAskedFor;
use Modules\Kernel\Api\WhichTitles;

use function sprintf;

/**
 * What a member can search among, as the chips the Search tab offers.
 *
 * A row of one chip is not drawn, since there is nothing to choose between,
 * and the tab says it is for films and series where that is all it offers.
 */
final readonly class HowWhatCanBeAskedForReads
{
    public const string AMONG = 'household.search.among.%s';

    public const string AMONG_FILMS_AND_SERIES = 'household.search.among.films-and-series';

    public const string UNIT = 'household.search.unit.%s';

    public const string EXPLAINED = 'household.search.explained';

    public const string EXPLAINED_EVERYTHING = 'household.search.explained_everything';

    public const string PLACEHOLDER = 'household.search.placeholder';

    public const string PLACEHOLDER_EVERYTHING = 'household.search.placeholder_everything';

    public function chips(WhatCanBeAskedFor $can, WhichTitles $chosen): WhatCanBeSearchedAsShown
    {
        $kinds = $this->kinds($can, $chosen);
        $units = $this->units($can, $chosen);
        $filmsAndSeriesAlone = $kinds === [] || (count($kinds) === 1 && $kinds[0]->word === WhichTitles::FILMS_AND_SERIES);

        return new WhatCanBeSearchedAsShown(
            kinds: count($kinds) > 1 ? $kinds : [],
            units: count($units) > 1 ? $units : [],
            explained: $filmsAndSeriesAlone ? self::EXPLAINED : self::EXPLAINED_EVERYTHING,
            placeholder: $filmsAndSeriesAlone ? self::PLACEHOLDER : self::PLACEHOLDER_EVERYTHING,
        );
    }

    /** @return list<AChoiceAsShown> */
    private function kinds(WhatCanBeAskedFor $can, WhichTitles $chosen): array
    {
        $kinds = [];

        foreach ($can->kinds() as $kind) {
            $kinds[] = new AChoiceAsShown(said: $kind->either(filmsAndSeries: static fn(): InWords => new InWords(self::AMONG_FILMS_AND_SERIES), of: static fn(KindOfTitle $of): InWords => new InWords(sprintf(self::AMONG, $of->value)))->said, word: $kind->kindWord(), chosen: $kind->kindWord() === $chosen->kindWord());
        }

        return $kinds;
    }

    /** @return list<AChoiceAsShown> */
    private function units(WhatCanBeAskedFor $can, WhichTitles $chosen): array
    {
        $units = [];

        foreach ($can->unitsBeside($chosen) as $unit) {
            $units[] = new AChoiceAsShown(said: sprintf(self::UNIT, $unit->unitWord()), word: $unit->word(), chosen: $unit->is($chosen));
        }

        return $units;
    }
}
