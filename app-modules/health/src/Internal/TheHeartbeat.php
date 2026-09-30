<?php

declare(strict_types=1);

namespace Modules\Health\Internal;

use Modules\Kernel\Api\HowLong;

/**
 * How often the core breaks silence on a stream, and how long a stream may be
 * silent before it is broken rather than quiet.
 *
 * The contract has the core speak at least every fifteen seconds, heartbeat or
 * not, and a client call twice that in silence a broken stream. Both are facts
 * of the contract rather than choices of this app, so there is nothing here
 * for anyone to tune: they are lengths of time, declared once.
 */
final readonly class TheHeartbeat
{
    /** The longest the core goes without a word, in the seconds a {@see HowLong} counts. */
    private const int EVERY = 15;

    /** How many heartbeats may go missing before a stream is broken rather than quiet. */
    private const int MAY_GO_MISSING = 2;

    /** How often the core breaks silence at the least. */
    public static function interval(): HowLong
    {
        return HowLong::ofSeconds(self::EVERY);
    }

    /** The longest a stream may be silent and what it carried still be current. */
    public static function silenceAllowed(): HowLong
    {
        return HowLong::ofSeconds(self::interval()->inSeconds() * self::MAY_GO_MISSING);
    }
}
