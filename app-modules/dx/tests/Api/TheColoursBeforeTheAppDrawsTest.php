<?php

declare(strict_types=1);

namespace Modules\Dx\Tests\Api;

use function expect;
use function it;

use JsonException;
use Modules\Dx\Api\NotACellDrawing;
use Modules\Dx\Api\TheColoursBeforeTheAppDraws;

use function sprintf;

it('writes paper and the ink theme\'s canvas as an Android colour resource', function (): void {
    expect(TheColoursBeforeTheAppDraws::fromTokens('{"color": {"paper": "#FBF7EA"}, "theme": {"ink": {"canvas": "#100F0A"}}}')->onAndroid())->toBe(
        "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n"
        . "<!-- Taken from app-modules/design/resources/tokens.json by scripts/brand_drawables.php. Do not edit. -->\n"
        . "<resources>\n"
        . "    <color name=\"lemonfiber_paper\">#FBF7EA</color>\n"
        . "    <color name=\"lemonfiber_canvas\">#100F0A</color>\n"
        . "</resources>\n",
    );
});

it('writes the canvas as an iOS colour set, each channel in hexadecimal', function (): void {
    expect(TheColoursBeforeTheAppDraws::fromTokens('{"color": {"paper": "#FBF7EA"}, "theme": {"ink": {"canvas": "#10A0FF"}}}')->onIos())
        ->toContain('"blue" : "0xFF",')
        ->toContain('"green" : "0xA0",')
        ->toContain('"red" : "0x10"')
        ->toContain('"color-space" : "srgb",')
        ->toContain('"idiom" : "universal"');
});

it('refuses tokens that carry no colour where one is taken from, naming the place', function (string $tokens, string $where): void {
    expect(static fn(): TheColoursBeforeTheAppDraws => TheColoursBeforeTheAppDraws::fromTokens($tokens))
        ->toThrow(NotACellDrawing::class, sprintf('The tokens carry no colour at %s.', $where));
})->with([
    'no paper' => ['{"color": {}, "theme": {"ink": {"canvas": "#100F0A"}}}', 'color.paper'],
    'no ink theme' => ['{"color": {"paper": "#FBF7EA"}, "theme": "ink"}', 'theme.ink.canvas'],
    'a named colour' => ['{"color": {"paper": "white"}}', 'color.paper'],
    'a colour with alpha' => ['{"color": {"paper": "#FFFBF7EA"}}', 'color.paper'],
    'a colour in lower case' => ['{"color": {"paper": "#fbf7ea"}}', 'color.paper'],
    'not a string' => ['{"color": {"paper": 16316394}}', 'color.paper'],
]);

it('refuses tokens that are not JSON', function (): void {
    expect(static fn(): TheColoursBeforeTheAppDraws => TheColoursBeforeTheAppDraws::fromTokens('{'))->toThrow(JsonException::class);
});
