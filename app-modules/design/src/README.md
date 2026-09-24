# Design

The theme every surface renders through.

`Theme::resolver()` is the resolver the composition root gives EDGE's
`TailwindParser`. `ThemeToken` names the tokens it answers for and the colour
each one paints. `resources/tokens.json` is a copy of the brand's token file, and
`tests/Arch/BrandPaletteParityTest.php` checks those colours against it.

A surface module may depend on this one. It depends on `kernel` and
`nativephp/mobile`.
