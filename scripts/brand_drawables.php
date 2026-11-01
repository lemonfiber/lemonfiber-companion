<?php

declare(strict_types=1);

/*
 * Writes the Android drawables and the colours both platforms paint before the
 * app draws, which the bridge ships, from the brand's
 * own files: the SVGs under `bridge/resources/brand` and the design module's
 * copy of `tokens.json`. `tests/Arch/TheBrandIsDrawnFromItsOwnFilesTest.php`
 * fails while a written file differs from what this writes.
 *
 *     php scripts/brand_drawables.php
 */

use Modules\Dx\Api\ACellDrawing;
use Modules\Dx\Api\TheColoursBeforeTheAppDraws;
use Modules\Dx\Api\WhatAndroidDrawsOfTheBrand;

require sprintf('%s/../vendor/autoload.php', __DIR__);

/** Where the bridge keeps what its manifest copies. */
const THE_BRIDGES_RESOURCES = '%s/bridge/resources/%s';

/** The text of one file, or the run stopped naming it. */
function read(string $path): string
{
    $text = file_get_contents($path);

    if (! is_string($text)) {
        fwrite(STDERR, sprintf("brand_drawables: %s could not be read.\n", $path));

        exit(1);
    }

    return $text;
}

/** Writes one of the bridge's resources. */
function write(string $resource, string $text): void
{
    $path = sprintf(THE_BRIDGES_RESOURCES, dirname(__DIR__), $resource);

    if (! is_dir(dirname($path))) {
        mkdir(dirname($path), recursive: true);
    }

    file_put_contents($path, $text);
}

foreach (WhatAndroidDrawsOfTheBrand::cases() as $drawable) {
    write($drawable->value, $drawable->drawn(ACellDrawing::fromSvg(read(sprintf(THE_BRIDGES_RESOURCES, dirname(__DIR__), $drawable->source())))));
}

$colours = TheColoursBeforeTheAppDraws::fromTokens(read(sprintf('%s/app-modules/design/resources/tokens.json', dirname(__DIR__))));
write(TheColoursBeforeTheAppDraws::ON_ANDROID, $colours->onAndroid());
write(TheColoursBeforeTheAppDraws::ON_IOS, $colours->onIos());
