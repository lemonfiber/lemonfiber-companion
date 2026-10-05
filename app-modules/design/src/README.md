# Design

The colour roles, faces and measures, and the elements every surface renders through.

## Colour roles

`Api\ThemeToken` names the roles (accent, on-accent, surface, raised, text,
muted, faint, line, the severities ok, warn, alarm, activity, warn-tint and
alarm-tint, on-alarm for the glyph on an alarm fill, and own-action for the
words of the operator's quieter actions) and the hex each paints in the
member's theme and in the operator's, both from the brand's ink theme. Only
the operator's theme paints a severity as one, and lemon as words only in
own-action; the member's paints those roles as text, as the raised surface and
as muted text. `Api\WhoseTheme` names the two themes, and whose session a
screen is drawn for chooses between them.
`Api\Theme::resolver()` is what the composition root gives EDGE's
`TailwindParser` for the screen on view, as its light and its dark resolver
alike, so a theme paints the same whatever the phone is set to. An element
handed a colour as a value reads whose theme is on view from
`Api\WhichThemeIsOnTheGlass`, and a screen that keeps the theme of the one it
opens over is marked `Api\TakesTheThemeItOpensOver`. `resources/tokens.json`
is a copy of the brand's token file, and `tests/Arch/BrandPaletteParityTest.php`
checks every role against it.

## Faces and measures

`Api\Typeface` names every face the app bundles: Golos Text at the brand's
body weight (500) and its display weight (800) for interface text, and DM Mono
at two weights for figures, identifiers, timestamps and log text. Each is a
file in the application's `resources/fonts`, beside its family's licence, and
the build copies both into each platform's bundle. A text element names its
face in a `font` attribute, with the weight class that face is; a widget is
drawn in `Typeface::Interface`, which the composition root sets as the face for
anything that names none.

`Api\TypeSize` names the brand's text sizes the app sets (eyebrow 12, caption
13, body 15 and its smallest display size, 27), each the size at the platform's
default text size, which the platform scales. `Api\Radius` names the brand's
radii: `sm` and `md` for anything drawn here, and the pill for a chip. Every
gap, padding and margin is one of the brand's spacing steps.
`tests/Arch/BrandMeasuresParityTest.php` holds the enums and every template
to `resources/tokens.json`.

## Elements

Blade components under `x-design::`, each drawn from the platform's own
elements and painted only through the roles.

| Element | What it is |
|---|---|
| `title`, `heading`, `body`, `note`, `strong`, `verbatim` | Text in its role and face: a screen's lead line, a heading, running text, a quieter line, a weighted line, and what a machine wrote, in DM Mono |
| `standing` | Where something stands: a `View\Tone` glyph in the tone's colour beside its words and an optional note |
| `notice` | Something told before anything else, raised on its tone's ground with its tone's glyph |
| `card` | Lines that belong together, on a padded card |
| `section` and `row` | A labelled card of the platform's list rows; a row that goes somewhere or does something carries a chevron |
| `action` | A tall filled button, `primary` or `tonal` (`View\Prominence`) |
| `link` | Words that are tapped, with a chevron, on a target a thumb can find |
| `chips` and `chip` | A wrapping row of choices, each chosen or not |
| `scannable` | A code another device scans off the screen, dark squares on the accent, or the words given where there is none |

A container element opens before its slot and closes after it
(`View\HoldsItsSlot`), because a slot is drawn before its component.

A surface module may depend on this one. It depends on `kernel` and
`nativephp/mobile`.
