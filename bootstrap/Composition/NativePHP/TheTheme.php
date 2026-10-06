<?php

declare(strict_types=1);

namespace Bootstrap\Composition\NativePHP;

use function array_map;

use Closure;

use function is_string;

use Modules\Design\Api\DrawnAsAMemberSeesIt;
use Modules\Design\Api\Radius;
use Modules\Design\Api\TakesTheThemeItOpensOver;
use Modules\Design\Api\Theme;
use Modules\Design\Api\ThemeToken;
use Modules\Design\Api\Typeface;
use Modules\Design\Api\TypeSize;
use Modules\Design\Api\WhichThemeIsOnTheGlass;
use Modules\Design\Api\WhoseTheme;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackIsUnidentified;
use Modules\Wayfinding\Api\WhoTheMenuIsFor;
use Native\Mobile\Edge\NativeComponent;
use Native\Mobile\Edge\TailwindParser;
use Native\Mobile\UI\Theme as WhatTheWidgetsPaintWith;

/**
 * Which of the two themes the classes and the widgets paint with, for the screen on view.
 *
 * Whose session a screen is drawn for decides it, as it decides the
 * application: a screen about a stack is drawn in the operator's theme where
 * this phone holds the operator's session for that stack, and in the member's
 * otherwise. A screen about no stack is drawn in the member's, unless it
 * {@see TakesTheThemeItOpensOver}. A screen {@see DrawnAsAMemberSeesIt} is
 * drawn in the member's whoever opened it. No setting chooses between them.
 *
 * `TailwindParser` holds one light and one dark resolver and keeps whichever
 * was set last, and `nativephp/mobile-ui` sets its own in its boot. So the
 * composition root paints the member's theme from `$this->app->booted()`,
 * after every provider, and the navigation stack hands each screen that comes
 * to the front to {@see self::forTheScreen()}.
 *
 * One instance for the application, holding the theme last painted: the
 * runtime is persistent, and pushing the widgets' colours across the bridge
 * for a screen in the theme already on the glass would be a round trip that
 * changes nothing.
 */
final class TheTheme implements WhichThemeIsOnTheGlass
{
    /**
     * What each key of mobile-ui's theme store is painted with.
     *
     * The widgets (buttons, list rows, fields, the top bar and the bottom bar)
     * read their colours from that store rather than from classes. Every
     * neutral key is mapped, so no widget falls back to the package's own
     * palette. `secondary` is the tonal button's fill, so it is the line role
     * with the text role on it: a quiet button beside the accent's filled one.
     * Material's own `secondary`, which a selected tab's label is drawn in, is
     * the text role instead, and a selected tab's indicator is the outline
     * (both in `scripts/patch_nativephp.php`). `destructive`, `success` and
     * the package's `accent` are not mapped: this surface asserts no colour
     * for them, and uses no widget variant that would.
     */
    public const array WIDGET_ROLES = [
        'primary' => ThemeToken::Accent,
        'on-primary' => ThemeToken::OnAccent,
        'secondary' => ThemeToken::Line,
        'on-secondary' => ThemeToken::Text,
        'background' => ThemeToken::Surface,
        'on-background' => ThemeToken::Text,
        'surface' => ThemeToken::Surface,
        'on-surface' => ThemeToken::Text,
        'surface-variant' => ThemeToken::Raised,
        'on-surface-variant' => ThemeToken::Muted,
        'outline' => ThemeToken::Line,
        'outline-variant' => ThemeToken::Line,
    ];

    /**
     * What each of mobile-ui's corner radii is drawn with.
     *
     * The package names four radii and the brand allows two inside the app,
     * so the package's large radius is the brand's `md` as well: the corner a
     * field, a sheet and a button group are drawn with. Its full radius is the
     * brand's pill, which the chip takes (`scripts/patch_nativephp.php`).
     */
    public const array WIDGET_RADII = [
        'radius-sm' => Radius::Small,
        'radius-md' => Radius::Medium,
        'radius-lg' => Radius::Medium,
        'radius-full' => Radius::Pill,
    ];

