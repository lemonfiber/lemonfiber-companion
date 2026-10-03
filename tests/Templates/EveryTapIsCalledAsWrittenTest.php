<?php

declare(strict_types=1);

use Tests\Support\Template;

// A control's tap reaches the screen as the call it was written as.
//
// A component hands its `tap` on to the native control as `@tap="{{ $tap }}"`
// or `@change="{{ $tap }}"`, which escapes it once, and the native markup reads
// that escape back. A screen that passes the call in as `tap="{{ $call }}"`
// escapes it a second time, so a quoted argument reaches the screen as
// `&#039;` and the tap calls nothing: the chip lights up, and the screen
// never hears it. A call built in PHP is handed over bound, `:tap="$call"`,
// and escaped once, by the component.

it('hands a component its tap bound, so a quoted argument is escaped once', function (): void {
    $twice = [];
    $looked = 0;

    foreach (Template::all() as $template) {
        preg_match_all('/\stap="\{\{[^"]*"/', $template->source, $found, PREG_OFFSET_CAPTURE);
        $looked += substr_count($template->source, 'tap=');

        foreach ($found[0] as [$attribute, $offset]) {
            // The tag the attribute sits in opens at the last `<` before it; a
            // native tag's own `@tap` is the one escape the markup reads back.
            $opens = (int) strrpos(substr($template->source, 0, $offset), '<');

            if (str_starts_with(substr($template->source, $opens), '<x-')) {
                $twice[] = sprintf('%s:%d — %s', $template->path, $template->lineAt($offset), trim($attribute));
            }
        }
    }

    expect($looked)->toBeGreaterThan(10, 'no template hands a component a tap, so this rule read nothing')
        ->and($twice)->toBe([], sprintf(
            "These hand a component its tap echoed, which escapes it twice:\n  %s\n\nWrite `:tap=\"\$call\"` instead.\n",
            implode("\n  ", $twice),
        ));
});
