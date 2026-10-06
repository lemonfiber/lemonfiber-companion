<?php

declare(strict_types=1);

namespace Modules\Design\View;

use Modules\Design\Api\ThemeToken;
use Modules\Kernel\Api\HowItStands;
use Modules\Kernel\Api\Severity;

/**
 * How a status reads, and the glyph that says so beside its words.
 *
 * A state is never carried by colour alone: each tone has a glyph of its own
 * as well as its colour, and the words beside it say the rest. The glyphs are
 * Material Symbols on Android and SF Symbols on iOS. The colour is a severity
 * role, which only the operator's theme paints as one.
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

    /** Nothing is wrong and nothing is there: a thing nobody asked for. */
    case Quiet = 'quiet';

    /** The tone a stack's standing is drawn in, wherever it is said in a word. */
    public static function ofAStanding(HowItStands $standing): self
    {
        return match ($standing) {
            HowItStands::Healthy => self::Fine,
            HowItStands::Advisory, HowItStands::Degraded, HowItStands::Stopped, HowItStands::Unconfigured => self::Attention,
            HowItStands::Broken, HowItStands::Critical => self::Trouble,
            HowItStands::Unknown => self::Unknown,
        };
    }

    /**
     * The tone a finding of one severity is drawn in.
     *
     * What {@see Severity::demandsAttention()} says the operator must be shown
     * now is drawn as broken, and the rest as wanting a look.
     */
    public static function ofASeverity(Severity $severity): self
    {
        return $severity->demandsAttention() ? self::Trouble : self::Attention;
    }

    /** The role its glyph is painted in. */
    public function colour(): ThemeToken
    {
        return match ($this) {
            self::Fine => ThemeToken::Ok,
            self::Attention => ThemeToken::Warn,
            self::Trouble => ThemeToken::Alarm,
            self::Working => ThemeToken::Activity,
            self::Unknown => ThemeToken::Muted,
            self::Quiet => ThemeToken::Faint,
        };
    }

    /** The role a notice in this tone is raised on. */
    public function ground(): ThemeToken
    {
        return match ($this) {
            self::Attention => ThemeToken::WarnTint,
            self::Trouble => ThemeToken::AlarmTint,
            self::Fine, self::Working, self::Unknown, self::Quiet => ThemeToken::Raised,
        };
    }

    /** The Material Symbol drawn on Android. */
    public function glyph(): string
    {
        return match ($this) {
            self::Fine => 'check_circle',
            self::Attention => 'warning',
            self::Trouble => 'error',
            self::Unknown => 'help',
            self::Working => 'schedule',
            self::Quiet => 'radio_button_unchecked',
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
            self::Quiet => 'circle',
        };
    }
}
