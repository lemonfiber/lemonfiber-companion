<?php

declare(strict_types=1);

namespace Modules\Household\Internal\Presenters;

use Modules\Household\Internal\ViewModels\ALanguageAsShown;
use Modules\Household\Internal\ViewModels\TheirLanguagesAsShown;
use Modules\Kernel\Api\HearIn;
use Modules\Kernel\Api\ReadIn;
use Modules\Kernel\Api\TheirLanguages;

/**
 * A member's choice of languages, as the two rows of chips Profile offers it with.
 *
 * `F2`: data in, view model out. Every case of {@see HearIn} and of
 * {@see ReadIn} is offered, so a language added to either is offered without
 * an edit to the template.
 */
final readonly class HowTheirLanguagesRead
{
    public function chosen(TheirLanguages $chosen): TheirLanguagesAsShown
    {
        $hear = [];

        foreach (HearIn::cases() as $case) {
            $hear[] = new ALanguageAsShown(said: $this->heard($case), word: $case->value, chosen: $case === $chosen->hear());
        }

        $read = [];

        foreach (ReadIn::cases() as $case) {
            $read[] = new ALanguageAsShown(said: $this->read($case), word: $case->value, chosen: $case === $chosen->read());
        }

        return new TheirLanguagesAsShown(hear: $hear, read: $read);
    }

    /** The catalogue key for the words that offer hearing titles in this language. */
    private function heard(HearIn $case): string
    {
        return match ($case) {
            HearIn::TheOriginal => 'household.languages.hear_original',
            HearIn::Dutch => 'household.languages.dutch',
            HearIn::English => 'household.languages.english',
        };
    }

    /** The catalogue key for the words that offer reading subtitles in this language, or none. */
    private function read(ReadIn $case): string
    {
        return match ($case) {
            ReadIn::Nothing => 'household.languages.no_subtitles',
            ReadIn::Dutch => 'household.languages.dutch',
            ReadIn::English => 'household.languages.english',
        };
    }
}
