<?php

declare(strict_types=1);

use PhpParser\Node\Expr\CallLike;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;
use Tests\Support\Tree;
use Tests\Support\WhereTheReadingStops;

// The analysis behind `WhatTheContractCarriesThatNothingReadsTest` makes a
// negative claim: that nothing in this app reads a given wire field. A negative
// claim is worth exactly what the analysis can see, so the edge of what it can
// see has to be a thing that fails loudly rather than a thing that returns
// nothing.
//
// `Foo::bar(...)` is the case that found this. It parses as a call and is not
// one — it builds a closure and defers the call somewhere the analysis does not
// go. php-parser says so by asserting inside `getArgs()`, which reaches a
// reader as `assert(!$this->isFirstClassCallable())` from inside a vendor
// directory: true, unhelpful, and easy to make go away with a guard that reads
// it as a call with no arguments. That guard is the bug, not the fix. A field
// taken through a deferred call would then be reported as read by nothing.
//
// So this shows the refusal, and then holds it to being the only one.

/** One expression, parsed, as the analysis meets it. */
function theCallIn(string $expression): CallLike
{
    $parsed = new ParserFactory()->createForNewestSupportedVersion()
        ->parse(sprintf('<?php %s;', $expression));

    $found = new NodeFinder()->findFirstInstanceOf($parsed ?? [], CallLike::class);

    return $found instanceof CallLike
        ? $found
        : throw new RuntimeException('The fixture parsed to no call at all.');
}

it('Q-R66 — refuses a call it cannot follow, and says which one', function (): void {
    expect(fn(): array => WhereTheReadingStops::theArgumentsOf(
        theCallIn('self::permanent(...)'),
        'following a reader to what it reads',
    ))->toThrow(RuntimeException::class, 'permanent');
});

it('Q-R66 — the refusal says what to do about it', function (): void {
    // A gate that refuses without a remedy is a gate somebody routes around,
    // which is how the guard this exists to prevent gets written in the first
    // place. Both ways out are named: call it directly, or teach the reading.
    // Caught rather than asserted through `toThrow`, since what is being held
    // to is the whole of what the refusal says. A run where nothing throws
    // leaves this empty, and every expectation below fails on it.
    $said = '';

    try {
        WhereTheReadingStops::theArgumentsOf(
            theCallIn('$this->permanent(...)'),
            'reading a presence check',
        );
    } catch (RuntimeException $refused) {
        $said = $refused->getMessage();
    }

    expect($said)
        ->toContain('reading a presence check')
        ->toContain('read by nothing')
        ->toContain('Call it directly')
        ->toContain(WhereTheReadingStops::class);
});

it('hands over the arguments of a call that is one', function (): void {
    // The ordinary path, asserted so the refusal above cannot be met by a
    // helper that refuses everything.
    $args = WhereTheReadingStops::theArgumentsOf(
        theCallIn('array_key_exists(WireField::Service->value, $said)'),
        'reading a presence check',
    );

    expect($args)->toHaveCount(2);
});

it('is the only place the arguments of a call are taken', function (): void {
    // What makes this an edge rather than three patches. Three guards is three
    // places to forget and three private guesses at where the analysis stops;
    // one is a thing a reader can find and a reviewer can argue with.
    $reaching = [];

    foreach (Tree::filesUnder(Tree::at('tests/Support'), '.php') as $file) {
        if (str_ends_with($file, 'WhereTheReadingStops.php')) {
            continue;
        }

        if (str_contains((string) file_get_contents($file), '->getArgs()')) {
            $reaching[] = $file;
        }
    }

    expect($reaching)->toBe([], sprintf(
        "These take a call's arguments without going through the one place that says "
        . "what the reading cannot follow:\n  %s\n\nRoute them through "
        . "`WhereTheReadingStops::theArgumentsOf()`, so a construct the analysis cannot "
        . "see is refused everywhere rather than in two places out of three.\n",
        implode("\n  ", $reaching),
    ));
});
