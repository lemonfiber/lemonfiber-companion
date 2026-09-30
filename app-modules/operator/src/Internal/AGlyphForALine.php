<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Design\View\Tone;
use Modules\Kernel\Api\HowSeriousALineIs;

/**
 * The glyph a log line earns by the severity it declared, or none.
 *
 * An error — and a fatal one — is trouble, a warning is attention, and every
 * other severity is drawn without a glyph. The word is what the glyph is read
 * aloud as.
 */
final readonly class AGlyphForALine
{
    private function __construct(
        public string $tone,
        public string $said,
        public bool $isAnError,
    ) {}

    /** The glyph that severity earns, if any. */
    public static function for(HowSeriousALineIs $level): self
    {
        if ($level->isAnError()) {
            return new self(Tone::Trouble->value, $level->saidOnTheScreen(), isAnError: true);
        }

        if ($level->isAWarning()) {
            return new self(Tone::Attention->value, $level->saidOnTheScreen(), isAnError: false);
        }

        return self::none();
    }

    /** No glyph: the line is ordinary, or nobody classified it. */
    public static function none(): self
    {
        return new self('', '', isAnError: false);
    }
}
