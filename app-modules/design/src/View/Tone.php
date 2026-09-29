<?php

declare(strict_types=1);

namespace Modules\Design\View;

/**
 * How a status reads, and the glyph that says so beside its words.
 *
 * A state is never carried by colour alone: each tone has its own
 * glyph, drawn in the text role, and the words beside it say the rest. The
 * glyphs are Material Symbols on Android and SF Symbols on iOS.
 */
enum Tone: string
{
    /** Everything this line is about is as it should be. */
    case Fine = 'fine';

    /** Something wants looking at, and nothing is lost yet. */
    case Attention = 'attention';

    /** Something is broken. */
    case Trouble = 'trouble';

    /** Nobody can say right now. */
    case Unknown = 'unknown';

    /** Something is under way. */
    case Working = 'working';

    /** The Material Symbol drawn on Android. */
    public function glyph(): string
    {
        return match ($this) {
            self::Fine => 'check_circle',
            self::Attention => 'warning',
            self::Trouble => 'error',
            self::Unknown => 'help',
            self::Working => 'schedule',
        };
    }

    /** The SF Symbol drawn on iOS. */
    public function iosGlyph(): string
    {
        return match ($this) {
            self::Fine => 'checkmark.circle.fill',
            self::Attention => 'exclamationmark.triangle.fill',
            self::Trouble => 'exclamationmark.octagon.fill',
            self::Unknown => 'questionmark.circle.fill',
            self::Working => 'clock.fill',
        };
    }
}
