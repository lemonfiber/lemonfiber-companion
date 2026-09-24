<?php

declare(strict_types=1);

use Tests\Support\Template;

// F16 — the column a screen's content sits in is written in one template.
//
// Every screen puts what it says in the same padded column, and the column's
// class list is the platform mapping decided once: its width, its gap and its
// padding. The `content` component holds it, and every screen puts its content
// in that component's slot rather than writing the column out.
//
// `ContentTest` draws the component and asserts the slot is inside the column.
//
// This refuses the column written out anywhere else, whatever order its classes
// are written in.

/** The one template the column is written in. */
const WHERE_THE_CONTENT_OPENS = 'app-modules/operator/resources/views/components/content.blade.php';

/** The classes that make a column the one a screen's content sits in. */
const THE_CONTENT_COLUMN = ['gap-4', 'px-6', 'py-4', 'w-full'];

/**
 * Where a template writes the content column out, by line.
 *
 * Read off the elements rather than matched as one string, so the same four
 * classes in another order or with other spacing are the same column.
 *
 * @return list<int>
 */
function whereTheContentColumnIsWritten(Template $template): array
{
    $lines = [];

    foreach ($template->elements() as $element) {
        if ($element['tag'] !== 'column') {
            continue;
        }

        if (preg_match('/(?<![:\w-])class\s*=\s*"([^"]*)"/', $element['attributes'], $class) !== 1) {
            continue;
        }

        $classes = preg_split('/\s+/', trim($class[1]), flags: PREG_SPLIT_NO_EMPTY);

        if ($classes === false) {
            continue;
        }

        sort($classes);

        if ($classes === THE_CONTENT_COLUMN) {
            $lines[] = $element['line'];
        }
    }

    return $lines;
}

it('F16 — finds the content column where it is written', function (): void {
    // The floor. A reading that found the column nowhere, including in the
    // component, would pass the rule below having read nothing.
    $holding = array_filter(
        Template::all(),
        static fn(Template $template): bool => $template->path === WHERE_THE_CONTENT_OPENS
            && whereTheContentColumnIsWritten($template) !== [],
    );

    expect($holding)->not->toBe([]);
});

it('F16 — no template but the component writes the content column out', function (): void {
    $offenders = [];

    foreach (Template::all() as $template) {
        if ($template->path === WHERE_THE_CONTENT_OPENS) {
            continue;
        }

        foreach (whereTheContentColumnIsWritten($template) as $line) {
            $offenders[] = sprintf('%s:%d', $template->path, $line);
        }
    }

    expect($offenders)->toBe([], sprintf(
        "These write out the column a screen's content sits in:\n  %s\n\n"
        . 'The column is written once, in the `content` component. Put what the '
        . 'column held in `<x-operator::content>` instead (F16).',
        implode("\n  ", $offenders),
    ));
});
