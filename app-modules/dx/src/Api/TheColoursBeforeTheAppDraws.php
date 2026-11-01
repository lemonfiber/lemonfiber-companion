<?php

declare(strict_types=1);

namespace Modules\Dx\Api;

use function array_key_exists;
use function explode;
use function is_array;
use function is_string;
use function json_decode;
use function preg_match;
use function sprintf;
use function vsprintf;

/**
 * The brand colours a phone paints before the app has drawn anything, taken from the brand's tokens.
 *
 * The launcher icon's ground is the brand's `paper`, and the launch's is the
 * ink theme's `canvas`, the member theme's ground: an Android colour resource
 * holds both, and an iOS colour set the canvas.
 */
final readonly class TheColoursBeforeTheAppDraws
{
    /** Where the bridge keeps the Android resource, under its `resources` directory. */
    public const string ON_ANDROID = 'android/res/values/lemonfiber_brand.xml';

    /** Where the bridge keeps the iOS colour set, under its `resources` directory. */
    public const string ON_IOS = 'catalog/LemonfiberCanvas.colorset/Contents.json';

    /** The canvas, by its resource name. */
    private const string THE_CANVAS = 'lemonfiber_canvas';

    /** Each colour resource by name, with the token path it is taken from. */
    private const array THE_COLOURS = [
        'lemonfiber_paper' => 'color.paper',
        self::THE_CANVAS => 'theme.ink.canvas',
    ];

    /** @param array<string, array{string, string, string}> $colours each colour resource by name, as its red, green and blue in hexadecimal */
    private function __construct(private array $colours) {}

    /** The colours, from the text of the brand's `tokens.json`. */
    public static function fromTokens(string $tokens): self
    {
        $decoded = json_decode($tokens, associative: true, flags: JSON_THROW_ON_ERROR);
        $colours = [];

        foreach (self::THE_COLOURS as $name => $path) {
            $colours[$name] = self::at($decoded, $path);
        }

        return new self($colours);
    }

    /** The Android colour resource's XML. */
    public function onAndroid(): string
    {
        $colours = '';

        foreach ($this->colours as $name => $colour) {
            $colours .= vsprintf("    <color name=\"%s\">#%s%s%s</color>\n", [$name, ...$colour]);
        }

        return sprintf(
            <<<'XML'
                <?xml version="1.0" encoding="utf-8"?>
                <!-- Taken from app-modules/design/resources/tokens.json by scripts/brand_drawables.php. Do not edit. -->
                <resources>
                %s</resources>

                XML,
            $colours,
        );
    }

    /** The iOS colour set of the canvas, as Xcode writes one. */
    public function onIos(): string
    {
        [$red, $green, $blue] = $this->colours[self::THE_CANVAS];

        return sprintf(
            <<<'JSON'
                {
                  "colors" : [
                    {
                      "color" : {
                        "color-space" : "srgb",
                        "components" : {
                          "alpha" : "1.000",
                          "blue" : "0x%s",
                          "green" : "0x%s",
                          "red" : "0x%s"
                        }
                      },
                      "idiom" : "universal"
                    }
                  ],
                  "info" : {
                    "author" : "xcode",
                    "version" : 1
                  }
                }

                JSON,
            $blue,
            $green,
            $red,
        );
    }

    /**
     * The colour at one dotted path of the tokens, as its red, green and blue in hexadecimal.
     *
     * @return array{string, string, string}
     */
    private static function at(mixed $tokens, string $path): array
    {
        foreach (explode('.', $path) as $key) {
            $tokens = is_array($tokens) && array_key_exists($key, $tokens) ? $tokens[$key] : null;
        }

        if (! is_string($tokens) || preg_match('/\A#([0-9A-F]{2})([0-9A-F]{2})([0-9A-F]{2})\z/', $tokens, $channels) !== 1) {
            throw NotACellDrawing::withNoColourAt($path);
        }

        return [$channels[1], $channels[2], $channels[3]];
    }
}
