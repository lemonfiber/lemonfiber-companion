<?php

declare(strict_types=1);

use Modules\Design\Api\Typeface;
use Tests\Support\OurCode;
use Tests\Support\Tree;

// The faces the app bundles are the brand's, each with its licence beside it,
// and nothing fetches a face while the app runs.
//
// `resources/fonts` is what the build copies into each platform's bundle, so
// what sits there is what ships: every face `Typeface` names and no other, and
// the SIL Open Font License each family is distributed under, which the
// licence requires to travel with every copy. The family a face belongs to is
// read from the file itself and compared with the brand's token file, so a
// face renamed into place is caught as well as one missing.

/** Where the build takes the faces from. */
const WHERE_THE_FACES_ARE = 'resources/fonts';

/**
 * Which of the brand's type tokens each face is, the file its family's licence is in, and the weight token it is drawn at.
 *
 * Written here rather than derived from the enum, so the test has something of
 * its own to compare against. The brand names no weight for its mono family,
 * so a DM Mono face is held to no weight token.
 *
 * @return array<string, array{string, string, ?string}> face => [brand font token, licence file, brand weight token]
 */
function whatEachBundledFaceIs(): array
{
    return [
        Typeface::Interface->value => ['body', 'GolosText-OFL.txt', 'weightBody'],
        Typeface::InterfaceDisplay->value => ['body', 'GolosText-OFL.txt', 'weightDisplay'],
        Typeface::Figures->value => ['mono', 'DMMono-OFL.txt', null],
        Typeface::FiguresMedium->value => ['mono', 'DMMono-OFL.txt', null],
    ];
}

/**
 * The weight a font file declares, from its `OS/2` table's `usWeightClass`, or null where it has none.
 *
 * The table directory follows the twelve-byte header, sixteen bytes a table:
 * its tag, checksum, offset and length. The weight is the second field of
 * `OS/2`, four bytes in.
 */
function theWeightTheFaceDeclares(string $file): ?int
{
    $bytes = (string) file_get_contents($file);
    $tables = theShortAt($bytes, 4);

    for ($table = 0; $table < $tables; $table++) {
        $at = 12 + 16 * $table;

        if (substr($bytes, $at, 4) === 'OS/2') {
            $offset = unpack('N', $bytes, $at + 8);

            return is_array($offset) && is_int($offset[1] ?? null) ? theShortAt($bytes, $offset[1] + 4) : null;
        }
    }

    return null;
}

/** The unsigned big-endian sixteen-bit number at one offset of a font file. */
function theShortAt(string $bytes, int $offset): int
{
    $read = unpack('n', $bytes, $offset);

    return is_array($read) && is_int($read[1] ?? null) ? $read[1] : 0;
}

/**
 * The brand's type weights by token: `weightBody` and `weightDisplay`.
 *
 * @return array<string, int>
 */
function theBrandsWeights(): array
{
    $raw = file_get_contents(Tree::at('app-modules/design/resources/tokens.json'));

    /** @var mixed $decoded */
    $decoded = json_decode(is_string($raw) ? $raw : '', associative: true, depth: 512, flags: JSON_THROW_ON_ERROR);
    $font = data_get($decoded, 'font');
    $weights = [];

    foreach (is_array($font) ? $font : [] as $token => $weight) {
        if (is_string($token) && is_int($weight)) {
            $weights[$token] = $weight;
        }
    }

    return $weights;
}

/**
 * The brand's type families by token: `display`, `body` and `mono`.
 *
 * @return array<string, string>
 */
function theBrandsFamilies(): array
{
    $raw = file_get_contents(Tree::at('app-modules/design/resources/tokens.json'));

    /** @var mixed $decoded */
    $decoded = json_decode(is_string($raw) ? $raw : '', associative: true, depth: 512, flags: JSON_THROW_ON_ERROR);
    $families = data_get($decoded, 'font');

    if (! is_array($families)) {
        throw new RuntimeException('The brand\'s token file carries no font block.');
    }

    $named = [];

    foreach ($families as $token => $family) {
        if (is_string($token) && is_string($family)) {
            $named[$token] = $family;
        }
    }

    return $named;
}

