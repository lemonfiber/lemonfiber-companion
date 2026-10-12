<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** The household's hours, flattened for a template. */
final readonly class TheHoursAsShown
{
    /**
     * @param string $from when the household's day starts, as the stack wrote it
     * @param string $to   when it ends, as the stack wrote it
     * @param string $zone the zone they are read in, empty where the stack did not say
     */
    public function __construct(
        public string $from,
        public string $to,
        public string $zone,
    ) {}
}
