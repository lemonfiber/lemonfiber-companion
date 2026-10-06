<?php

declare(strict_types=1);

use Tests\Support\OurCode;

// P5 — a field only a trait reads is protected, never private: an analyser
// reading the class alone sees a private field nothing in it reads.
//
// The screens' navigation traits read the screen's own `$around`, and a
// screen that reads it nowhere else and holds it private looks to such an
// analyser like a field with no reader. Protected says the field is there to
// be read beside the class, which is what it is.

/**
 * The fields each trait in our code reads off the class using it, by the trait's short name.
 *
 * @return array<string, list<string>>
 */
function theFieldsEachTraitReads(): array
{
    $reads = [];

    foreach (OurCode::sourceFiles() as $file) {
        $code = (string) file_get_contents($file);

        if (preg_match('/^trait (\w+)/m', $code, $trait) !== 1) {
            continue;
        }

        preg_match_all('/\$this->(\w+)\b(?!\s*\()/', $code, $fields);
        $reads[$trait[1]] = array_values(array_unique($fields[1]));
    }

    return $reads;
}

it('holds a field only a trait reads as protected, never private', function (): void {
    $traits = theFieldsEachTraitReads();
    $hidden = [];

    expect($traits)->not->toBeEmpty('no trait was found, so this read nothing');

    foreach (OurCode::sourceFiles() as $file) {
        $code = (string) file_get_contents($file);

        if (preg_match('/^(?:final )?(?:readonly )?class \w+/m', $code) !== 1) {
            continue;
        }

        preg_match_all('/^\s+use (\w+);/m', $code, $used);

        foreach (array_intersect($used[1], array_keys($traits)) as $trait) {
            foreach ($traits[$trait] as $field) {
                $private = preg_match(sprintf('/private (?:readonly )?\??[\w\\\\]+ \$%s\b/', $field), $code) === 1;
                $readHere = preg_match(sprintf('/\$this->%s\b(?!\s*\()/', $field), $code) === 1;

                if ($private && ! $readHere) {
                    $hidden[] = sprintf('%s holds $%s private, and only %s reads it', basename($file), $field, $trait);
                }
            }
        }
    }

    expect($hidden)->toBe([], sprintf(
        "These fields are private and read only by a trait:\n  %s\n\nHold each as protected.\n",
        implode("\n  ", $hidden),
    ));
});
