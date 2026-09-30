<?php

declare(strict_types=1);

namespace Modules\Health\Internal;

use function max;

use Modules\Kernel\Api\HowLong;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\SecondsIn;

/**
 * How long the phone keeps a stack's last reading before it lets it go.
 *
 * {@see standard()} is thirty days, and it is a default rather than a fact: the
 * operator's own choice of how long replaces it when there is a setting to
 * make it in, and that choice arrives as another one of these.
 */
final readonly class HowLongAReadingIsKept
{
    /** Thirty days, in the seconds an {@see Instant} counts. */
    private const int THIRTY_DAYS = 30 * SecondsIn::ADay->value;

    private function __construct(private HowLong $kept) {}

    /** How long a reading is kept until the operator says otherwise. */
    public static function standard(): self
    {
        return new self(HowLong::ofSeconds(self::THIRTY_DAYS));
    }

    /**
     * The moment a reading must have been read at or after to be kept, as of now.
     *
     * Never before the epoch: a clock reading less than the length kept keeps
     * everything, rather than asking about a moment no reading can have.
     */
    public function keepsWhatWasReadSince(Instant $now): Instant
    {
        return Instant::atEpochSeconds(max(0, $now->epochSeconds() - $this->kept->inSeconds()));
    }
}
