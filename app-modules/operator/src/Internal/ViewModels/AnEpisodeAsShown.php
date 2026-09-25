<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** A wanted episode not here yet, and the stage it rests at, flattened for a template. */
final readonly class AnEpisodeAsShown
{
    /**
     * @param string $word the stage as the stack names it
     * @param string $said the catalogue key for the sentence beside it
     */
    public function __construct(
        public int $season,
        public int $number,
        public string $title,
        public string $word,
        public string $said,
    ) {}
}
