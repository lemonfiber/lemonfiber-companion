<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Closure;
use Modules\Kernel\Api\AResetAgreed;
use Modules\Kernel\Api\HowTheResetIsGoing;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ResettingTheConfiguration;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Underway;

/**
 * A stack that previews putting its configuration back, and puts it back,
 * without a file existing.
 *
 * The preview and the yes are each answered with a handle of their own, and
 * asking after one is answered with what the case gave for it, so a test can
 * say which reading a screen followed. The counters say how often a preview
 * was asked for, which yeses were sent and which handles were followed.
 *
 * Not `readonly`: what was asked is written when the asking happens.
 */
final class AStackThatResets implements ResettingTheConfiguration
{
    public const string THE_PREVIEW = 'a-preview-a-test-can-name';

    public const string THE_RESET = 'a-reset-a-test-can-name';

    private int $previews = 0;

    /** @var list<AResetAgreed> */
    private array $agreed = [];

    /** @var list<Job> */
    private array $followed = [];

    /**
     * @param Closure(): Underway $previewing
     * @param Closure(): Underway $reverting
     */
    private function __construct(
        private readonly Closure $previewing,
        private readonly HowTheResetIsGoing $preview,
        private readonly Closure $reverting,
        private readonly HowTheResetIsGoing $after,
    ) {}

    /** A stack that takes a preview on and answers it with `$preview`, and takes a yes on and answers it with `$after`. */
    public static function previewing(HowTheResetIsGoing $preview, HowTheResetIsGoing $after): self
    {
        return new self(
            static fn(): Underway => Underway::as(Job::named(self::THE_PREVIEW)),
            $preview,
            static fn(): Underway => Underway::as(Job::named(self::THE_RESET)),
            $after,
        );
    }

    /**
     * A stack that previews and refuses the yes.
     *
     * The shape a test needs to reach a refused yes at all: a stack refusing
     * the preview too never hands over anything to agree to.
     */
    public static function previewingButRefusing(HowTheResetIsGoing $preview, Obstacle $why): self
    {
        return new self(
            static fn(): Underway => Underway::as(Job::named(self::THE_PREVIEW)),
            $preview,
            static fn(): Underway => Underway::met($why),
            HowTheResetIsGoing::met($why),
        );
    }

    /** A stack that meets every question with the same obstacle. */
    public static function met(Obstacle $why): self
    {
        return new self(
            static fn(): Underway => Underway::met($why),
            HowTheResetIsGoing::met($why),
            static fn(): Underway => Underway::met($why),
            HowTheResetIsGoing::met($why),
        );
    }

    public function wouldRevert(Stack $stack, Session $session): Underway
    {
        $this->previews++;

        return ($this->previewing)();
    }

    public function revert(Stack $stack, Session $session, AResetAgreed $agreed): Underway
    {
        $this->agreed[] = $agreed;

        return ($this->reverting)();
    }

    public function whatBecameOf(Stack $stack, Session $session, Job $job): HowTheResetIsGoing
    {
        $this->followed[] = $job;

        return $job->shown() === self::THE_RESET ? $this->after : $this->preview;
    }

    /** How many times a preview was asked for. */
    public function previewsAskedFor(): int
    {
        return $this->previews;
    }

    /** @return list<AResetAgreed> every yes sent, in order */
    public function agreed(): array
    {
        return $this->agreed;
    }

    /** @return list<string> every handle asked after, in order */
    public function followed(): array
    {
        $shown = [];

        foreach ($this->followed as $job) {
            $shown[] = $job->shown();
        }

        return $shown;
    }
}
