<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;
use function is_array;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\CopyingEnvelope;
use Modules\Kernel\Api\ACopyRunning;
use Modules\Kernel\Api\HowTheCopyMoves;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\WhatIsBeingCopied;
use Modules\Kernel\Api\WhatIsCopied;
use Modules\Sdk\Api\Fields\CopyingField;
use Modules\Sdk\Internal\APath;
use Modules\Sdk\Internal\HowACopyIsGoing;
use Modules\Sdk\Internal\Required;
use Modules\Sdk\Internal\Wire;

/**
 * Reads a `copying` envelope: the backup or restore running now, or that none is.
 *
 * Every field the screen draws is required, and a `state` this app has no word
 * for is refused rather than drawn as working: the stack's reading is the only
 * one shown, and a guess at it would be this side's reckoning.
 */
final readonly class WhatTheCopyingSaid
{
    /**
     * What the envelope says is being copied.
     *
     * @param Envelope<mixed> $envelope
     */
    public static function in(Envelope $envelope): WhatIsBeingCopied
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw CopyingIsUnreadable::missing(WireField::Data->value);
        }

        if (! array_key_exists(WireField::Running->value, $data) || $data[WireField::Running->value] === null) {
            return WhatIsBeingCopied::nothing();
        }

        $running = $data[WireField::Running->value];

        return is_array($running) ? WhatIsBeingCopied::running(self::running($running)) : throw CopyingIsUnreadable::missing(WireField::Running->value);
    }

    /** @param Envelope<mixed> $envelope */
    private static function payload(Envelope $envelope): mixed
    {
        return CopyingEnvelope::in($envelope)->data;
    }

    /** @param array<mixed> $running */
    private static function running(array $running): ACopyRunning
    {
        return new ACopyRunning(
            what: WhatIsCopied::tryFrom(Required::text($running, WireField::What, self::missing(WireField::What)))
                ?? throw self::missing(WireField::What),
            started: Instant::atEpochSeconds(Required::number($running, WireField::Started, self::missing(WireField::Started))),
            written: Required::count($running, WireField::Written, self::missing(WireField::Written)),
            whole: Required::count($running, CopyingField::Whole, self::missing(CopyingField::Whole)),
            part: Required::text($running, WireField::Part, self::missing(WireField::Part)),
            moves: self::moves($running),
        );
    }

    /** @param array<mixed> $running */
    private static function moves(array $running): HowTheCopyMoves
    {
        $state = HowACopyIsGoing::tryFrom(Required::text($running, WireField::State, self::missing(WireField::State)))
            ?? throw self::missing(WireField::State);

        return match ($state) {
            HowACopyIsGoing::Working => HowTheCopyMoves::working(),
            HowACopyIsGoing::Paced => HowTheCopyMoves::paced(Required::count($running, WireField::Limit, self::missing(WireField::Limit))),
            HowACopyIsGoing::Stalled => HowTheCopyMoves::stalled(Instant::atEpochSeconds(Required::number($running, WireField::Wrote, self::missing(WireField::Wrote)))),
        };
    }

    private static function missing(NamesAWireField $field): CopyingIsUnreadable
    {
        return CopyingIsUnreadable::missing(APath::beneath(WireField::Running->value, $field->value));
    }
}
