<?php

declare(strict_types=1);

use Lemonfiber\Sdk\Envelope\Envelope;
use Modules\Kernel\Api\Holding;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Sentences;
use Modules\Kernel\Api\Shelf;
use Modules\Kernel\Api\WhatTheyMayWatch;
use Modules\Sdk\Api\Holdings;
use Modules\Sdk\Api\ShelfIsUnreadable;
use Tests\Support\WhatTheContractAccepts;

/**
 * One row of a shelf, as a stack sends it, with this case's field changed.
 *
 * @param  array<string, mixed> $differently
 * @return array<string, mixed>
 */
function oneHolding(array $differently = []): array
{
    return ['id' => 'a1', 'title' => 'A film', 'medium' => 'film', 'year' => 1999, ...$differently];
}

/**
 * What a stack says about one member's shelf, with this case's field changed.
 *
 * @param  array<string, mixed> $differently
 * @return array<string, mixed>
 */
function whatAStackSaysAboutAShelf(array $differently = []): array
{
    return [
        'id' => 'the-loft',
        'member' => 'ada',
        'available' => true,
        'findings' => [],
        'holdings' => [oneHolding()],
        ...$differently,
    ];
}

/** The reading of a payload, as the adapter would make it. */
function theShelfIn(mixed $data): WhatTheyMayWatch
{
    return Holdings::in(new Envelope(1, 'held', $data));
}

/**
 * One reading carried out of an `either()` arm.
 *
 * A class rather than an array, because `either()` answers with an object and
 * the arms must agree on which — the shape the contract suites use for the
 * same reason.
 */
final readonly class WhatAShelfReadingCameTo
{
    /** @param list<string> $rows */
    public function __construct(public array $rows) {}
}

/**
 * What one reading came away with, whichever arm it took.
 *
 * @return list<string>
 */
function whatTheShelfHeld(mixed $data): array
{
    return theShelfIn($data)->either(
        told: static function (Shelf $shelf): WhatAShelfReadingCameTo {
            $rows = [];

            foreach ($shelf as $holding) {
                $rows[] = $holding->titled();
            }

            return new WhatAShelfReadingCameTo($rows);
        },
        outOfReach: static function (Sentences $said): WhatAShelfReadingCameTo {
            $lines = [];

            foreach ($said as $sentence) {
                $lines[] = sprintf('out-of-reach:%s', $sentence->shown());
            }

            return new WhatAShelfReadingCameTo($lines);
        },
        refused: static fn(Obstacle $why): WhatAShelfReadingCameTo
            => new WhatAShelfReadingCameTo([$why->value]),
    )->rows;
}

/** The year one holding carries, as a word, or how it is undated. */
function theYearOnTheOnlyHolding(mixed $data): string
{
    return whatTheShelfHeldOf($data, static fn(Holding $holding): string => $holding->year()->either(
        dated: static fn(int $year): WhatAShelfReadingCameTo
            => new WhatAShelfReadingCameTo([(string) $year]),
        unstated: static fn(): WhatAShelfReadingCameTo => new WhatAShelfReadingCameTo(['undated']),
    )->rows[0]);
}

/**
 * One fact read off the first holding a reading carried.
 *
 * @param Closure(Holding): string $reading
 */
function whatTheShelfHeldOf(mixed $data, Closure $reading): string
{
    return theShelfIn($data)->either(
        told: static function (Shelf $shelf) use ($reading): WhatAShelfReadingCameTo {
            foreach ($shelf as $holding) {
                return new WhatAShelfReadingCameTo([$reading($holding)]);
            }

            return new WhatAShelfReadingCameTo(['no rows']);
        },
        // Fewer parameters than the fold hands over, which PHP allows and
        // which says plainly that these arms are not what the case is about.
        outOfReach: static fn(): WhatAShelfReadingCameTo
            => new WhatAShelfReadingCameTo(['out-of-reach']),
        refused: static fn(): WhatAShelfReadingCameTo
            => new WhatAShelfReadingCameTo(['refused']),
    )->rows[0];
}

it('N3-R14 — reads the shelf the core listed, in the order it listed it', function (): void {
    $held = whatAStackSaysAboutAShelf(['holdings' => [
        oneHolding(),
        oneHolding(['id' => 'b2', 'title' => 'A series', 'medium' => 'series']),
        oneHolding(['id' => 'c3', 'title' => 'Something else', 'medium' => 'other']),
    ]]);

    expect(whatTheShelfHeld($held))->toBe(['A film', 'A series', 'Something else']);
});

it('N3-R15 — reads a library that could not be read as out of reach, not as empty', function (): void {
    // The one distinction a screen cannot recover on its own, and the reason
    // `available` is read before anything else is believed.
    $unread = whatAStackSaysAboutAShelf([
        'available' => false,
        'holdings' => [],
        'findings' => ['The media server did not answer.'],
    ]);

    expect(whatTheShelfHeld($unread))->toBe(['out-of-reach:The media server did not answer.'])
        ->and(whatTheShelfHeld(whatAStackSaysAboutAShelf(['holdings' => []])))->toBe([]);
});