    /**
     * What each of mobile-ui's text sizes is set at.
     *
     * The package sets a chip's and a field's label small, a field and a
     * button at the middle size, and a tall button large. The brand's caption
     * and body cover those, so a tall button's label is set at the body size
     * as every other line that is tapped is; its largest is the brand's
     * smallest display size.
     */
    public const array WIDGET_SIZES = [
        'font-sm' => TypeSize::Caption,
        'font-md' => TypeSize::Body,
        'font-lg' => TypeSize::Body,
        'font-xl' => TypeSize::DisplayM,
    ];

    /**
     * The alias mobile-ui draws a widget in, and a text element that names no face.
     *
     * Set before the colours are merged, because the merge is what carries
     * both across the bridge.
     */
    private const string THE_FACE_WHERE_NONE_IS_NAMED = 'default';

    private ?WhoseTheme $painted = null;

    /** @param Closure(): SecureStorage $keychain the keychain, reached afresh for each screen */
    public function __construct(private readonly Closure $keychain) {}

    /** Paint the theme the screen coming to the front is drawn in, where it is not the one on the glass. */
    public function forTheScreen(NativeComponent $screen): void
    {
        $theme = $this->whoseThemeIs($screen);

        if ($theme !== $this->painted) {
            $this->paint($theme);
        }
    }

    /**
     * Make one theme's roles the ones classes and widgets resolve against.
     *
     * The one resolver is handed to the parser as its light and its dark,
     * because a theme paints the same whatever the phone is set to. Setting a
     * resolver replaces the one before it, which is what makes this
     * idempotent. The widget theme is merged rather than loaded: the keys the
     * design module maps are replaced, and the ones it does not assert stay.
     * The brand's radii, its text sizes and the interface face are set with
     * it, the same in both themes, so a widget is drawn in them from the first
     * frame.
     */
    public function paint(WhoseTheme $theme): void
    {
        $resolve = Theme::resolver($theme);

        TailwindParser::setThemeResolver($resolve);
        TailwindParser::setThemeDarkResolver($resolve);

        $colours = array_map(static fn(ThemeToken $role): string => $role->in($theme), self::WIDGET_ROLES);

        WhatTheWidgetsPaintWith::fonts([self::THE_FACE_WHERE_NONE_IS_NAMED => Typeface::Interface->value]);
        $radii = array_map(static fn(Radius $radius): int => $radius->points(), self::WIDGET_RADII);
        $sizes = array_map(static fn(TypeSize $size): int => $size->points(), self::WIDGET_SIZES);

        WhatTheWidgetsPaintWith::merge(['light' => $colours, 'dark' => $colours, ...$radii, ...$sizes]);

        $this->painted = $theme;
    }

    /** The theme last painted, which is the member's until the first screen is drawn. */
    public function whose(): WhoseTheme
    {
        return $this->painted ?? WhoseTheme::Member;
    }

    /**
     * The theme a screen is drawn in.
     *
     * A screen drawn as a member sees it is the member's whoever opened it,
     * which is asked first because it is the one answer the session does not
     * decide.
     */
    private function whoseThemeIs(NativeComponent $screen): WhoseTheme
    {
        return $screen instanceof DrawnAsAMemberSeesIt ? WhoseTheme::Member : $this->whoseSessionDraws($screen);
    }

    /** A route naming a stack it cannot identify names nobody's session, so its screen is drawn as one for no session is. */
    private function whoseSessionDraws(NativeComponent $screen): WhoseTheme
    {
        $stack = $screen->param('stack');

        if (is_string($stack)) {
            try {
                return WhoTheMenuIsFor::for(($this->keychain)(), StackId::rememberedAs($stack))->theme();
            } catch (StackIsUnidentified) {
                return WhoseTheme::Member;
            }
        }

        return $screen instanceof TakesTheThemeItOpensOver ? $this->whose() : WhoseTheme::Member;
    }
}
