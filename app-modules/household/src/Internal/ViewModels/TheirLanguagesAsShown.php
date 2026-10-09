<?php

declare(strict_types=1);

namespace Modules\Household\Internal\ViewModels;

/**
 * What a member chose to hear and read titles in, and every language they can choose for each.
 */
final readonly class TheirLanguagesAsShown
{
    /**
     * @param list<ALanguageAsShown> $hear every language to hear titles in, the one chosen now marked
     * @param list<ALanguageAsShown> $read every language to read subtitles in, and none, the one chosen now marked
     */
    public function __construct(
        public array $hear,
        public array $read,
    ) {}
}
