<?php

declare(strict_types=1);

namespace Modules\Dx\Tests\Api;

use function expect;
use function it;

use Modules\Dx\Api\ACellDrawing;
use Modules\Dx\Api\NotACellDrawing;

use function sprintf;

/** A drawing the brand's generator could have written, holding the given body. */
function aDrawingHolding(string $body, string $box = '0 0 4 3'): string
{
    return sprintf("<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"%s\" shape-rendering=\"crispEdges\">\n  <title>a mark</title>\n%s</svg>\n", $box, $body);
}

it('reads each colour\'s runs in the order the file paints them', function (): void {
    $drawing = ACellDrawing::fromSvg(aDrawingHolding(
        "  <rect x=\"0\" y=\"0\" width=\"2\" height=\"1\" fill=\"#17160F\"/>\n"
        . "  <rect x=\"2\" y=\"0\" width=\"2\" height=\"3\" fill=\"#F0C419\"/>\n"
        . "  <rect x=\"0\" y=\"2\" width=\"1\" height=\"1\" fill=\"#17160F\"/>\n",
    ));

    expect($drawing->wide)->toBe(4)
        ->and($drawing->high)->toBe(3)
        ->and($drawing->runs)->toBe([
            '#17160F' => [[0, 0, 2, 1], [0, 2, 1, 1]],
            '#F0C419' => [[2, 0, 2, 3]],
        ]);
});

it('refuses a drawing with no viewBox of whole cells from the origin', function (string $box): void {
    expect(static fn(): ACellDrawing => ACellDrawing::fromSvg(aDrawingHolding('', $box)))
        ->toThrow(NotACellDrawing::class, 'no viewBox of whole cells');
})->with(['moved off the origin' => '1 0 4 3', 'part cells' => '0 0 4.5 3']);

it('refuses an element that is not a cell, naming it', function (): void {
    expect(static fn(): ACellDrawing => ACellDrawing::fromSvg(aDrawingHolding("  <circle cx=\"1\" cy=\"1\" r=\"1\"/>\n  <path d=\"M0 0\"/>\n  <path d=\"M1 1\"/>\n")))
        ->toThrow(NotACellDrawing::class, 'The drawing holds circle, path, which is not a cell.');
});

it('refuses a rect that is not a run of whole cells in one colour', function (string $rect): void {
    expect(static fn(): ACellDrawing => ACellDrawing::fromSvg(aDrawingHolding($rect)))
        ->toThrow(NotACellDrawing::class, 'not a run of whole cells in one colour');
})->with([
    'part of a cell' => '<rect x="0.5" y="0" width="1" height="1" fill="#17160F"/>',
    'no colour' => '<rect x="0" y="0" width="1" height="1"/>',
    'a named colour' => '<rect x="0" y="0" width="1" height="1" fill="black"/>',
]);

it('refuses a run that leaves the grid', function (string $rect, string $where): void {
    expect(static fn(): ACellDrawing => ACellDrawing::fromSvg(aDrawingHolding($rect)))
        ->toThrow(NotACellDrawing::class, sprintf('The run at %s leaves the 4x3 grid.', $where));
})->with([
    'past the right edge' => ['<rect x="3" y="0" width="2" height="1" fill="#17160F"/>', '3,0'],
    'past the bottom edge' => ['<rect x="0" y="2" width="1" height="2" fill="#17160F"/>', '0,2'],
    'no width' => ['<rect x="0" y="0" width="0" height="1" fill="#17160F"/>', '0,0'],
    'no height' => ['<rect x="1" y="1" width="1" height="0" fill="#17160F"/>', '1,1'],
]);

it('takes a run that reaches the last cell of the grid', function (): void {
    expect(ACellDrawing::fromSvg(aDrawingHolding('<rect x="3" y="2" width="1" height="1" fill="#17160F"/>'))->runs)
        ->toBe(['#17160F' => [[3, 2, 1, 1]]]);
});

it('refuses a cell painted twice, naming it', function (): void {
    expect(static fn(): ACellDrawing => ACellDrawing::fromSvg(aDrawingHolding(
        "<rect x=\"0\" y=\"0\" width=\"3\" height=\"2\" fill=\"#17160F\"/>\n<rect x=\"2\" y=\"1\" width=\"1\" height=\"1\" fill=\"#F0C419\"/>\n",
    )))->toThrow(NotACellDrawing::class, 'The cell at 2,1 is painted twice.');
});
