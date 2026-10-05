<?php

declare(strict_types=1);

// A list row hands its leading glyph a colour, so a service's row is drawn in
// the colour of how it stands. The renderers are the package's, rewritten by
// `scripts/patch_nativephp.php` where they read no colour; a colour the row
// carries that a renderer never reads is a state drawn in grey on that
// platform.

it('paints a row\'s leading glyph in the colour the row hands it, on each platform', function (string $renderer, string ...$paints): void {
    foreach ($paints as $line) {
        expect((string) file_get_contents(base_path($renderer)))->toContain($line);
    }
})->with([
    'the row' => [
        'vendor/nativephp/mobile-ui/src/Elements/ListItem.php',
        '$this->listItemProps[\'leading_icon_color\'] = $this->resolveColorValue($color);',
    ],
    'Android' => [
        'vendor/nativephp/mobile-ui/resources/android/ListItemRenderer.kt',
        'val leadingIconColor = p.getColor("leading_icon_color", 0)',
        'leadingIconColor = if (leadingIconColor != 0) Color(leadingIconColor) else Color.Unspecified,',
    ],
    'iOS' => [
        'vendor/nativephp/mobile-ui/resources/ios/NativeUIListItemRenderer.swift',
        'let leadingIconColor = p.getColor("leading_icon_color", default: 0)',
        'iconColor: leadingIconColor,',
        'iconBgColor: Int = 0, iconColor: Int = 0, checked: Bool = false',
        <<<'SWIFT'
                Image(systemName: getIconForName(value))
                    .frame(width: 24, height: 24)
                    .foregroundColor(iconColor != 0 ? Color(argb: iconColor) : .secondary)
                    .accessibilityHidden(true)
SWIFT,
    ],
]);
