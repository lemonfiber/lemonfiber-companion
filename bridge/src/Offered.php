<?php

declare(strict_types=1);

namespace Lemonfiber\Native;

use Closure;

/**
 * What became of asking for the share sheet.
 *
 * **Two arms, and the missing third is the point of the type.** A handover ends
 * with the operator choosing an app, with them dismissing the sheet, or with the
 * platform never presenting it. The first two are one answer here, deliberately:
 * where the report went is none of this application's business, and an app that
 * watched where it went would not be honouring *assembled for the operator to
 * send, not sent*.
 *
 * So there is no arm for *they chose mail* and no arm for *they changed their
 * mind*. What a caller can learn is whether the sheet was reached, which is the
 * whole of what this can honestly claim.
 */
final readonly class Offered
{
    private function __construct(private ?WhyNothingWasHandedOver $why) {}

    /** The sheet was put in front of them. What they did with it is theirs. */
    public static function toThem(): self
    {
        return new self(why: null);
    }

    /** It was not, and this is which of the two ways. */
    public static function refused(WhyNothingWasHandedOver $why): self
    {
        return new self(why: $why);
    }

    /**
     * Say what happens either way, and get back what you built.
     *
     * @template TOffered of object
     * @template TRefused of object
     *
     * @param  Closure(): TOffered  $offered
     * @param  Closure(WhyNothingWasHandedOver): TRefused  $refused
     * @return TOffered|TRefused
     */
    public function either(Closure $offered, Closure $refused): object
    {
        if ($this->why instanceof WhyNothingWasHandedOver) {
            return $refused($this->why);
        }

        return $offered();
    }
}
