# Design

The colour roles every surface renders through.

`ThemeToken` names the roles (accent, on-accent, surface, raised, text, muted,
line) and the hex each paints in light mode and in dark. `Theme::resolver()` and
`Theme::darkResolver()` are what the composition root gives EDGE's
`TailwindParser`, so every `bg-`, `text-` and `border-theme-*` class carries its
dark companion. `Theme::forTheWidgets()` is the palette the platform's widgets
paint with. `resources/tokens.json` is a copy of the brand's token file, and
`tests/Arch/BrandPaletteParityTest.php` checks every role against it, the dark
values against its ink theme.

A surface module may depend on this one. It depends on `kernel` and
`nativephp/mobile`.
