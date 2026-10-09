<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * One title on a member's shelf, in full, as the member's own session reads it.
 *
 * What it is about, how long it runs, its genres, its certificate, when it
 * came out and a series' seasons and episodes, each with where it streams
 * from. Every part is the core's answer: a part the core does not state is
 * one this type says is unstated rather than one a screen fills in.
 */
final readonly class ATitle
{
    private function __construct(
        private Holding $holding,
        private string $about,
        private HowLongItRuns $runs,
        private Genres $genres,
        private string $certificate,
        private WhenItWasReleased $released,
        private WhereItPlays $plays,
        private Seasons $seasons,
    ) {}

    /**
     * @param string $about       what it is about, or empty where the server holds no description
     * @param string $certificate the certificate it carries, or empty where it carries none
     */
    public static function of(
        Holding $holding,
        string $about,
        HowLongItRuns $runs,
        Genres $genres,
        string $certificate,
        WhenItWasReleased $released,
        WhereItPlays $plays,
        Seasons $seasons,
    ): self {
        return new self($holding, $about, $runs, $genres, $certificate, $released, $plays, $seasons);
    }

    /** Its id, its name, its kind and its year, as the shelf lists it. */
    public function holding(): Holding
    {
        return $this->holding;
    }

    /** What it is about, or empty where the server holds no description. */
    public function about(): string
    {
        return $this->about;
    }

    public function runs(): HowLongItRuns
    {
        return $this->runs;
    }

    public function genres(): Genres
    {
        return $this->genres;
    }

    /** The certificate it carries where the operator lives, or empty where it carries none. */
    public function certificate(): string
    {
        return $this->certificate;
    }

    public function released(): WhenItWasReleased
    {
        return $this->released;
    }

    public function plays(): WhereItPlays
    {
        return $this->plays;
    }

    /** A series' seasons in order; none for anything else. */
    public function seasons(): Seasons
    {
        return $this->seasons;
    }
}
