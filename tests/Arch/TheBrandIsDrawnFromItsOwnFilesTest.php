<?php

declare(strict_types=1);

use Modules\Dx\Api\ACellDrawing;
use Modules\Dx\Api\TheColoursBeforeTheAppDraws;
use Modules\Dx\Api\WhatAndroidDrawsOfTheBrand;
use Tests\Support\Tree;

// What a phone draws of the brand before the app draws is drawn from the
// brand's own files and tokens, and the bridge's manifest copies each into the
// native projects.

/** Where the bridge keeps what its manifest copies. */
const WHERE_THE_BRIDGE_KEEPS_ITS_RESOURCES = 'bridge/resources/%s';

/** The text of one file of the tree, a brand file or one drawn from it. */
function whatTheBrandFileHolds(string $path): string
{
    $text = file_get_contents(Tree::at($path));

    return is_string($text) ? $text : '';
}

/**
 * What the bridge's manifest copies into one platform's project, by where it copies from.
 *
 * @return array<string, string>
 */
function whatTheBridgeCopiesTo(string $platform): array
{
    $manifest = json_decode(whatTheBrandFileHolds('bridge/nativephp.json'), associative: true, flags: JSON_THROW_ON_ERROR);
    $copied = data_get($manifest, sprintf('assets.%s', $platform));

    $named = [];

    foreach (is_array($copied) ? $copied : [] as $from => $to) {
        if (is_string($from) && is_string($to)) {
            $named[$from] = $to;
        }
    }

    return $named;
}

/** The ink theme's canvas, as the brand's tokens carry it. */
function theInkCanvas(): string
{
    $tokens = json_decode(whatTheBrandFileHolds('app-modules/design/resources/tokens.json'), associative: true, flags: JSON_THROW_ON_ERROR);
    $canvas = data_get($tokens, 'theme.ink.canvas');

    return is_string($canvas) ? $canvas : '';
}

it('ships each drawable as the brand file it is drawn from draws it', function (WhatAndroidDrawsOfTheBrand $drawable): void {
    expect(whatTheBrandFileHolds(sprintf(WHERE_THE_BRIDGE_KEEPS_ITS_RESOURCES, $drawable->value)))
        ->toBe($drawable->drawn(ACellDrawing::fromSvg(whatTheBrandFileHolds(sprintf(WHERE_THE_BRIDGE_KEEPS_ITS_RESOURCES, $drawable->source())))));
})->with(WhatAndroidDrawsOfTheBrand::cases());

it('ships the brand\'s colours on both platforms as its tokens carry them', function (): void {
    $colours = TheColoursBeforeTheAppDraws::fromTokens(whatTheBrandFileHolds('app-modules/design/resources/tokens.json'));

    expect(whatTheBrandFileHolds(sprintf(WHERE_THE_BRIDGE_KEEPS_ITS_RESOURCES, TheColoursBeforeTheAppDraws::ON_ANDROID)))->toBe($colours->onAndroid())
        ->and(whatTheBrandFileHolds(sprintf(WHERE_THE_BRIDGE_KEEPS_ITS_RESOURCES, TheColoursBeforeTheAppDraws::ON_IOS)))->toBe($colours->onIos());
});

it('gives Interface Builder the canvas the tokens carry, where the storyboard names it', function (): void {
    $patch = whatTheBrandFileHolds('scripts/patch_nativephp/58-the-ios-launch-is-the-launch-mark.patch');
    $channels = array_map(
        static fn(string $pair): string => sprintf('%.4F', hexdec($pair) / 255),
        str_split(substr(theInkCanvas(), 1), 2),
    );

    expect($patch)->toContain(vsprintf('<color red="%s" green="%s" blue="%s" alpha="1"', $channels));
});

it('copies every drawable and the colours into the Android project, each where its name puts it', function (): void {
    $made = [...array_column(WhatAndroidDrawsOfTheBrand::cases(), 'value'), TheColoursBeforeTheAppDraws::ON_ANDROID];
    $copied = whatTheBridgeCopiesTo('android');

    foreach ($made as $resource) {
        expect($copied[$resource] ?? null)->toBe(substr($resource, strlen('android/')));
    }
});

it('copies the launch mark and the canvas into the iOS asset catalog', function (): void {
    expect(whatTheBridgeCopiesTo('ios'))->toBe([
        'brand/mark-primary.svg' => 'Resources/../Assets.xcassets/LaunchMark.imageset/mark-primary.svg',
        'catalog/LaunchMark.imageset/Contents.json' => 'Resources/../Assets.xcassets/LaunchMark.imageset/Contents.json',
        TheColoursBeforeTheAppDraws::ON_IOS => 'Resources/../Assets.xcassets/LemonfiberCanvas.colorset/Contents.json',
    ]);
});

it('copies only files the bridge has', function (string $platform): void {
    $missing = array_filter(
        array_keys(whatTheBridgeCopiesTo($platform)),
        static fn(string $from): bool => ! is_file(Tree::at(sprintf(WHERE_THE_BRIDGE_KEEPS_ITS_RESOURCES, $from))),
    );

    expect($missing)->toBe([]);
})->with(['android', 'ios']);
