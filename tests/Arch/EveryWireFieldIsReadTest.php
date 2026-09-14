<?php

declare(strict_types=1);

use Modules\Sdk\Api\WireField;
use Tests\Support\Tree;

// Every name in `WireField` is a name a reader reads.
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

it('reads every field it names, so a case is never a field nobody reads', function (): void {
    $read = '';

    foreach (readersOfTheWire() as $reader) {
        $read .= file_get_contents($reader);
    }

    foreach (WireField::cases() as $field) {
        expect(str_contains($read, sprintf('WireField::%s', $field->name)))->toBeTrue(sprintf(
            '`WireField::%s` names `%s` on the wire and no reader reads it. Either a reader '
            . 'dropped the field — which is how `unverified` came to read as a passing check — '
            . 'or the case describes something this app does not read and should go.',
            $field->name,
            $field->value,
        ));
    }
});

it('names each field once, so two cases cannot describe the same wire word', function (): void {
    $seen = [];

    foreach (WireField::cases() as $field) {
        expect($seen)->not->toContain($field->value, sprintf(
            '`%s` is named by two cases. The wire has one field by that name, and a reader '
            . 'picking either of them would be right — until somebody renamed one.',
            $field->value,
        ));

        $seen[] = $field->value;
    }
});
