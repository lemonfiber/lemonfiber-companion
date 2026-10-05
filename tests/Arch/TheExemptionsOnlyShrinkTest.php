<?php

declare(strict_types=1);

use Lemonfiber\Companion\PHPStan\Rules\ClosedSets\OutsideVocabularies;
use Lemonfiber\Companion\PHPStan\Rules\OneHome\Coincidences;
use Tests\Support\Tree;

// D8 and D9 each carry a list of what they leave alone, and a list like that
// is where a rule goes to stop meaning anything: every red run can be made
// green by one more line in it. So each list is held to a ceiling that may
// fall and may not rise, and every entry must still name something. A fixed
// site that leaves its entry behind would exempt whatever comes to take its
// name.
//
// Lower the ceiling in the change that empties an entry.

// D9 — one value has one home
it('lets the list of constants allowed a second home only shrink', function (): void {
    $ceiling = 28;

    expect(count(Coincidences::named()))->toBeLessThanOrEqual($ceiling, sprintf(
        "D9 leaves %d constants alone, and the ceiling is %d.\n"
        . 'A value two classes declare has one home; give it one, rather than a line in '
        . "Coincidences (D9).\n",
        count(Coincidences::named()),
        $ceiling,
    ));
});

// D8 — a closed set of strings is an enum
it('lets the list of files allowed a set of strings instead of an enum only shrink', function (): void {
    $ceiling = 1;

    expect(count(OutsideVocabularies::IN))->toBeLessThanOrEqual($ceiling, sprintf(
        "D8 leaves %d files alone, and the ceiling is %d.\n"
        . 'A closed set of strings is an enum; make it one, rather than a line in '
        . "OutsideVocabularies (D8).\n",
        count(OutsideVocabularies::IN),
        $ceiling,
    ));
});

it('leaves alone only constants that are still declared', function (): void {
    $gone = [];

    foreach (Coincidences::named() as $constant) {
        [$class, $name] = explode('::', $constant, 2);

        $declared = (class_exists($class) || trait_exists($class)) && new ReflectionClass($class)->hasConstant($name);

        if (! $declared) {
            $gone[] = $constant;
        }
    }

    expect($gone)->toBe([], sprintf(
        "Coincidences names constants nothing declares:\n  %s\n\nTake each one off the list (D9).",
        implode("\n  ", $gone),
    ));
});

it('leaves alone only files that are there', function (): void {
    $gone = [];

    foreach (array_keys(OutsideVocabularies::IN) as $path) {
        if (! is_file(Tree::at($path))) {
            $gone[] = $path;
        }
    }

    expect($gone)->toBe([], sprintf(
        "OutsideVocabularies names files that are not there:\n  %s\n\nTake each one off the list (D8).",
        implode("\n  ", $gone),
    ));
});

it('says what each constant it leaves alone means, and whose words each file reads', function (): void {
    $unsaid = [];

    foreach (Coincidences::OF as $class => $constants) {
        foreach ($constants as $constant => $meaning) {
            if (trim($meaning) === '') {
                $unsaid[] = sprintf('%s::%s', $class, $constant);
            }
        }
    }

    foreach (OutsideVocabularies::IN as $file => $whose) {
        if (trim($whose) === '') {
            $unsaid[] = $file;
        }
    }

    expect($unsaid)->toBe([], sprintf(
        "These are left alone without saying why:\n  %s\n\nSay what each means (D8, D9).",
        implode("\n  ", $unsaid),
    ));
});
