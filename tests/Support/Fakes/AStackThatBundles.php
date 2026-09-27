<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Closure;
use Modules\Kernel\Api\ABundleAsked;
use Modules\Kernel\Api\ABundleFetched;
use Modules\Kernel\Api\ABundleFile;
use Modules\Kernel\Api\AskingForHelp;
use Modules\Kernel\Api\AWrittenBundle;
use Modules\Kernel\Api\HowTheBundleIsGoing;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Underway;

/**
 * A stack that gathers support bundles without one existing.
 *
 * {@see AStackThatTakesCopies} for bundles: the counters are what let a test
 * say what was sent, what was followed and what was fetched, which no rendered
 * value can see.
 *
 * Not `readonly`: what was asked is written when the asking happens.
 */
final class AStackThatBundles implements AskingForHelp
{
    public const string THE_JOB = 'a-bundle-a-test-can-name';

    /** What a written bundle's file holds, as this stack serves it. */
    public const string THE_BYTES = "\x1F\x8B\x08\x00a bundle a test can read";

    /** @var list<ABundleAsked> */
    private array $asked = [];

    /** @var list<Job> */
    private array $followed = [];

    /** @var list<AWrittenBundle> */
    private array $fetched = [];

    /**
     * @param Closure(): Underway                      $asking
     * @param Closure(): HowTheBundleIsGoing           $becoming
     * @param Closure(AWrittenBundle): ABundleFetched  $serving
     */
    private function __construct(
        private readonly Closure $asking,
        private readonly Closure $becoming,
        private readonly Closure $serving,
    ) {}

    /** A stack that takes the bundle on, says `$became` when asked after it, and serves a written one's file. */
    public static function whichGathered(HowTheBundleIsGoing $became): self
    {
        return new self(
            static fn(): Underway => Underway::as(Job::named(self::THE_JOB)),
            static fn(): HowTheBundleIsGoing => $became,
            self::servingItsFile(),
        );
    }

    /** A stack that takes the bundle on and says `$became`, and meets fetching its file with `$why`. */
    public static function whichGatheredAndCouldNotServe(HowTheBundleIsGoing $became, Obstacle $why): self
    {
        return new self(
            static fn(): Underway => Underway::as(Job::named(self::THE_JOB)),
            static fn(): HowTheBundleIsGoing => $became,
            static fn(): ABundleFetched => ABundleFetched::met($why),
        );
    }

    /**
     * A stack that takes the first bundle on and, asked after it, says
     * `$became`, and meets every later one with `$why`.
     */
    public static function whichGatheredOnceThenMet(HowTheBundleIsGoing $became, Obstacle $why): self
    {
        $asked = 0;

        return new self(
            static function () use (&$asked, $why): Underway {
                $asked++;

                return $asked === 1 ? Underway::as(Job::named(self::THE_JOB)) : Underway::met($why);
            },
            static fn(): HowTheBundleIsGoing => $became,
            self::servingItsFile(),
        );
    }

    /** A stack that meets every question with the same obstacle. */
    public static function met(Obstacle $why): self
    {
        return new self(
            static fn(): Underway => Underway::met($why),
            static fn(): HowTheBundleIsGoing => HowTheBundleIsGoing::met($why),
            static fn(): ABundleFetched => ABundleFetched::met($why),
        );
    }

    public function ask(Stack $stack, Session $session, ABundleAsked $asked): Underway
    {
        $this->asked[] = $asked;

        return ($this->asking)();
    }

    public function whatBecameOf(Stack $stack, Session $session, Job $job): HowTheBundleIsGoing
    {
        $this->followed[] = $job;

        return ($this->becoming)();
    }

    public function fetch(Stack $stack, Session $session, AWrittenBundle $written): ABundleFetched
    {
        $this->fetched[] = $written;

        return ($this->serving)($written);
    }

    /** @return list<ABundleAsked> every bundle asked for, in order */
    public function asked(): array
    {
        return $this->asked;
    }

    /** @return list<Job> every handle a bundle was asked after by, in order */
    public function followed(): array
    {
        return $this->followed;
    }

    /** @return list<AWrittenBundle> every written bundle whose file was fetched, in order */
    public function fetched(): array
    {
        return $this->fetched;
    }

    /**
     * Serving the file of whichever bundle it wrote.
     *
     * @return Closure(AWrittenBundle): ABundleFetched
     */
    private static function servingItsFile(): Closure
    {
        return static fn(AWrittenBundle $written): ABundleFetched => ABundleFetched::as(ABundleFile::fetched($written, self::THE_BYTES));
    }
}