it('carries the year where the core dated it, and says so where it did not', function (): void {
    $undated = oneHolding();
    unset($undated['year']);

    expect(theYearOnTheOnlyHolding(whatAStackSaysAboutAShelf(['holdings' => [oneHolding()]])))
        ->toBe('1999')
        // Absent and null are one answer: the contract carries the year
        // optionally, so a holding nobody dated is ordinary rather than
        // malformed, and both reach a screen as something it can draw.
        ->and(theYearOnTheOnlyHolding(whatAStackSaysAboutAShelf([
            'holdings' => [oneHolding(['year' => null])],
        ])))->toBe('undated')
        ->and(theYearOnTheOnlyHolding(whatAStackSaysAboutAShelf(['holdings' => [$undated]])))
        ->toBe('undated');
});

it('refuses a payload that is not a shape at all', function (): void {
    expect(fn(): object => theShelfIn('nothing to report'))
        ->toThrow(ShelfIsUnreadable::class, 'data');
});

it('refuses a shelf that never said whether it could be read', function (): void {
    // Refused rather than defaulted. The reassuring default turns every
    // unreachable library into an empty one, silently.
    $said = whatAStackSaysAboutAShelf();
    unset($said['available']);

    expect(fn(): object => theShelfIn($said))->toThrow(ShelfIsUnreadable::class, 'available')
        ->and(fn(): object => theShelfIn(whatAStackSaysAboutAShelf(['available' => 'yes'])))
        ->toThrow(ShelfIsUnreadable::class, 'available');
});

it('refuses an unreadable shelf whose sentences it cannot read', function (): void {
    $without = whatAStackSaysAboutAShelf(['available' => false]);
    unset($without['findings']);

    expect(fn(): object => theShelfIn($without))->toThrow(ShelfIsUnreadable::class, 'findings')
        ->and(fn(): object => theShelfIn(whatAStackSaysAboutAShelf([
            'available' => false, 'findings' => 'one sentence',
        ])))->toThrow(ShelfIsUnreadable::class, 'findings')
        ->and(fn(): object => theShelfIn(whatAStackSaysAboutAShelf([
            'available' => false, 'findings' => [7],
        ])))->toThrow(ShelfIsUnreadable::class, 'findings');
});

it('refuses a payload that listed no holdings at all', function (): void {
    $without = whatAStackSaysAboutAShelf();
    unset($without['holdings']);

    expect(fn(): object => theShelfIn($without))->toThrow(ShelfIsUnreadable::class, 'holdings')
        ->and(fn(): object => theShelfIn(whatAStackSaysAboutAShelf(['holdings' => 'none'])))
        ->toThrow(ShelfIsUnreadable::class, 'holdings');
});

it('refuses one row it cannot read, naming where it sat', function (): void {
    foreach ([
        ['a string where a holding belongs'],
        [oneHolding(['id' => 7])],
        [oneHolding(['title' => 7])],
        [oneHolding(['medium' => 7])],
        [oneHolding(['year' => 'nineteen ninety-nine'])],
    ] as $rows) {
        expect(fn(): object => theShelfIn(whatAStackSaysAboutAShelf(['holdings' => $rows])))
            ->toThrow(ShelfIsUnreadable::class);
    }

    foreach ([['id'], ['title'], ['medium']] as [$field]) {
        $said = oneHolding();
        unset($said[$field]);

        expect(fn(): object => theShelfIn(whatAStackSaysAboutAShelf(['holdings' => [$said]])))
            ->toThrow(ShelfIsUnreadable::class);
    }
});

it('refuses a medium this build does not know, by name', function (): void {
    // Not read as `other`. A holding the core had no better word for and a
    // stack speaking a vocabulary this release has never heard of are
    // different answers, and reading the second as the first hides it.
    expect(fn(): object => theShelfIn(whatAStackSaysAboutAShelf([
        'holdings' => [oneHolding(['medium' => 'hologram'])],
    ])))->toThrow(ShelfIsUnreadable::class, 'hologram');
});

it('G12 — the payload this suite reads a shelf from is one a stack would send', function (): void {
    // The well-formed ones only. Every other fixture here is deliberately
    // short of a field or wrong about one, which is the point of it — a
    // reader is worth no more than what it refuses.
    expect(WhatTheContractAccepts::complaintsAbout('HeldEnvelope', [
        'api_version' => 1,
        'kind' => 'held',
        'data' => whatAStackSaysAboutAShelf(['holdings' => [
            oneHolding(),
            oneHolding(['id' => 'b2', 'title' => 'A series', 'medium' => 'series']),
            oneHolding(['id' => 'c3', 'title' => 'Something else', 'medium' => 'other']),
        ]]),
    ]))->toBe([], "The payload this suite reads a shelf from is not one a stack would send.\n")
        ->and(WhatTheContractAccepts::complaintsAbout('HeldEnvelope', [
            'api_version' => 1,
            'kind' => 'held',
            'data' => whatAStackSaysAboutAShelf([
                'available' => false,
                'holdings' => [],
                'findings' => ['The media server did not answer.'],
            ]),
        ]))->toBe([], "The unreadable-shelf payload this suite reads is not one a stack would send.\n");
});
