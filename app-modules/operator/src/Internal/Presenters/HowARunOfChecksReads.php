<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Health\Api\Queries\TheCauseBeforeItsSymptoms;
use Modules\Health\Api\Queries\WorstFirst;
use Modules\Kernel\Api\ACheckThatDisturbs;
use Modules\Kernel\Api\Category;
use Modules\Kernel\Api\Findings;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Report;
use Modules\Kernel\Api\WhatARunCovers;
use Modules\Operator\Internal\ViewModels\ADisturbanceAsShown;
use Modules\Operator\Internal\ViewModels\ARunOffered;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\TheChecksTurnedOutToBe;
use Modules\Operator\Internal\ViewModels\TheDisturbancesAsShown;
use Modules\Operator\Internal\ViewModels\WhatOneFindingSays;

/**
 * What running every check, or a family of them, would disturb and came to, as the fields a screen draws.
 *
 * Before the run it lists every finding the run covers that the stack left
 * unverified because checking it disturbs something, each with the stack's
 * bound; after it, every finding the run came back with, worst first and each
 * cause before its symptoms, as Health orders them.
 */
final readonly class HowARunOfChecksReads
{
    /** A run of every check, said as its title. */
    public const string EVERY = 'health.run_every.every';

    /** A run of one family, said as its title with the family. */
    public const string FAMILY = 'health.run_every.family';

    /** A route naming neither every check nor a family, said as its title. */
    public const string UNKNOWN = 'health.run_every.unknown';

    /** The link to a run of every check. */
    public const string OFFER_EVERY = 'health.run_every.offer_every';

    /** The link to a run of the family being read. */
    public const string OFFER_FAMILY = 'health.run_every.offer_family';

    /**
     * The runs Health offers: every check, and the family being read where one is.
     *
     * @return list<ARunOffered>
     */
    public function offered(?Category $reading): array
    {
        $every = new ARunOffered(said: self::OFFER_EVERY, familySaid: '', covers: WhatARunCovers::everyCheck());

        return $reading instanceof Category
            ? [$every, new ARunOffered(said: self::OFFER_FAMILY, familySaid: $reading->saidOnTheScreen(), covers: WhatARunCovers::theFamily($reading))]
            : [$every];
    }

    /** A route that names no run this app knows, which offers nothing to run. */
    public function unknown(): TheChecksTurnedOutToBe
    {
        return new TheChecksTurnedOutToBe(went: HowTheReadingWent::itCameBack(), titleSaid: self::UNKNOWN, familySaid: '', disturbing: [], found: [], runnable: false);
    }

    /** What the run would disturb, read from what the stack last reported. */
    public function before(Report $report, WhatARunCovers $covers): TheChecksTurnedOutToBe
    {
        $disturbing = [];

        foreach ($this->covered($report->findings(), $covers) as $finding) {
            $title = $finding->title();
            $shown = $finding->whetherCheckingDisturbs()->either(
                so: static function (ACheckThatDisturbs $check) use ($title): TheDisturbancesAsShown {
                    $bound = new HowABoundReads()->of($check);

                    return TheDisturbancesAsShown::of(new ADisturbanceAsShown($title, $check->disturbs(), $bound->said, $bound->seconds));
                },
                not: static fn(): TheDisturbancesAsShown => TheDisturbancesAsShown::of(),
            );
            $disturbing = [...$disturbing, ...$shown->lines];
        }

        return $this->drawn(HowTheReadingWent::itCameBack(), $covers, $disturbing, []);
    }

    /** What the run came back with, every finding it covers. */
    public function after(Report $report, WhatARunCovers $covers): TheChecksTurnedOutToBe
    {
        $run = new TheCauseBeforeItsSymptoms()->over(new WorstFirst()->over($this->covered($report->findings(), $covers)));
        $reads = new HowAFindingReads($run);
        $found = [];

        foreach ($run as $finding) {
            $found[] = $reads->of($finding);
        }

        return $this->drawn(HowTheReadingWent::itCameBack(), $covers, [], $found);
    }

    /** It did not answer, and this is what the operator met. */
    public function met(Obstacle $why, WhatARunCovers $covers): TheChecksTurnedOutToBe
    {
        return $this->drawn(HowTheReadingWent::somethingStopped($why), $covers, [], []);
    }

    /** This device no longer holds a session for that stack. */
    public function signedOut(WhatARunCovers $covers): TheChecksTurnedOutToBe
    {
        return $this->drawn(HowTheReadingWent::theSessionEnded(), $covers, [], []);
    }

    /** The findings this run covers, in the order they arrived. */
    private function covered(Findings $findings, WhatARunCovers $covers): Findings
    {
        $kept = [];

        foreach ($findings as $finding) {
            if ($covers->covers($finding)) {
                $kept[] = $finding;
            }
        }

        return Findings::of(...$kept);
    }

    /**
     * @param list<ADisturbanceAsShown>                                       $disturbing
     * @param list<WhatOneFindingSays>   $found
     */
    private function drawn(HowTheReadingWent $went, WhatARunCovers $covers, array $disturbing, array $found): TheChecksTurnedOutToBe
    {
        return $covers->either(
            every: static fn(): TheChecksTurnedOutToBe => new TheChecksTurnedOutToBe(went: $went, titleSaid: self::EVERY, familySaid: '', disturbing: $disturbing, found: $found, runnable: true),
            family: static fn(Category $family): TheChecksTurnedOutToBe => new TheChecksTurnedOutToBe(went: $went, titleSaid: self::FAMILY, familySaid: $family->saidOnTheScreen(), disturbing: $disturbing, found: $found, runnable: true),
        );
    }
}
