<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\AWordInUse;
use Modules\Kernel\Api\TheGlossary;
use Modules\Operator\Internal\ViewModels\AGlossAsShown;
use Modules\Operator\Internal\WhereAStackIs;

/**
 * What a word a screen draws means, as the fields the gloss beside it draws.
 *
 * `F2`: data in, view model out. The first word the glossary lists for it
 * explains it; none leaves it unexplained, and nothing here makes one up.
 */
final readonly class HowAGlossReads
{
    public function of(TheGlossary $glossary, AWordInUse $drawn, WhereAStackIs $goes): AGlossAsShown
    {
        foreach ($glossary->explaining($drawn) as $word) {
            return new AGlossAsShown(
                word: $word->word(),
                short: $word->short(),
                goes: $word->deep() === '' ? '' : $goes->ofItself()->wordAbout(AWordInUse::named($word->word())),
            );
        }

        return new AGlossAsShown(word: $drawn->said(), short: '', goes: '');
    }
}
