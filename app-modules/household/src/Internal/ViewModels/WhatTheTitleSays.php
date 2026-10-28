<?php

declare(strict_types=1);

namespace Modules\Household\Internal\ViewModels;

/**
 * One title in full, flattened for a template.
 *
 * Strings and catalogue keys with what fills them. A part the core did not
 * state is empty or null, and the template draws nothing for it rather than a blank.
 */
final readonly class WhatTheTitleSays
{
    /**
     * @param string                    $about       what it is about, or empty
     * @param ALineAsShown|null         $runs        how long it runs, or null where unstated
     * @param list<string>              $genres      its genres, in the server's words and order
     * @param string                    $certificate its certificate, or empty
     * @param WhenItCameOutAsShown|null $released    when it came out, or null where unstated
     * @param list<WhatOneSeasonSays>   $seasons     a series' seasons in order; none for anything else
     */
    public function __construct(
        public WhatOnePosterSays $poster,
        public string $about,
        public ?ALineAsShown $runs,
        public array $genres,
        public string $certificate,
        public ?WhenItCameOutAsShown $released,
        public WhatPlayingSays $playing,
        public array $seasons,
    ) {}

    /** The title, its poster drawn from `$source`, or lettered where that is empty. */
    public function pictured(?string $source): self
    {
        return new self($this->poster->pictured($source), $this->about, $this->runs, $this->genres, $this->certificate, $this->released, $this->playing, $this->seasons);
    }
}
