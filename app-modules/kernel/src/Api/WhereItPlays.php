<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * Where one title or episode streams from at the household's door, or why the core says it cannot.
 *
 * Three answers. It streams: the location, and the fingerprint of the door's
 * certificate to pin it to. It cannot: the core's own words for why no
 * location is stated, which is said to the member as written. Or it is not a
 * thing that streams at all, as a series is, whose episodes stream instead.
 */
final readonly class WhereItPlays
{
    private function __construct(
        private ?Location $location,
        private ?Fingerprint $door,
        private ?Sentence $why,
    ) {}

    /** It streams from here, through a door presenting this certificate. */
    public static function at(Location $location, Fingerprint $door): self
    {
        return new self($location, $door, null);
    }

    /** The core states no location for it, and says why. */
    public static function cannot(Sentence $why): self
    {
        return new self(null, null, $why);
    }

    /** It is not something that streams, and the core says nothing of where. */
    public static function doesNotStream(): self
    {
        return new self(null, null, null);
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * @template TAt of object
     * @template TCannot of object
     * @template TDoesNot of object
     *
     * @param Closure(Location, Fingerprint): TAt $at
     * @param Closure(Sentence): TCannot          $cannot
     * @param Closure(): TDoesNot                 $doesNotStream
     *
     * @return TAt|TCannot|TDoesNot
     */
    public function either(Closure $at, Closure $cannot, Closure $doesNotStream): object
    {
        if ($this->location instanceof Location && $this->door instanceof Fingerprint) {
            return $at($this->location, $this->door);
        }

        return $this->why instanceof Sentence ? $cannot($this->why) : $doesNotStream();
    }
}
