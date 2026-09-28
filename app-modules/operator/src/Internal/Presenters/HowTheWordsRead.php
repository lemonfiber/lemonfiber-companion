<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use function count;
use function implode;

use Modules\Kernel\Api\AWord;
use Modules\Kernel\Api\AWordInUse;
use Modules\Kernel\Api\LookingFor;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\TheGlossary;
use Modules\Operator\Internal\ViewModels\AWordAskedAbout;
use Modules\Operator\Internal\ViewModels\AWordAsShown;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\TheWordsTurnedOutToBe;

/**
 * What asking a stack for its glossary produces, as the fields a screen draws.
 *
 * `F2`: data in, view model out. The search narrows what is shown and never
 * what was fetched.
 *
 * **A word searched for that the glossary has no entry for may be asked for.**
 * Offered for that one word, and not again once the stack has said it has no
 * entry either: that is its answer, and the word is then shown as it came.
 */
final readonly class HowTheWordsRead
{
    /** This device no longer holds a session for that stack. */
    public function signedOut(): TheWordsTurnedOutToBe
    {
        return new TheWordsTurnedOutToBe(HowTheReadingWent::theSessionEnded(), [], isSearching: false);
    }

    /**
     * The stack answered, and these are its words, narrowed by what is looked
     * for, with the one named open, and what asking for the word looked for
     * came to where it was asked for.
     */
    public function this(TheGlossary $glossary, LookingFor $looking, string $open, ?AWordAskedAbout $asked = null): TheWordsTurnedOutToBe
    {
        $shown = [];

        foreach ($glossary->matching($looking) as $word) {
            $shown[] = $this->shown($word, $open);
        }

        if (! $looking->isSearching() || count($glossary->explaining(AWordInUse::named($looking->typed()))) > 0) {
            return new TheWordsTurnedOutToBe(HowTheReadingWent::itCameBack(), $shown, $looking->isSearching());
        }

        $answered = $asked instanceof AWordAskedAbout && AWordInUse::named($asked->word)->is(AWordInUse::named($looking->typed())) ? $asked : null;
        $unexplained = $answered instanceof AWordAskedAbout && $answered->unexplained;

        return new TheWordsTurnedOutToBe(
            HowTheReadingWent::itCameBack(),
            $shown,
            isSearching: true,
            mayAsk: $unexplained ? '' : $looking->typed(),
            unexplained: $unexplained ? $looking->typed() : '',
            askingMet: $answered instanceof AWordAskedAbout ? $answered->met : '',
        );
    }

    /** It did not, and this is what the operator met. */
    public function met(Obstacle $why): TheWordsTurnedOutToBe
    {
        return new TheWordsTurnedOutToBe(HowTheReadingWent::somethingStopped($why), [], isSearching: false);
    }

    /** One word, open where it is the one named. */
    private function shown(AWord $word, string $open): AWordAsShown
    {
        // Collected by hand rather than through `iterator_to_array`: the names
        // are a list, so a `preserve_keys` argument could not be wrong (`C10`).
        $names = [];

        foreach ($word->alsoCalled() as $name) {
            $names[] = $name;
        }

        return new AWordAsShown(
            word: $word->word(),
            short: $word->short(),
            deep: $word->deep(),
            alsoCalled: implode(', ', $names),
            isOpen: $word->deep() !== '' && $open !== '' && $word->explains(AWordInUse::named($open)),
        );
    }
}
