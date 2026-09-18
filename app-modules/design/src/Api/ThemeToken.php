<?php

declare(strict_types=1);

namespace Modules\Design\Api;

/**
 * Every theme token this surface answers for, and there are two.
 *
 * EDGE resolves `bg-theme-*`, `text-theme-*` and `border-theme-*` through a
 * resolver the application provides; the package ships none, so until one is
 * registered every such class is parsed, found to mean nothing, and dropped.
 * The screen renders, looks wrong, and says nothing about why.
 *
 * What it answers for is deliberately almost nothing, and the narrowness is the
 * point rather than a gap to be filled in later. `60-brand/surface-mapping.md`
 * decides it: this app's components are the platform's own, which is what buys
 * the accessibility tree, the reader's text size, the system's contrast and
 * reduced-motion settings and its light and dark modes without any of them
 * being built a second time. Overriding that look to reach the web palette
 * would spend exactly what it was chosen for (DES-R24, DES-R26, ADR-0017).
 *
 *   | Brand token   | Companion mapping                                        |
 *   |---------------|----------------------------------------------------------|
 *   | `lemon`       | the accent role — the one place brand colour is asserted  |
 *   | `ink`/`paper` | left to the platform's theme roles                       |
 *   | everything    | not mapped; the platform's spacing, radii, type, elevation |
 *
 * So a token that is not a case here resolves to nothing, `tests/Templates`
 * reports the class as one EDGE drops, and the build fails — which is the
 * correct answer to asking this surface to paint something the platform should
 * own. An enum rather than two string constants because the set really is
 * closed, and a closed set is a type (D4).
 */
enum ThemeToken: string
{
    /** The brand's `lemon`, and the only colour this surface asserts. */
    case Accent = 'accent';

    /** The brand's `ink`, which is the foreground `Accent` is filled behind. */
    case OnAccent = 'on-accent';

    /**
     * The hex this token paints, in light mode and in dark alike.
     *
     * Written here rather than read from `resources/tokens.json` because B3
     * keeps the filesystem in an adapter and A9 keeps a read out of boot — and
     * copied rather than derived, which is a second source of truth and so is
     * checked against the first: `ThemeTokenTest` fails the moment either of
     * these stops matching the brand's own token file. That is the same
     * arrangement `brand:scripts/check_tokens.py` already makes for
     * `tokens.css`, which is hand-maintained and checked for the same reason.
     *
     * There is no dark companion, and that is a decision rather than an
     * omission. `TailwindParser` emits one only when a dark resolver is
     * registered, and registering one would mean naming a dark-mode lemon the
     * brand has not chosen — `lemon-bright` is documented as the step above
     * lemon for hover and lift, not as a dark-mode value. The pair below needs
     * none: ink on lemon measures 10.9:1 whichever way the reader has their
     * phone set, so the accent carries its own legibility instead of borrowing
     * the ground's.
     */
    public function hex(): string
    {
        return match ($this) {
            // brand `lemon`
            self::Accent => '#F0C419',
            // brand `ink`
            self::OnAccent => '#17160F',
        };
    }

    /**
     * Whether this token may appear in a `text-theme-*` class.
     *
     * `lemon` on `paper` measures 1.6:1. It is an accent — a fill, a bar, a
     * selected state — and never text, for the same measured reason amber is
     * never text. The resolver cannot tell which prefix
     * asked it, so this is where the distinction is kept and
     * `tests/Templates` is what enforces it.
     *
     * `on-accent` is the other half of that pair and exists to be text: it is
     * what a label on an accent fill is set in. It is not a general foreground
     * — ink on the platform's own dark surface would be unreadable — so it
     * belongs on an element filled with `bg-theme-accent` and nowhere else.
     * No rule can check that from a class string alone, because the fill is
     * often on the parent; it is a review note, stated here so it is at least
     * stated.
     */
    public function safeAsText(): bool
    {
        return match ($this) {
            self::Accent => false,
            self::OnAccent => true,
        };
    }
}
