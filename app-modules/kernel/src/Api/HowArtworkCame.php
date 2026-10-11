<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What came of asking the core for a picture: the picture, or the reason there is none.
 */
final readonly class HowArtworkCame
{
    private function __construct(private Artwork|Obstacle $answer) {}

    /** The core served the picture. */
    public static function arrived(Artwork $artwork): self
    {
        return new self($artwork);
    }

    /** It did not, and this is why. */
    public static function refused(Obstacle $why): self
    {
        return new self($why);
    }

    /**
     * Say what happens in both cases, and get back what you built.
     *
     * @template TArrived of object
     * @template TRefused of object
     *
     * @param  Closure(Artwork): TArrived  $arrived
     * @param  Closure(Obstacle): TRefused  $refused
     * @return TArrived|TRefused
     */
    public function either(Closure $arrived, Closure $refused): object
    {
        return $this->answer instanceof Artwork ? $arrived($this->answer) : $refused($this->answer);
    }
}
