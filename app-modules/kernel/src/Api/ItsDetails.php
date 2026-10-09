<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What a title is, beyond its name: what it is about, how long it runs, its
 * genres, its certificate and when it came out, each as the core answered it.
 */
final readonly class ItsDetails
{
    private function __construct(
        private string $about,
        private HowLongItRuns $runs,
        private Genres $genres,
        private string $certificate,
        private WhenItWasReleased $released,
    ) {}

    /**
     * @param string $about       what it is about, or empty where the server holds no description
     * @param string $certificate the certificate it carries, or empty where it carries none
     */
    public static function of(string $about, HowLongItRuns $runs, Genres $genres, string $certificate, WhenItWasReleased $released): self
    {
        return new self($about, $runs, $genres, $certificate, $released);
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
}
