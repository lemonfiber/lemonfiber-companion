<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use function array_map;
use function array_shift;
use function array_values;
use function count;

use Modules\Kernel\Api\FindingTitles;
use Modules\Kernel\Api\HowASearchWent;
use Modules\Kernel\Api\HowTheKindsWent;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatASearchFound;
use Modules\Kernel\Api\WhatCanBeAskedFor;
use Modules\Kernel\Api\WhatToLookFor;
use Modules\Kernel\Api\WhereTheNextPageBegins;
use Modules\Kernel\Api\WhichTitles;

use function sprintf;

/**
 * A stack answering a member's search with the pages a test hands it, in order, listing what a test hands it as searchable, and remembering what it was asked.
 */
final class AStackThatFindsTitles implements FindingTitles
{
    /** @var list<string> */
    private array $asked = [];

    /** How many times it was asked what can be searched among. */
    private int $listed = 0;

    private HowTheKindsWent $askable;

    /** @param list<HowASearchWent> $answers */
    private function __construct(private array $answers)
    {
        $this->askable = HowTheKindsWent::listed(WhatCanBeAskedFor::filmsAndSeriesAlone());
    }

    /** A stack answering each search with the next of these pages. */
    public static function finding(WhatASearchFound ...$pages): self
    {
        return new self(array_values(array_map(HowASearchWent::found(...), $pages)));
    }

    /** A stack answering each search with the next of these answers, a page or an obstacle. */
    public static function answering(HowASearchWent ...$answers): self
    {
        return new self(array_values($answers));
    }

    /** A stack answering every search with this obstacle. */
    public static function met(Obstacle $why): self
    {
        return new self([HowASearchWent::refused($why)]);
    }

    /** The same stack, answering what the member can search among with this. */
    public function listing(HowTheKindsWent $askable): self
    {
        $this->askable = $askable;

        return $this;
    }

    public function whatCanBeAskedFor(Stack $stack, Session $session): HowTheKindsWent
    {
        ++$this->listed;

        return $this->askable;
    }

    public function firstPage(Stack $stack, Session $session, WhatToLookFor $words): HowASearchWent
    {
        $this->asked[] = $this->among($words, $words->said());

        return $this->next();
    }

    public function pageAfter(Stack $stack, Session $session, WhatToLookFor $words, WhereTheNextPageBegins $next): HowASearchWent
    {
        $this->asked[] = $this->among($words, sprintf('%s after %s', $words->said(), $next->said()));

        return $this->next();
    }

    /**
     * What it was asked, in order: the words, and the place a later page began at.
     *
     * @return list<string>
     */
    public function asked(): array
    {
        return $this->asked;
    }

    /** How many times it was asked what can be searched among. */
    public function listed(): int
    {
        return $this->listed;
    }

    /** What was asked, with what it was looked for among where that was not films and series. */
    private function among(WhatToLookFor $words, string $asked): string
    {
        $which = $words->which()->word();

        return $which === WhichTitles::FILMS_AND_SERIES ? $asked : sprintf('%s among %s', $asked, $which);
    }

    private function next(): HowASearchWent
    {
        return count($this->answers) > 1 ? array_shift($this->answers) : $this->answers[0];
    }
}
