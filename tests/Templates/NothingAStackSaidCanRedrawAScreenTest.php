<?php

declare(strict_types=1);

use Tests\Support\Template;

// Text this product did not author cannot alter the surface showing it.
//
// The requirement is written for terminals and then says *on any surface that
// shows it*, which is this one. A phone has no escape sequences, so the same
// harm arrives by the other road: a value echoed without escaping is markup,
// and markup is how a screen is built here.
//
// This app shows a great deal of text it did not write. `WhatThisServiceSaid`
// renders the scrollback of whatever a household runs — Sonarr, Jellyfin, a
// container somebody added last week — and a log line is the least trustworthy
// string in the product. It is also the one an operator most needs to read
// literally: a line that drew something instead of saying it has lied about
// what a service reported, on the screen an operator opened to find out.
//
// **The vector is `{!! !!}` and there is nothing else like it.** `{{ }}` runs
// `e()`, so a line carrying a tag arrives as the characters of a tag; an
// attribute built from `{{ }}` is escaped the same way before the component
// bag ever sees it. Raw echo is the one construct that skips that, and it skips
// it completely.
//
// **What this does not check**, because a rule is worth what it enforces and
// not what it is named after: it does not know which values came from a stack.
// It refuses raw echo everywhere rather than raw echo of a log line, which is
// the stronger rule and the only one a text search can make true. A template
// that genuinely needs unescaped markup is a conversation, not an exception.

it('G3-R15 — no template echoes a value without escaping it', function (): void {
    $raw = [];

    foreach (Template::all() as $template) {
        // The line as well as the file: a template is long and the reader needs
        // to find the echo, not the template.
        foreach (explode("\n", $template->source) as $number => $line) {
            if (str_contains($line, '{!!')) {
                $raw[] = sprintf('%s:%d %s', $template->path, $number + 1, trim($line));
            }
        }
    }

    sort($raw);

    expect($raw)->toBe([], sprintf(
        "These echo a value without escaping it:\n  %s\n\n"
        . 'A screen here is built from markup, so an unescaped value is markup — and the values '
        . 'this app shows include the scrollback of every service a household runs. A log line '
        . 'that draws something instead of saying it has lied about what a service reported, on '
        . "the screen an operator opened to find out (G3-R15).\n\n"
        . 'Use `{{ }}`. If the value really is markup this product authored, it belongs in a '
        . 'component rather than in a string.',
        implode("\n  ", $raw),
    ));
});
