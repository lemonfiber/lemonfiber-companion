<?php

declare(strict_types=1);

it('draws a text marked fixed-size at the size it names, on each platform, and scales every other', function (string $renderer, string ...$draws): void {
    foreach ($draws as $line) {
        expect((string) file_get_contents(base_path($renderer)))->toContain($line);
    }
})->with([
    'the text' => [
        'vendor/nativephp/mobile/src/Edge/Elements/Text.php',
        "'fixed-size' => 'fixedSize',",
        "\$this->textProps['fixed_size'] = filter_var(\$attrs['fixedSize'], FILTER_VALIDATE_BOOLEAN) ? 1 : 0;",
    ],
    'iOS' => [
        'vendor/nativephp/mobile-ui/resources/ios/NativeUITextRenderer.swift',
        'let isFixedSize = p.getInt("fixed_size") == 1',
        '.modifier(NUIFontAtItsSize(fixed: isFixedSize, size: CGFloat(fontSize), weight: fontWeight, design: fontDesign, fontName: fontName.isEmpty ? nil : fontName, italic: isItalic))',
        'content.nuiScaledFont(size: size, weight: weight, design: design, fontName: fontName, italic: italic)',
    ],
    'Android' => [
        'vendor/nativephp/mobile-ui/resources/android/TextRenderer.kt',
        'val drawnAt = if (p.getInt("fixed_size") == 1) with(LocalDensity.current) { fontSize.dp.toSp() } else fontSize.sp',
        'fontSize = drawnAt,',
    ],
]);
