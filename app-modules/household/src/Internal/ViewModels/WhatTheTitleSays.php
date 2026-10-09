<?php

declare(strict_types=1);

namespace Modules\Household\Internal\ViewModels;

/**
 * One title in full, flattened for a template.
 *
 * Strings and catalogue keys with what fills them. A part the core did not
 * state is empty, and the template draws nothing for it rather than a blank.
 */
final readonly class WhatTheTitleSays
{
    /**
     * @param string                  $about           what it is about, or empty
     * @param string                  $runs            how long it runs, as a catalogue key, or empty where unstated
     * @param array<string, int|string> $runsFilling     what that key is filled with
     * @param list<string>            $genres          its genres, in the server's words and order
     * @param string                  $certificate     its certificate, or empty
     * @param string                  $released        the line saying when it came out, as a catalogue key, or empty where unstated
     * @param array<string, int>      $releasedFilling its day and year
     * @param string                  $releasedIn      its month, as a catalogue key
     * @param list<WhatOneSeasonSays> $seasons         a series' seasons in order; none for anything else
     */
    public function __construct(
        public WhatOnePosterSays $poster,
        public string $about,
        public string $runs,
        public array $runsFilling,
        public array $genres,
        public string $certificate,
        public string $released,
        public array $releasedFilling,
        public string $releasedIn,
        public WhatPlayingSays $playing,
        public array $seasons,
    ) {}
}
