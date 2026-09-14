<?php

declare(strict_types=1);

use Modules\Kernel\Api\HowManyLines;
use Modules\Kernel\Api\LookingFor;
use Modules\Kernel\Api\Said;
use Modules\Kernel\Api\Scrollback;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Stream;

/** The service every window below is over. */
function theServiceTalking(): ServiceId
{
    return ServiceId::called('gluetun');
}

/** One line, named so an order can be read back. */
function aLineSaying(string $text): Said
{
    return Said::whenever($text, theServiceTalking(), Stream::Stdout);
}

/** Every line of a window, in the order it holds them. */
function everyLineIn(Scrollback $scrollback): string
{
    $rows = [];

    foreach ($scrollback as $line) {
        $rows[] = $line->line();
    }

    return implode(' | ', $rows);
}

it('N2-R10 — holds the lines in the order the service wrote them', function (): void {
    // The only order that means anything: a log line is read against the line
    // before it.
    $window = Scrollback::of(
        theServiceTalking(),
        HowManyLines::of(10),
        aLineSaying('first'),
        aLineSaying('second'),
    );

    expect(everyLineIn($window))->toBe('first | second')
        ->and($window->count())->toBe(2);
});

it('N2-R10 — names the service and the bound it was given', function (): void {
    $window = Scrollback::of(theServiceTalking(), HowManyLines::of(10), aLineSaying('first'));

    expect($window->service()->named())->toBe('gluetun')
        ->and($window->asked()->figure())->toBe(10);
});

it('N2-R10 — a full window says the view stops where it was told to', function (): void {
    $window = Scrollback::of(
        theServiceTalking(),
        HowManyLines::of(2),
        aLineSaying('first'),
        aLineSaying('second'),
    );

    expect($window->isAWindow())->toBeTrue();
});

it('N2-R10 — fewer lines than the bound says the bound cut nothing', function (): void {
    // And says nothing further. It does not claim to be everything the service
    // ever wrote, because the engine keeps what it keeps.
    $window = Scrollback::of(theServiceTalking(), HowManyLines::of(10), aLineSaying('only one'));

    expect($window->isAWindow())->toBeFalse();
});

it('searching narrows the lines and leaves the claim about the edge alone', function (): void {
    // The failure this type exists to refuse. A window of three filtered to one
    // is still a window that stopped at three, and recomputing the claim from
    // the one would have the screen announce that the search covered everything
    // the service ever said.
    $window = Scrollback::of(
        theServiceTalking(),
        HowManyLines::of(3),
        aLineSaying('tunnel up'),
        aLineSaying('connection timed out'),
        aLineSaying('retrying'),
    );

    $found = $window->matching(LookingFor::text('timed'));

    expect(everyLineIn($found))->toBe('connection timed out')
        ->and($found->count())->toBe(1)
        ->and($found->howManyArrived())->toBe(3)
        ->and($found->isAWindow())->toBeTrue();
});

it('a blank search is not a search, and gives the window back whole', function (): void {
    $window = Scrollback::of(
        theServiceTalking(),
        HowManyLines::of(3),
        aLineSaying('tunnel up'),
        aLineSaying('retrying'),
    );

    $same = $window->matching(LookingFor::text('   '));

    expect($same->count())->toBe(2)
        ->and($same->lookingFor()->isSearching())->toBeFalse();
});

it('a search that matches nothing is still a window over what arrived', function (): void {
    $window = Scrollback::of(theServiceTalking(), HowManyLines::of(1), aLineSaying('tunnel up'));

    $found = $window->matching(LookingFor::text('nothing like that'));

    expect($found->count())->toBe(0)
        ->and($found->howManyArrived())->toBe(1)
        ->and($found->isAWindow())->toBeTrue()
        ->and($found->lookingFor()->typed())->toBe('nothing like that');
});

it('narrowing twice narrows the window each time, never the narrowing', function (): void {
    // `matching()` answers the window it was asked of, so a second search is
    // not a search within the first — which is what a person typing expects,
    // and what keeps a backspace from leaving lines hidden.
    $window = Scrollback::of(
        theServiceTalking(),
        HowManyLines::of(3),
        aLineSaying('tunnel up'),
        aLineSaying('tunnel down'),
    );

    expect(everyLineIn($window->matching(LookingFor::text('up'))->matching(LookingFor::text('down'))))
        ->toBe('tunnel down');
});

it('a service that said nothing is an empty window, not a missing one', function (): void {
    $window = Scrollback::of(theServiceTalking(), HowManyLines::of(10));

    expect($window->count())->toBe(0)
        ->and($window->howManyArrived())->toBe(0)
        ->and($window->isAWindow())->toBeFalse();
});

it('reads by position, whatever keys the variadic arrived with', function (): void {
    // Named arguments give a variadic string keys, and this window hands its
    // lines out again through its iterator — so the keys escape, and everything
    // downstream reads by position. The keys are what has to be read back:
    // `foreach` yields insertion order whatever they are, so a window that had
    // kept `first` and `second` iterates identically to one that reindexed, and
    // a line assertion alone cannot tell the two apart.
    $window = Scrollback::of(
        service: theServiceTalking(),
        asked: HowManyLines::of(3),
        first: aLineSaying('first'),
        second: aLineSaying('second'),
    );

    expect(array_keys(iterator_to_array($window, preserve_keys: true)))->toBe([0, 1])
        ->and(everyLineIn($window))->toBe('first | second');
});

it('a blank search selects every line, which is what exempts the early return', function (): void {
    // `matching()` carries a mutation exemption, and this is the assertion that
    // ends it.
    //
    // The early return answers a blank search with the window whole. Removing
    // it drops through to the loop below, which asks each line whether it holds
    // what is being looked for — and `mb_stripos($line, '')` is 0, so every
    // line says yes. The two arms then build the same window from the same
    // lines, with the same `LookingFor`: every non-searching one holds the
    // empty string, so `nothing()` and a blank `text()` are the same value.
    //
    // Nothing can tell them apart while that is true. The day `holds()` answers
    // a blank search with `false`, it stops being true and the early return
    // becomes the only thing keeping the window whole.
    foreach ([LookingFor::nothing(), LookingFor::text('   ')] as $blank) {
        expect($blank->isSearching())->toBeFalse()
            ->and($blank->typed())->toBe('', 'a non-searching LookingFor now holds text, so the early return in `Scrollback::matching()` is observable: it answers `nothing()` where the loop answers what it was given. Delete the `@pest-mutate-ignore: RemoveEarlyReturn` beside it and assert which of the two a blank search reports.')
            ->and(aLineSaying('nothing like that')->holds($blank))
            ->toBeTrue('a blank search no longer selects every line, so the early return in `Scrollback::matching()` is the only thing that gives the window back whole. Delete the `@pest-mutate-ignore: RemoveEarlyReturn` beside it and assert the count it returns.');
    }
});
