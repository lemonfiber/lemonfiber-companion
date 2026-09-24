<?php

declare(strict_types=1);

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\WireField;
use Tests\Support\Tree;
use Tests\Support\WhatTheReadersRead;

// Every name in `WireField`, and in each envelope's own enum under `Fields`, is a
// name a reader reads, and sits in the enum it belongs in.
//
// The enum exists because two readers spell the same eight field names, and
// spelled as literals nothing held them in agreement. Naming them once fixes
// that and opens a second gap in the same place: a case nobody reads is a field
// this app believes it reads and does not.
//
// That is not a hypothetical. The reader that keyed on whether a `code` key was
// present read `unverified` and `skipped` as passing checks for as long as it
// stood, because neither carries a code — the fields were on the wire, had
// names, and were read by nothing. A case sitting here with no reader is
// exactly what that looked like from the inside.
//
// Pointed the other way, `EveryDerivedKeyResolvesTest` and the catalogue mirror
// do the same pair of jobs for the words on screens. This is the wire's half.

/**
 * The files that read an envelope, which is where a field name may be used.
 *
 * @return list<string>
 */
function readersOfTheWire(): array
{
    // The whole module rather than four names. A list written out here is a
    // list that goes stale the day a fifth reader arrives — silently, and in
    // the direction that matters: a field read only by the unlisted file reads
    // as a field nobody reads, and the rule asks for the case to be deleted.
    // `Households` and `HouseholdIsUnreadable` were exactly that on the day
    // they were written.
    //
    // Reading every file in the module over-approximates, which is the safe
    // direction for this rule: it refuses a *case*, so seeing more files means
    // refusing fewer cases rather than more.
    return Tree::filesUnder(Tree::at('app-modules/sdk/src'), '.php');
}

/**
 * Every enum that names wire fields, by short name.
 *
 * Read off the directory rather than listed, so an enum added for a new
 * envelope is one these rules cover without an edit.
 *
 * @return array<string, ReflectionEnum<UnitEnum>> short class name => the enum
 */
function everyWireFieldEnum(): array
{
    $enums = [];
    $files = [Tree::at('app-modules/sdk/src/Api/WireField.php'), ...Tree::filesUnder(Tree::at('app-modules/sdk/src/Api/Fields'), '.php')];

    foreach ($files as $file) {
        preg_match('/^enum (\w+): string implements NamesAWireField/m', (string) file_get_contents($file), $named);
        $name = $named === [] || $named[1] === 'WireField' ? WireField::class : sprintf('Modules\Sdk\Api\Fields\%s', $named[1]);

        if ($named !== [] && enum_exists($name)) {
            $enums[$named[1]] = new ReflectionEnum($name);
        }
    }

    return $enums;
}

/**
 * Every enum that names wire fields, by short name, each with its cases.
 *
 * @return array<string, array<string, string>> short class name => case name => wire word
 */
function everyWireVocabulary(): array
{
    $vocabularies = [];

    foreach (everyWireFieldEnum() as $enum => $class) {
        $cases = [];

        foreach ($class->getCases() as $case) {
            $value = $case->getValue();

            if ($value instanceof NamesAWireField) {
                $cases[$case->getName()] = $value->value;
            }
        }

        $vocabularies[$enum] = $cases;
    }

    return $vocabularies;
}

/**
 * Which envelopes each wire word is read out of, from the paths the readers are followed to.
 *
 * @return array<string, list<string>> wire word => envelope short names, e.g. `Space`
 */
function whereEachWordIsRead(): array
{
    $read = [];

    foreach (WhatTheReadersRead::paths() as $path) {
        [$envelope, $rest] = explode('.', $path, 2);

        $words = preg_split('/\.|\[\]\.?/', $rest);

        foreach ($words === false ? [] : $words as $word) {
            if ($word !== '') {
                $read[$word][str_replace('Envelope', '', $envelope)] = true;
            }
        }
    }

    $answer = [];

    foreach ($read as $word => $envelopes) {
        $names = array_keys($envelopes);
        sort($names);
        $answer[$word] = $names;
    }

    return $answer;
}