/** Whether a font file names a family in its name table, which a Windows-platform record holds in UTF-16BE. */
function theFaceIsOf(string $file, string $family): bool
{
    $bytes = file_get_contents($file);

    return is_string($bytes) && str_contains($bytes, mb_convert_encoding($family, 'UTF-16BE', 'UTF-8'));
}

/**
 * Every file the build would copy, by name.
 *
 * @return list<string>
 */
function whatTheBuildCopies(): array
{
    $found = glob(sprintf('%s/*.{ttf,otf,ttc,txt}', Tree::at(WHERE_THE_FACES_ARE)), GLOB_BRACE);
    $copied = array_map(basename(...), is_array($found) ? $found : []);
    sort($copied);

    return $copied;
}

it('describes every face the app bundles', function (): void {
    expect(array_keys(whatEachBundledFaceIs()))->toEqualCanonicalizing(array_map(
        static fn(Typeface $face): string => $face->value,
        Typeface::cases(),
    ));
});

it('bundles every face it names, each family\'s licence, and nothing else', function (): void {
    $expected = [
        ...array_map(static fn(Typeface $face): string => sprintf('%s.ttf', $face->value), Typeface::cases()),
        ...array_column(whatEachBundledFaceIs(), 1),
    ];
    $expected = array_values(array_unique($expected));
    sort($expected);

    expect(whatTheBuildCopies())->toBe($expected);
});

it('sets each face in the family the brand names for it', function (Typeface $face): void {
    [$token] = whatEachBundledFaceIs()[$face->value];
    $family = theBrandsFamilies()[$token] ?? '';

    expect($family)->not->toBe('')
        ->and(theFaceIsOf(Tree::at(sprintf('%s/%s.ttf', WHERE_THE_FACES_ARE, $face->value)), $family))->toBeTrue();
})->with(Typeface::cases());

it('draws each interface face at the brand\'s weight for it', function (Typeface $face): void {
    [, , $token] = whatEachBundledFaceIs()[$face->value];

    expect($token === null || theWeightTheFaceDeclares(Tree::at(sprintf('%s/%s.ttf', WHERE_THE_FACES_ARE, $face->value))) === (theBrandsWeights()[$token] ?? 0))->toBeTrue();
})->with(Typeface::cases());

it('reads a face\'s weight from the file', function (): void {
    expect(theWeightTheFaceDeclares(Tree::at(sprintf('%s/%s.ttf', WHERE_THE_FACES_ARE, Typeface::Figures->value))))->toBe(400)
        ->and(theWeightTheFaceDeclares(Tree::at(sprintf('%s/%s.ttf', WHERE_THE_FACES_ARE, Typeface::InterfaceDisplay->value))))->toBe(800);
});

it('bundles no face of the brand\'s display family, which only the outlined wordmark carries', function (): void {
    $display = theBrandsFamilies()['display'] ?? '';
    $found = array_filter(
        Typeface::cases(),
        static fn(Typeface $face): bool => theFaceIsOf(Tree::at(sprintf('%s/%s.ttf', WHERE_THE_FACES_ARE, $face->value)), $display),
    );

    expect($display)->not->toBe('')
        ->and($found)->toBe([]);
});

it('ships each family under the SIL Open Font License, with its copyright', function (Typeface $face): void {
    [$token, $licence] = whatEachBundledFaceIs()[$face->value];
    $text = file_get_contents(Tree::at(sprintf('%s/%s', WHERE_THE_FACES_ARE, $licence)));

    expect($text)->toBeString()
        ->toContain('SIL Open Font License, Version 1.1')
        ->toContain(sprintf('The %s Project Authors', theBrandsFamilies()[$token] ?? ''));
})->with(Typeface::cases());

it('fetches no face while the app runs', function (): void {
    $fetching = [];

    foreach (OurCode::sourceFiles() as $file) {
        $source = file_get_contents($file);

        if (is_string($source) && preg_match('/fonts\.(googleapis|gstatic)\.com|GoogleFonts|native:font\b/', $source) === 1) {
            $fetching[] = $file;
        }
    }

    expect($fetching)->toBe([]);
});
