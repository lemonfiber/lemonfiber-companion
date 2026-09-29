<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What taking somebody out costs, or did, as the rows that draw it.
 */
final readonly class ARemovalAsShown
{
    /**
     * @param string       $name          the name their account is held under, as the media server spells it
     * @param bool         $carriedOut    whether they were taken out, rather than the cost only described
     * @param string       $revokedSaid   the catalogue key for how far it reached
     * @param bool         $isDone        whether they are out everywhere, which is the only reach drawn as done
     * @param int          $requests      how many of their requests go with them
     * @param string       $asksSaid      the catalogue key for whether the request service holds an account for them
     * @param list<string> $findings      what the stack found, each in its own words
     */
    public function __construct(
        public string $name,
        public bool $carriedOut,
        public string $revokedSaid,
        public bool $isDone,
        public int $requests,
        public string $asksSaid,
        public array $findings,
    ) {}
}
