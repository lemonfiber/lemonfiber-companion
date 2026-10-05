<?php

declare(strict_types=1);

namespace Modules\Design\Api;

/**
 * Every colour role this surface asserts, with the hex it paints in each theme.
 *
 * EDGE resolves `bg-theme-*`, `text-theme-*` and `border-theme-*` through the
 * resolver {@see Theme} builds from these cases for the theme in force. A token
 * that is not a case resolves to nothing, and `tests/Templates` reports the
 * class as one EDGE drops.
 *
 * `60-brand/surface-mapping.md` decides the values, from the brand's ink theme
 * in both: `lemon` as the accent with `ink` on it, `ink-soft` raised off the
 * ground, `paper` and `text-muted` as text and `line` as the hairline. The
 * member's ground is the ink theme's `canvas` and the operator's is `ink`, and
 * only the operator's theme sets text in `text-faint`. There is one value per
 * theme rather than a light and a dark one, because neither theme follows the
 * phone's setting.
 *
 * How a thing stands is painted from the brand's severity colours in the
 * operator's theme alone: `ok`, `alarm`, `fiber` as warning and as activity,
 * and the tints `warn-tint` and `alarm-tint` behind a notice. The member's
 * theme draws no severity colour, so there those roles paint as text and as a
 * raised surface do. Every severity also has a glyph of its own
 * ({@see \Modules\Design\View\Tone}), so colour is never the only thing
 * that says it. In the operator's theme lemon also sets the words of the
 * operator's own quieter actions; the member's theme sets lemon as nothing but
 * the one primary action's fill.
 */
enum ThemeToken: string
{
    /** The brand's `lemon`: a fill, a bar, a selected state; never text. */
    case Accent = 'accent';

    /** The brand's `ink`, set on an `Accent` fill and on nothing else. */
    case OnAccent = 'on-accent';

    /** The ground every screen is drawn on. */
    case Surface = 'surface';

    /** A card, a row or a notice raised off the ground. */
    case Raised = 'raised';

    /** Everything read as text. */
    case Text = 'text';

    /** Text that says less: an age, a hint, a label beside a value. */
    case Muted = 'muted';

    /**
     * Text that says least, in the operator's theme: a unit, a stamp beside a figure.
     *
     * The member's theme sets text in `paper` and `text-muted` alone, so there
     * this role paints as `Muted` does.
     */
    case Faint = 'faint';

    /** A hairline between rows, and the edge of a raised surface. */
    case Line = 'line';

    /** The glyph of something that is as it should be: the brand's `ok`. */
    case Ok = 'ok';

    /** The glyph of something that wants looking at: the brand's `fiber`, serving as warning. */
    case Warn = 'warn';

    /** The glyph of something broken: the brand's `alarm`. */
    case Alarm = 'alarm';

    /** The glyph of something under way: the brand's `fiber`, as activity. */
    case Activity = 'activity';

    /** The ground of a notice about something that wants looking at: the brand's `warn-tint`. */
    case WarnTint = 'warn-tint';

    /** The ground of a notice about something broken: the brand's `alarm-tint`. */
    case AlarmTint = 'alarm-tint';

    /** The brand's `ink`, the glyph on an `Alarm` fill and nothing else. */
    case OnAlarm = 'on-alarm';

    /**
     * The words of an action the operator takes that is not the way forward.
     *
     * `lemon` in the operator's theme, which is lemonfiber's colour for the
     * operator's own actions and measures past AA on ink. In the member's
     * theme lemon is the fill of the one primary action and nothing else, so
     * there these words paint as `Muted` does.
     */
    case OwnAction = 'own-action';

    /** The brand's `lemon`. */
    private const string LEMON = '#F0C419';

    /** The brand's `ink`. */
    private const string INK = '#17160F';

    /** The ink theme's `paper`, its ground. */
    private const string INK_PAPER = '#17160F';

    /** The ink theme's `canvas`, darker than its ground. */
    private const string INK_CANVAS = '#100F0A';

    /** The ink theme's `pith`, the brand's `ink-soft`. */
    private const string INK_PITH = '#241F14';

    /** The ink theme's `text`. */
    private const string INK_TEXT = '#FBF7EA';

    /** The ink theme's `text-muted`. */
    private const string INK_TEXT_MUTED = '#ACAA9F';

    /** The ink theme's `text-faint`. */
    private const string INK_TEXT_FAINT = '#8D8972';

    /** The ink theme's `line`. */
    private const string INK_LINE = '#34322A';

    /** The ink theme's `ok`. */
    private const string INK_OK = '#9DB856';

    /** The ink theme's `alarm`. */
    private const string INK_ALARM = '#E8705C';

    /** The ink theme's `fiber`. */
    private const string INK_FIBER = '#F09A3C';

    /** The ink theme's `warn-tint`. */
    private const string INK_WARN_TINT = '#35240F';

    /** The ink theme's `alarm-tint`. */
    private const string INK_ALARM_TINT = '#3A1C16';

    /** The hex this role paints in one theme, copied from the brand's ink theme. */
    public function in(WhoseTheme $theme): string
    {
        return match ($this) {
            self::Accent => self::LEMON,
            self::OnAccent => self::INK,
            self::Surface => $theme === WhoseTheme::Operator ? self::INK_PAPER : self::INK_CANVAS,
            self::Raised => self::INK_PITH,
            self::Text => self::INK_TEXT,
            self::Muted => self::INK_TEXT_MUTED,
            self::Faint => $theme === WhoseTheme::Operator ? self::INK_TEXT_FAINT : self::INK_TEXT_MUTED,
            self::Line => self::INK_LINE,
            self::Ok => $theme === WhoseTheme::Operator ? self::INK_OK : self::INK_TEXT,
            self::Warn, self::Activity => $theme === WhoseTheme::Operator ? self::INK_FIBER : self::INK_TEXT,
            self::Alarm => $theme === WhoseTheme::Operator ? self::INK_ALARM : self::INK_TEXT,
            self::WarnTint => $theme === WhoseTheme::Operator ? self::INK_WARN_TINT : self::INK_PITH,
            self::AlarmTint => $theme === WhoseTheme::Operator ? self::INK_ALARM_TINT : self::INK_PITH,
            self::OnAlarm => self::INK,
            self::OwnAction => $theme === WhoseTheme::Operator ? self::LEMON : self::INK_TEXT_MUTED,
        };
    }

    /**
     * Whether this role may appear in a `text-theme-*` class.
     *
     * `lemon` is a fill rather than a foreground, and a surface or a line is a
     * ground. `on-accent` is text only on an accent fill; no rule can see the
     * fill from a class string, because it is often on the parent. A severity
     * colours a glyph beside the words that say the state, and never the words,
     * and `on-alarm` is a glyph on an alarm fill. `own-action` is the one role
     * that paints lemon as words, and only in the operator's theme.
     */
    public function safeAsText(): bool
    {
        return match ($this) {
            self::Text, self::Muted, self::Faint, self::OnAccent, self::OwnAction => true,
            self::Accent, self::Surface, self::Raised, self::Line,
            self::Ok, self::Warn, self::Alarm, self::Activity, self::WarnTint, self::AlarmTint, self::OnAlarm => false,
        };
    }
}
