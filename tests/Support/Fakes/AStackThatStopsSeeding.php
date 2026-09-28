<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Closure;
use Modules\Kernel\Api\ADownloadHeld;
use Modules\Kernel\Api\HowLettingItGoIsGoing;
use Modules\Kernel\Api\HowTheOfferToLetGoIsGoing;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StoppingSeeding;
use Modules\Kernel\Api\Underway;
use Modules\Kernel\Api\WhatLettingItGoCosts;

/**
 * A stack that says what stopping seeding would cost, and stops without a client to ask.
 *
 * The counters are what let a test say which download an offer was asked for,
 * which offer a yes was sent against and which handles were followed.
 *
 * Not `readonly`: what was asked is written when the asking happens.
 */
final class AStackThatStopsSeeding implements StoppingSeeding
{
    /** The handle asking what it would cost is answered with. */
    public const string THE_OFFER = 'an-offer-a-test-can-name';

    /** The handle the yes is answered with. */
    public const string THE_JOB = 'a-release-a-test-can-name';

    /** @var list<ADownloadHeld> */
    private array $asked = [];

    /** @var list<Job> */
    private array $read = [];

    /** @var list<WhatLettingItGoCosts> */
    private array $agreed = [];

    /** @var list<Job> */
    private array $followed = [];

    /**
     * @param Closure(): Underway                  $asking
     * @param Closure(): HowTheOfferToLetGoIsGoing $offering
     * @param Closure(): Underway                  $stopping
     * @param Closure(): HowLettingItGoIsGoing     $becoming
     */
    private function __construct(
        private readonly Closure $asking,
        private readonly Closure $offering,
        private readonly Closure $stopping,
        private readonly Closure $becoming,
    ) {}

    /** A stack whose offer comes to `$offer`, that takes a yes on and, asked after it, says `$became`. */
    public static function offering(HowTheOfferToLetGoIsGoing $offer, HowLettingItGoIsGoing $became): self
    {
        return new self(
            static fn(): Underway => Underway::as(Job::named(self::THE_OFFER)),
            static fn(): HowTheOfferToLetGoIsGoing => $offer,
            static fn(): Underway => Underway::as(Job::named(self::THE_JOB)),
            static fn(): HowLettingItGoIsGoing => $became,
        );
    }

    /**
     * A stack that offers and refuses the yes.
     *
     * The shape a test needs to reach a refused yes at all: a stack refusing
     * the question too never hands over anything to agree to.
     */
    public static function offeringButRefusing(WhatLettingItGoCosts $offer, Obstacle $why): self
    {
        return new self(
            static fn(): Underway => Underway::as(Job::named(self::THE_OFFER)),
            static fn(): HowTheOfferToLetGoIsGoing => HowTheOfferToLetGoIsGoing::offering($offer),
            static fn(): Underway => Underway::met($why),
            static fn(): HowLettingItGoIsGoing => HowLettingItGoIsGoing::met($why),
        );
    }

    /** A stack that meets every question with the same obstacle. */
    public static function met(Obstacle $why): self
    {
        return new self(
            static fn(): Underway => Underway::met($why),
            static fn(): HowTheOfferToLetGoIsGoing => HowTheOfferToLetGoIsGoing::met($why),
            static fn(): Underway => Underway::met($why),
            static fn(): HowLettingItGoIsGoing => HowLettingItGoIsGoing::met($why),
        );
    }

    public function whatItWouldCost(Stack $stack, Session $session, ADownloadHeld $download): Underway
    {
        $this->asked[] = $download;

        return ($this->asking)();
    }

    public function whatTheOfferCameTo(Stack $stack, Session $session, Job $job): HowTheOfferToLetGoIsGoing
    {
        $this->read[] = $job;

        return ($this->offering)();
    }

    public function stop(Stack $stack, Session $session, WhatLettingItGoCosts $offer): Underway
    {
        $this->agreed[] = $offer;

        return ($this->stopping)();
    }

    public function whatBecameOf(Stack $stack, Session $session, Job $job): HowLettingItGoIsGoing
    {
        $this->followed[] = $job;

        return ($this->becoming)();
    }

    /** @return list<ADownloadHeld> every download an offer was asked for, in order */
    public function asked(): array
    {
        return $this->asked;
    }

    /** @return list<Job> every handle an offer was read by, in order */
    public function read(): array
    {
        return $this->read;
    }

    /** @return list<WhatLettingItGoCosts> every offer a yes was sent against, in order */
    public function agreed(): array
    {
        return $this->agreed;
    }

    /** @return list<Job> every handle a yes was asked after by, in order */
    public function followed(): array
    {
        return $this->followed;
    }
}
