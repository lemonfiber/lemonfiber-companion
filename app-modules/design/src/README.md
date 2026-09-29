# Design

The colour roles and the elements every surface renders through.

## Colour roles

`Api\ThemeToken` names the roles (accent, on-accent, surface, raised, text,
muted, line) and the hex each paints in light mode and in dark, from the brand's
paper and ink themes. `Api\Theme::resolver()` and `Theme::darkResolver()` are
what the composition root gives EDGE's `TailwindParser`, so every `bg-`, `text-`
and `border-theme-*` class carries its dark companion. `resources/tokens.json` is
a copy of the brand's token file, and `tests/Arch/BrandPaletteParityTest.php`
checks every role against it.

## Elements

Blade components under `x-design::`, each drawn from the platform's own
elements and painted only through the roles.

| Element | What it is |
|---|---|
| `title`, `heading`, `body`, `note`, `strong`, `verbatim` | Text in its role: a screen's lead line, a heading, running text, a quieter line, a weighted line, and what a machine wrote |
| `standing` | Where something stands: a `View\Tone` glyph beside its words and an optional note |
| `notice` | Something told before anything else, raised off the ground with its tone's glyph |
| `card` | Lines that belong together, on a padded card |
| `section` and `row` | A labelled card of the platform's list rows; a row that goes somewhere or does something carries a chevron |
| `action` | A tall filled button, `primary` or `tonal` (`View\Prominence`) |
| `link` | Words that are tapped, with a chevron, on a target a thumb can find |
| `chips` and `chip` | A wrapping row of choices, each chosen or not |

A container element opens before its slot and closes after it
(`View\HoldsItsSlot`), because a slot is drawn before its component.

A surface module may depend on this one. It depends on `kernel` and
`nativephp/mobile`.