it('finds the vocabularies it holds to these rules', function (): void {
    // A rule that has stopped finding the enums reports no violations, and no
    // violations is what compliance looks like.
    expect(everyWireVocabulary())->toHaveKey('WireField')
        ->and(count(everyWireVocabulary()))->toBeGreaterThan(1);
});

it('reads every field it names, so a case is never a field nobody reads', function (): void {
    $read = '';

    foreach (readersOfTheWire() as $reader) {
        $read .= file_get_contents($reader);
    }

    foreach (everyWireVocabulary() as $enum => $cases) {
        foreach ($cases as $case => $word) {
            expect(str_contains($read, sprintf('%s::%s', $enum, $case)))->toBeTrue(sprintf(
                '`%s::%s` names `%s` on the wire and no reader reads it. Either a reader '
                . 'dropped the field — which is how `unverified` came to read as a passing check — '
                . 'or the case describes something this app does not read and should go.',
                $enum,
                $case,
                $word,
            ));
        }
    }
});

it('names each field once, so two cases cannot describe the same wire word', function (): void {
    $seen = [];

    foreach (everyWireVocabulary() as $cases) {
        foreach ($cases as $word) {
            expect($seen)->not->toContain($word, sprintf(
                '`%s` is named by two cases. The wire has one field by that name, and a reader '
                . 'picking either of them would be right — until somebody renamed one.',
                $word,
            ));

            $seen[] = $word;
        }
    }
});

it('names each envelope enum for an envelope the contract has', function (): void {
    foreach (array_keys(everyWireVocabulary()) as $enum) {
        if ($enum === 'WireField') {
            continue;
        }

        expect(class_exists(sprintf('Lemonfiber\Sdk\Generated\%sEnvelope', substr($enum, 0, -strlen('Field')))))
            ->toBeTrue(sprintf('`%s` is named for an envelope the SDK does not generate.', $enum));
    }
});

it('keeps a word in the shared enum only where more than one envelope is read for it', function (): void {
    // The rule that makes *which enum* a question with one answer. A word read
    // out of one envelope belongs to that envelope, and naming it in the shared
    // set says it is shared when it is not. `data` is the payload itself, which
    // every envelope has and no path names.
    $read = whereEachWordIsRead();
    $misplaced = [];

    foreach (everyWireVocabulary()['WireField'] as $case => $word) {
        if ($word !== WireField::Data->value && count($read[$word] ?? []) < 2) {
            $misplaced[] = sprintf('WireField::%s (`%s`) is read out of %s', $case, $word, implode(', ', $read[$word] ?? ['nothing']));
        }
    }

    expect($misplaced)->toBe([], sprintf(
        "These are in the shared enum and read out of fewer than two envelopes:\n  %s\n\n"
        . "Move each to its envelope's enum under `Fields`.\n",
        implode("\n  ", $misplaced),
    ));
});

it('keeps a word in an envelope\'s enum only where that envelope is the one it is read out of', function (): void {
    // The other half. A word read out of a second envelope is shared, and
    // reaching for it through the first envelope's enum hides that from the
    // next reader of either payload.
    $read = whereEachWordIsRead();
    $misplaced = [];

    foreach (everyWireVocabulary() as $enum => $cases) {
        if ($enum === 'WireField') {
            continue;
        }

        $envelope = substr($enum, 0, -strlen('Field'));

        foreach ($cases as $case => $word) {
            $elsewhere = array_values(array_diff($read[$word] ?? [], [$envelope]));

            if ($elsewhere !== []) {
                $misplaced[] = sprintf('%s::%s (`%s`) is also read out of %s', $enum, $case, $word, implode(', ', $elsewhere));
            }
        }
    }

    expect($misplaced)->toBe([], sprintf(
        "These are in one envelope's enum and read out of another as well:\n  %s\n\n"
        . "Move each to `WireField`, the words more than one envelope carries.\n",
        implode("\n  ", $misplaced),
    ));
});
