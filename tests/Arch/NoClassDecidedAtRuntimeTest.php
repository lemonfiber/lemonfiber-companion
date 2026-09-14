<?php

declare(strict_types=1);

use Tests\Support\Template;

// F9 — a class decided at runtime is a class nothing reads.
//
// Every rule about a class list reads `Template::classStrings()`, and that
// function drops any token holding a runtime expression rather than guessing at
// it. For `bg-{{ $tone }}` that is the right answer: with the echo deleted the
// token is `bg-`, an unknown utility nobody wrote. For
// `class="{{ $open ? 'bg-theme-accent' : '' }}"` the token that goes is the
// whole attribute, and the class names inside it are then read by nothing at
// all.
//
// Three rules go quiet together, because all three are handed that one
// function's answer. F3 stops seeing an unknown utility, DES-R24 stops seeing a
// literal colour, and DES-R15 stops seeing the accent set as text. A template
// whose ternaries hold `bg-theme-accnt`, `bg-red-500` and `text-theme-accent`
// passes every rule in `tests/Templates` — and EDGE parses each of those in
// turn, finds it means nothing, and drops it. No error, no warning, no failed
// build: a screen that renders wrong on a device and says nothing about why.
// That is the exact failure the vocabulary check exists to catch, which is why
// the hole in it is a rule rather than a note.
//
// So a class list is written out, and a state that changes is drawn rather than
// coloured — `how-this-stack-is.blade.php` puts the selected bar in an element
// of its own under an `@if`, with a static class the checks can read. A
// directive inside the attribute goes the same way: `@if($a)bg-theme-accnt@endif`
// is one token holding an `@`, so it is dropped whole and takes the class with
// it.
//
// The two forms that never reach `classStrings()` at all are refused here too.
// A bound `:class` or `native:class` is not read, because its value is PHP
// rather than a class list, and `@class([...])` is not an attribute for the
// expression to match. A class name hidden in either is hidden the same way and
// costs the same thing.

it('F9 — no class list in a template is decided at runtime', function (): void {
    $refused = [
        // The same attribute `classStrings()` reads, narrowed to the values it
        // cannot hand on: an echo, a raw echo, or a directive written inside
        // the quotes.
        '/(?<![:\w-])class\s*=\s*(?:"[^"]*(?:\{\{|\{!!|@)[^"]*"|\'[^\']*(?:\{\{|\{!!|@)[^\']*\')/s'
            => 'assembles its class list at runtime, so nothing reads the classes in it',
        '/[\s"\'](?:[a-z][a-z0-9-]*)?:class\s*=/i'
            => 'binds its class list, and a bound value is PHP rather than classes',
        '/@class\s*\(/'
            => 'builds its class list with `@class`, which is not an attribute anything reads',
    ];

    $offenders = [];

    foreach (Template::all() as $template) {
        foreach ($refused as $pattern => $what) {
            preg_match_all($pattern, $template->source, $found, PREG_OFFSET_CAPTURE);

            foreach ($found[0] as $match) {
                $offenders[] = sprintf(
                    '%s:%d %s — `%s`',
                    $template->path,
                    $template->lineAt($match[1]),
                    $what,
                    trim($match[0]),
                );
            }
        }
    }

    expect($offenders)->toBe([], sprintf(
        "These put a class name where nothing can check it:\n  %s\n\n"
        . 'A class token holding a runtime expression is dropped unread — it has to be, '
        . 'because the expression deleted leaves `bg-` and nobody wrote that. What goes '
        . 'with it is every class name inside, so F3 stops seeing an unknown utility, '
        . 'DES-R24 stops seeing a literal colour and DES-R15 stops seeing the accent set '
        . "as text, all at once and all silently.\n"
        . 'EDGE does not object either: it parses the class, finds it means nothing and '
        . 'discards it. There is no error, no warning and no failed build — a typo in '
        . "there renders nothing on a device, and nothing anywhere says so.\n"
        . 'Write the class list out, and draw a state that changes rather than colouring '
        . 'it: put the changing part in its own element under an `@if`, with a static '
        . 'class the checks can read (F9).',
        implode("\n  ", $offenders),
    ));
});
