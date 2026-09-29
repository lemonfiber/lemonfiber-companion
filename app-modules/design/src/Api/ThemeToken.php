<?php

declare(strict_types=1);

namespace Modules\Design\Api;

/**
 * Every colour role this surface asserts, each with a light and a dark value.
 *
 * EDGE resolves `bg-theme-*`, `text-theme-*` and `border-theme-*` through the
 * resolvers {@see Theme} builds from these cases. A token that is not a case
 * resolves to nothing, and `tests/Templates` reports the class as one EDGE
 * drops.
 *
 * `60-brand/surface-mapping.md` decides the set: `lemon` as the accent with
 * `ink` on it, and the brand's paper, pith, ink, line and muted text as the
 * surface, raised-surface, text, line and muted-text roles, with the ink
 * theme's values in dark mode. The renderer paints unstyled text black in both
 * modes, so a role the app leaves to it is a role nobody chose.
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

    /** A hairline between rows, and the edge of a raised surface. */
    case Line = 'line';

    /** The hex this role paints in light mode, copied from the brand's paper theme. */
    public function light(): string
    {
        return match ($this) {
            self::Accent => '#F0C419',
            self::OnAccent, self::Text => '#17160F',
            self::Surface => '#FBF7EA',
            self::Raised => '#FBF6E7',
            self::Muted => '#565344',
            self::Line => '#DAD2BC',
        };
    }

    /** The hex this role paints in dark mode, copied from the brand's ink theme. */
    public function dark(): string
    {
        return match ($this) {
            self::Accent => '#F0C419',
            self::OnAccent, self::Surface => '#17160F',
            self::Raised => '#241F14',
            self::Text => '#FBF7EA',
            self::Muted => '#ACAA9F',
            self::Line => '#34322A',
        };
    }

    /**
     * Whether this role may appear in a `text-theme-*` class.
     *
     * `lemon` on paper measures 1.55:1, and a surface or a line is a ground,
     * not a foreground. `on-accent` is text only on an accent fill; no rule can
     * see the fill from a class string, because it is often on the parent.
     */
    public function safeAsText(): bool
    {
        return match ($this) {
            self::Text, self::Muted, self::OnAccent => true,
            self::Accent, self::Surface, self::Raised, self::Line => false,
        };
    }
}
