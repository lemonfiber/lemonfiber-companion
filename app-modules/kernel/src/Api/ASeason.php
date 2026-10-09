<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/** One season of a series, with its episodes in order. */
final readonly class ASeason
{
    private function __construct(private string $named, private Episodes $episodes) {}

    public static function of(string $named, Episodes $episodes): self
    {
        return new self($named, $episodes);
    }

    /** What the media server calls it. */
    public function named(): string
    {
        return $this->named;
    }

    public function episodes(): Episodes
    {
        return $this->episodes;
    }
}
