<?php

declare(strict_types=1);

use Modules\Design\Api\Radius;
use Modules\Design\Api\TypeSize;
use Native\Mobile\Edge\TailwindParser;
use Tests\Support\Template;
use Tests\Support\Tree;

// The measures are the brand's, so the copies are checked.
//
// A class list names a measure by EDGE's scale, not by the brand's, so what a
// class draws is read from the parser itself and compared with the brand's
// token file, `app-modules/design/resources/tokens.json`: every gap, padding
// and margin is one of the brand's spacing steps, every text size one of its
// sizes, and every corner its `sm` or `md`. The pill is the brand's too, and
// is drawn by the platform's own chip and button alone, which `Radius` hands
// the widget theme.

/**
 * One block of the brand's token file, by name.
 *
 * Named for this file: the root suites share one namespace (G10).
 *
 * @return array<array-key, mixed>
 */
function theBrandsMeasures(string $block): array
{
    $raw = file_get_contents(Tree::at('app-modules/design/resources/tokens.json'));

    /** @var mixed $decoded */
    $decoded = json_decode(is_string($raw) ? $raw : '', associative: true, depth: 512, flags: JSON_THROW_ON_ERROR);
    $found = data_get($decoded, $block);

    if (! is_array($found) || $found === []) {
        throw new RuntimeException(sprintf('The brand\'s token file carries no `%s` block.', $block));
    }

    return $found;
}

/**
 * Every class written in a template, as `path class`.
 *
 * @return list<array{string, string}> [where, class]
 */
function everyClassWritten(): array
{
    $written = [];

    foreach (Template::all() as $template) {
        foreach ($template->classStrings() as $classes) {
            $split = preg_split('/\s+/', trim($classes));
            $listed = array_filter(is_array($split) ? $split : [], static fn(string $class): bool => $class !== '');

            foreach ($listed as $class) {
                $written[] = [$template->path, $class];
            }
        }
    }

    return $written;
}

/**
 * What every class written in a template draws, as `path class => what it draws`.
 *
 * @return array<string, array<mixed>>
 */
function whatEachClassDraws(): array
{
    $drawn = [];

    foreach (everyClassWritten() as [$where, $class]) {
        $drawn[sprintf('%s %s', $where, $class)] = TailwindParser::parse($class);
    }

    return $drawn;
}

/**
 * The classes whose measure of one kind is not among the brand's, as `path class draws value`.
 *
 * @param list<int|float> $allowed
 *
 * @return list<string>
 */
function measuresNotTheBrands(string $kind, array $allowed): array
{
    $outside = [];
    $steps = array_map(floatval(...), $allowed);

    foreach (whatEachClassDraws() as $where => $drawn) {
        foreach (theMeasuresOfAKind($drawn, $kind) as $property => $measure) {
            if (! in_array($measure, $steps, strict: true)) {
                $outside[] = sprintf('%s draws %s %s', $where, $property, $measure);
            }
        }
    }

    sort($outside);

    return $outside;
}

/**
 * The numeric measures one class draws whose property is of a kind.
 *
 * @param array<mixed> $drawn
 *
 * @return array<string, float>
 */
function theMeasuresOfAKind(array $drawn, string $kind): array
{
    $measures = [];

    foreach ($drawn as $property => $value) {
        if (is_string($property) && preg_match($kind, $property) === 1 && (is_int($value) || is_float($value))) {
            $measures[$property] = (float) $value;
        }
    }

    return $measures;
}

it('rounds every corner as the brand\'s radius of the same name', function (Radius $radius): void {
    expect(theBrandsMeasures('radius')[$radius->value] ?? null)->toBe($radius->points());
})->with(Radius::cases());

it('sets text at the brand\'s size of the same name, at a sixteen-point root', function (TypeSize $size): void {
    $rem = theBrandsMeasures('size')[$size->value] ?? '';
    $rem = is_string($rem) ? $rem : '';

    expect($rem)->toEndWith('rem')
        ->and((float) $rem * 16)->toBe((float) $size->points());
})->with(TypeSize::cases());

it('finds spacing and corners to judge', function (): void {
    expect(measuresNotTheBrands('/^gap$/', []))->not->toBe([])
        ->and(measuresNotTheBrands('/^borderRadius/', []))->not->toBe([])
        ->and(measuresNotTheBrands('/^fontSize$/', []))->not->toBe([]);
});

it('spaces everything a template lays out by the brand\'s steps', function (): void {
    $steps = array_values(array_filter(theBrandsMeasures('space'), is_int(...)));

    expect(measuresNotTheBrands('/^(gap|padding|margin)/', $steps))->toBe([], sprintf(
        "These space a template by a measure the brand does not have:\n  %s\n\n"
        . 'EDGE\'s scale steps by four and the brand\'s does not: `p-5` is 20 and the brand has '
        . 'no 20. Use the class whose measure is one of the brand\'s steps (%s) (DES-R33).',
        implode("\n  ", measuresNotTheBrands('/^(gap|padding|margin)/', $steps)),
        implode(', ', $steps),
    ));
});

it('rounds every corner a template draws to the brand\'s sm or md', function (): void {
    $allowed = [Radius::Small->points(), Radius::Medium->points()];

    expect(measuresNotTheBrands('/^borderRadius/', $allowed))->toBe([], sprintf(
        "These round a corner by a radius the brand does not allow there:\n  %s\n\n"
        . '`rounded` is the brand\'s md and `rounded-[%d]` its sm; the pill is for a chip or '
        . 'a button, which the platform draws (DES-R33).',
        implode("\n  ", measuresNotTheBrands('/^borderRadius/', $allowed)),
        Radius::Small->points(),
    ));
});

it('sets every text a template draws at one of the brand\'s sizes', function (): void {
    $sizes = array_map(static fn(TypeSize $size): int => $size->points(), TypeSize::cases());

    expect(measuresNotTheBrands('/^fontSize$/', $sizes))->toBe([], sprintf(
        "These set text at a size the brand does not have:\n  %s\n\n"
        . 'Set it through a design element, each of which sets a brand size (%s); a size is '
        . 'the one at the platform\'s default text size and scales with it (DES-R33, N4-R14).',
        implode("\n  ", measuresNotTheBrands('/^fontSize$/', $sizes)),
        implode(', ', $sizes),
    ));
});
