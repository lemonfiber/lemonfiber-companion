<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

use function intdiv;
use function sprintf;

/**
 * What a seeding download has given back against what it took.
 *
 * Carried in hundredths, as the client reports it. **No ratio is a case of its
 * own**: a torrent added from files already on disk downloaded nothing, so
 * what it has given back is not divisible by what it took.
 */
final readonly class ARatio
{
    /** How many hundredths make one, which is the unit the client reports in. */
    private const int HUNDREDTHS = 100;

    private function __construct(private ?int $hundredths) {}

    /** A ratio, in hundredths, refused below nothing. */
    public static function inHundredths(int $hundredths): self
    {
        if ($hundredths < 0) {
            throw RoomSaysNothing::negative('ratio', $hundredths);
        }

        return new self($hundredths);
    }

    /** No ratio: nothing was downloaded to divide by. */
    public static function none(): self
    {
        return new self(null);
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * The ratio arrives as a person reads it, `1.25` for 125 hundredths.
     *
     * @template TRead of object
     * @template TNone of object
     *
     * @param Closure(string): TRead $read
     * @param Closure(): TNone       $none
     *
     * @return TRead|TNone
     */
    public function either(Closure $read, Closure $none): object
    {
        return $this->hundredths === null
            ? $none()
            : $read(sprintf('%d.%02d', intdiv($this->hundredths, self::HUNDREDTHS), $this->hundredths % self::HUNDREDTHS));
    }
}
