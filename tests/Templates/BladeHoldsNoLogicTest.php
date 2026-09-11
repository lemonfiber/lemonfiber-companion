<?php

declare(strict_types=1);

use Tests\Support\Edge;
use Tests\Support\Template;

// F3 — a Blade view reads from its component and nothing else.
//
// Logic in a template is unreachable by every analyser in this repository:
// PHPStan does not read Blade, the architecture rules reflect over classes, and
// a template is neither. So a decision moved into a view leaves the governed
// part of the codebase entirely, which is why the cure is always the same —
// put it in the presenter, where F2 keeps it pure and where the mutation floor
// actually measures it.
//
// DES-R24 rides along here because a literal colour is the same kind of
// mistake: a value written into the class position that no rule outside this
// suite can see.

$templates = Template::all();

foreach ($templates as $template) {
    it(sprintf('F3 — %s holds no logic', $template->path), function () use ($template): void {
        // `<?php` is matched without its opening angle bracket so that this
        // file does not contain the token it refuses.
        $refused = [
            '@php' => 'runs PHP in a view',
            '?' . 'php' => 'opens a PHP block in a view',
            'dd(' => 'leaves a debugging call in a view',
            'dump(' => 'leaves a debugging call in a view',
            'style=' => 'styles inline instead of with EDGE classes',
        ];

        $found = [];

        foreach ($refused as $token => $what) {
            if (str_contains($template->source, $token)) {
                $found[] = sprintf('%s %s (%s)', $template->path, $what, $token);
            }
        }

        expect($found)->toBe([], sprintf(
            "These put something in a view that no analyser can read:\n  %s\n\n"
            . 'PHPStan does not read Blade and the architecture rules reflect over '
            . 'classes, so a decision here is outside everything that governs the rest of '
            . 'the codebase. Move it to the presenter, which is pure by rule and is where '
            . "the mutation floor measures it.\n`style=` is the same problem for "
            . 'appearance: an inline style is invisible to the class checks above and to '
            . 'the theme (F3).',
            implode("\n  ", $found),
        ));
    });

    it(sprintf('F3 — %s opens no web view (ADR-0017)', $template->path), function () use ($template): void {
        $found = array_values(array_filter(
            $template->nativeTags(),
            static fn(string $tag): bool => $tag === 'webview',
        ));

        expect($found)->toBe([], sprintf(
            "%s renders a web view.\n\n"
            . 'This is the one decision ADR-0017 exists to make, and the element really '
            . 'is there in the package — `webview` is a collector builtin that renders its '
            . 'slot as inline HTML, so nothing else in the toolchain would stop it. A '
            . 'fourth surface that is a browser is not a fourth surface. An exception has '
            . 'to arrive as a spec change rather than a pull request (F3, ADR-0017).',
            $template->path,
        ));
    });

    it(sprintf('F3 — %s names no literal colour (DES-R24)', $template->path), function () use ($template): void {
        $literals = [];

        foreach ($template->classStrings() as $classString) {
            $classes = preg_split('/\s+/', trim($classString), -1, PREG_SPLIT_NO_EMPTY);

            foreach ($classes === false ? [] : $classes as $class) {
                $value = colourValueIn($class);

                if ($value !== '' && Edge::isLiteralColour($value)) {
                    $literals[] = $class;
                }
            }
        }

        sort($literals);

        expect(array_values(array_unique($literals)))->toBe([], sprintf(
            "These write a colour instead of naming one:\n  %s\n\n"
            . 'A literal ignores the reader\'s light, dark and contrast setting, so it is '
            . 'correct on the device it was written on and wrong on somebody else\'s. The '
            . 'theme tokens carry a light value and a dark companion, and the parser picks '
            . "between them at render.\nUse `bg-theme-*` and `text-theme-*` (DES-R24).",
            implode("\n  ", array_values(array_unique($literals))),
        ));
    });
}

/**
 * The colour-bearing part of a utility class, or an empty string.
 *
 * The prefixes are the ones that take a colour; what counts as a colour after
 * the prefix is the parser's own grammar rather than a palette copied here, so
 * `red-500`, `#B91C1C` and `red-300/50` are all recognised and `2xl` is not.
 *
 * `transparent` is permitted by name: it has no light value and no dark
 * companion, so there is no reader setting for it to be wrong about.
 */
function colourValueIn(string $class): string
{
    // A variant prefix — `dark:`, `ios:`, `hover:` — sits in front of the
    // utility and is not part of the colour.
    $tail = strrchr($class, ':');
    $utility = $tail === false ? $class : substr($tail, 1);

    foreach (['bg-', 'text-', 'border-', 'from-', 'via-', 'to-', 'fill-', 'stroke-', 'shadow-'] as $prefix) {
        if (! str_starts_with($utility, $prefix)) {
            continue;
        }

        $value = substr($utility, strlen($prefix));

        if ($value === 'transparent' || str_starts_with($value, 'theme-')) {
            return '';
        }

        // An arbitrary value is written in brackets; the parser reads what is
        // inside them.
        return trim($value, '[]');
    }

    return '';
}
