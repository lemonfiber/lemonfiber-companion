<?php

declare(strict_types=1);

use Tests\Support\Template;

// A value reaches the glass as it was written.
//
// A component draws what it is handed with `{{ }}`, which escapes it once, and
// the native markup reads that escape back. A screen that hands it the value as
// `label="{{ $name }}"` escapes it a second time, so a name with an apostrophe
// or an ampersand in it is drawn as `&#039;` or `&amp;`, and a tap's quoted
// argument reaches the screen as something it never asked for. A component is
// handed every value bound, `:label="$name"`, and escapes it once, itself.

it('hands a component every value bound, so it is escaped once', function (): void {
    $twice = [];
    $looked = 0;

    foreach (Template::all() as $template) {
        preg_match_all('/\s[\w-]+="[^"]*\{\{[^"]*"/', $template->source, $found, PREG_OFFSET_CAPTURE);
        $looked += substr_count($template->source, '<x-');

        foreach ($found[0] as [$attribute, $offset]) {
            // The tag the attribute sits in opens at the last `<` before it; a
            // native tag's own attributes are the one escape the markup reads back.
            $opens = (int) strrpos(substr($template->source, 0, $offset), '<');

            if (str_starts_with(substr($template->source, $opens), '<x-')) {
                $twice[] = sprintf('%s:%d — %s', $template->path, $template->lineAt($offset), trim($attribute));
            }
        }
    }

    expect($looked)->toBeGreaterThan(100, 'no template uses a component, so this rule read nothing')
        ->and($twice)->toBe([], sprintf(
            "These hand a component a value echoed, which escapes it twice:\n  %s\n\nBind it instead: `:label=\"\$value\"`.\n",
            implode("\n  ", $twice),
        ));
});

// F21 — no value is written into a tap's expression.
//
// The package reads a tap as text: the method up to the first parenthesis, and
// its arguments as JSON once every single quote has become a double one. A
// value echoed into that text is read as part of the expression, so a key name
// with a quote in it closes its argument and opens another, and an ampersand
// is escaped twice and reaches the screen as `&amp;`. A call that hands a value
// is bound, `:tap="$this->tapping('choose', $form->name)"`. The one echo left
// is a component handing its own `$tap` to the native control whole.

it('writes no value into a tap\'s expression', function (): void {
    $written = [];
    $handed = 0;

    foreach (Template::all() as $template) {
        preg_match_all('/\s(@\w+|tap|change|pulled)="([^"]*\{\{[^"]*)"/', $template->source, $found, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

        foreach ($found as [[, $offset], [$attribute], [$expression]]) {
            $opens = (int) strrpos(substr($template->source, 0, $offset), '<');
            $whole = str_starts_with(substr($template->source, $opens), '<native:') && preg_match('/^\{\{ \$[\w>-]+ \}\}$/', $expression) === 1;
            $handed += $whole ? 1 : 0;

            if (! $whole) {
                $written[] = sprintf('%s:%d — %s="%s"', $template->path, $template->lineAt($offset), $attribute, $expression);
            }
        }
    }

    expect($handed)->toBeGreaterThan(5, 'no component hands its tap on, so this rule read nothing')
        ->and($written)->toBe([], sprintf(
            "These write a value into a tap's expression, where the package parses it as part of the call:\n  %s\n\n"
            . "Bind it instead: `:tap=\"\$this->tapping('method', \$value)\"`.\n",
            implode("\n  ", $written),
        ));
});
