<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use function array_map;
use function array_shift;
use function array_values;
use function count;

use Modules\Kernel\Api\FindingTitles;
use Modules\Kernel\Api\HowASearchWent;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatASearchFound;
use Modules\Kernel\Api\WhatToLookFor;
use Modules\Kernel\Api\WhereTheNextPageBegins;

use function sprintf;

/**
 * A stack answering a member's search with the pages a test hands it, in order, and remembering what it was asked.
 */
final class AStackThatFindsTitles implements FindingTitles
{
    /** @var list<string> */
    private array $asked = [];

    /** @param list<HowASearchWent> $answers */
    private function __construct(private array $answers) {}

    /** A stack answering each search with the next of these pages. */
    public static function finding(WhatASearchFound ...$pages): self
    {
        return new self(array_values(array_map(HowASearchWent::found(...), $pages)));
    }

    /** A stack answering every search with this obstacle. */
    public static function met(Obstacle $why): self
    {
        return new self([HowASearchWent::refused($why)]);
    }

    public function firstPage(Stack $stack, Session $session, WhatToLookFor $words): HowASearchWent
    {
        $this->asked[] = $words->said();

        return $this->next();
    }

    public function pageAfter(Stack $stack, Session $session, WhatToLookFor $words, WhereTheNextPageBegins $next): HowASearchWent
    {
        $this->asked[] = sprintf('%s after %s', $words->said(), $next->said());

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

    private function next(): HowASearchWent
    {
        return count($this->answers) > 1 ? array_shift($this->answers) : $this->answers[0];
    }
}
