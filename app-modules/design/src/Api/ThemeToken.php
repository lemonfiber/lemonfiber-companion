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
        };
    }

    /**
     * Whether this role may appear in a `text-theme-*` class.
     *
     * `lemon` is a fill rather than a foreground, and a surface or a line is a
     * ground. `on-accent` is text only on an accent fill; no rule can see the
     * fill from a class string, because it is often on the parent.
     */
    public function safeAsText(): bool
    {
        return match ($this) {
            self::Text, self::Muted, self::Faint, self::OnAccent => true,
            self::Accent, self::Surface, self::Raised, self::Line => false,
        };
    }
}
