<?php

declare(strict_types=1);

use Modules\Kernel\Api\Holding;
use Modules\Kernel\Api\HoldingId;
use Modules\Kernel\Api\HoldingIsUnnamed;
use Modules\Kernel\Api\Medium;
use Modules\Kernel\Api\Shelf;
use Modules\Kernel\Api\WhenItCameOut;

/** One thing a member may watch. */
function aHolding(string $id = 'a1', string $titled = 'A film'): Holding
{
    return Holding::of(HoldingId::called($id), $titled, Medium::Film, WhenItCameOut::in(1999));
}

it('carries what the core said about one holding', function (): void {
    $holding = aHolding();

    expect($holding->id()->named())->toBe('a1')
        ->and($holding->titled())->toBe('A film')
        ->and($holding->medium())->toBe(Medium::Film);
});

it('keeps an identifier as the core spelled it, less the space around it', function (): void {
    // Carried exactly, for the reason a service's name is: it is what a
    // holding is asked for by rather than a label, so title-casing it or
    // trimming its insides would make it a word that works nowhere else.
    expect(HoldingId::called('  a1  ')->named())->toBe('a1');
});

it('refuses a holding with nothing to identify it by', function (): void {
    // A row nothing can be done with rather than a row missing a label.
    expect(fn(): object => HoldingId::called('   '))->toThrow(HoldingIsUnnamed::class)
        ->and(fn(): object => HoldingId::called(''))->toThrow(HoldingIsUnnamed::class);
});

it('C2 — says whether the core dated a holding rather than answering with nothing', function (): void {
    // A plain nullable cannot say which of *nobody dated this* and *this is
    // the year* it means, and the guess that gets made is a zero printed
    // beside a title.
    $dated = WhenItCameOut::in(1999)->either(
        dated: static fn(int $year): ArrayObject => new ArrayObject([(string) $year]),
        unstated: static fn(): ArrayObject => new ArrayObject(['undated']),
    )->getArrayCopy();

    $undated = WhenItCameOut::unstated()->either(
        dated: static fn(int $year): ArrayObject => new ArrayObject([(string) $year]),
        unstated: static fn(): ArrayObject => new ArrayObject(['undated']),
    )->getArrayCopy();

    expect($dated)->toBe(['1999'])->and($undated)->toBe(['undated']);
});

it('D1 — hands out a shelf in the order the core listed it', function (): void {
    // The order is part of what the collection carries: a shelf arrives
    // ordered by whoever built it, and re-sorting here would be this app
    // deciding what a member sees first.
    $shelf = Shelf::of(aHolding('a1', 'First'), aHolding('b2', 'Second'));

    $titles = [];

    foreach ($shelf as $holding) {
        $titles[] = $holding->titled();
    }

    expect($titles)->toBe(['First', 'Second'])
        ->and($shelf->count())->toBe(2)
        ->and($shelf->isEmpty())->toBeFalse();
});

it('reads an empty shelf as an answer rather than as a missing one', function (): void {
    expect(Shelf::none()->isEmpty())->toBeTrue()
        ->and(Shelf::none()->count())->toBe(0);
});

it('hands out a list however it was built', function (): void {
    // A PHP variadic is not a list. Named arguments carry their names through
    // as keys, so a collection that took what arrived would hand out a map —
    // and a template indexing `[0]` would find nothing while `count()` said
    // there was something there.
    $shelf = Shelf::of(holdings: aHolding());

    foreach ($shelf as $at => $holding) {
        expect($at)->toBe(0);
    }

    expect($shelf->count())->toBe(1);
});

it('L1 — names the key a screen shows each medium under', function (): void {
    expect(Medium::Film->saidOnTheScreen())->toBe('household.medium.film')
        ->and(Medium::Series->saidOnTheScreen())->toBe('household.medium.series')
        ->and(Medium::Other->saidOnTheScreen())->toBe('household.medium.other');
});
