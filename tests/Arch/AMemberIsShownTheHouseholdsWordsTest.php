<?php

declare(strict_types=1);

use Lemonfiber\Sdk\Generated\HouseholdWords;
use Tests\Support\TheHouseholdsWords;
use Tests\Support\Tree;

// A member's screen says what it says in the household's words: no
// service's name, no domain term, no size, no rate, no address and no version.
//
// Read off the source rather than a drawn frame, so every arm of every member
// screen is read, not only the one a test happened to reach: each catalogue
// line the member's own catalogue holds, each line of another catalogue a
// member's screen names, and the words a member's template writes down. The
// list is the one the core keeps for every surface (`TheHouseholdsWords`), so
// the companion and the web hold a member to the same words. The English
// catalogue is the one read, being the language the list is written in; the
// Dutch one is held to mirror it key for key next door.

/** Where a member's screens and their parts are drawn. */
const A_MEMBERS_TEMPLATES = 'app-modules/household/resources/views';

/** Where a member's screens and their parts are written. */
const A_MEMBERS_CODE = 'app-modules/household/src';

/**
 * What the household module draws that is not a member's surface, and why.
 *
 * @var array<string, string> template, under the views, => why
 */
const DRAWN_FOR_THE_OPERATOR = [
    'what-a-member-would-see.blade.php' => 'the operator\'s preview of the member\'s side, drawn for the operator\'s session',
    'components/the-preview-mark.blade.php' => 'the mark that tells the operator the screen is a preview, drawn only over the preview',
];

/** The group of the member's catalogue drawn only on the operator's preview, for the same reason. */
const THE_OPERATORS_PREVIEW = 'preview';

/**
 * Every line a catalogue holds, by its dotted key.
 *
 * @param  array<mixed>          $lines
 * @return array<string, string>
 */
function everyCatalogueLineByKey(array $lines, string $under = ''): array
{
    $flat = [];

    foreach ($lines as $key => $line) {
        $dotted = sprintf('%s%s', $under, $key);
        $flat = [...$flat, ...(is_array($line) ? everyCatalogueLineByKey($line, sprintf('%s.', $dotted)) : [$dotted => is_string($line) ? $line : ''])];
    }

    return $flat;
}

/**
 * One English catalogue, by its dotted keys, each under the catalogue's name.
 *
 * @return array<string, string>
 */
function theEnglishCatalogue(string $name): array
{
    $path = Tree::at(sprintf('lang/en/%s.php', $name));
    $lines = is_file($path) ? require $path : [];

    return everyCatalogueLineByKey(is_array($lines) ? $lines : [], sprintf('%s.', $name));
}

/**
 * The member's templates, by their path under the views, with what is the operator's left out.
 *
 * @return array<string, string> path under the views => source
 */
function theMembersTemplates(): array
{
    $root = Tree::at(A_MEMBERS_TEMPLATES);
    $templates = [];

    foreach (Tree::filesUnder($root, '.blade.php') as $path) {
        $under = substr($path, strlen($root) + 1);

        if (! array_key_exists($under, DRAWN_FOR_THE_OPERATOR)) {
            $templates[$under] = (string) file_get_contents($path);
        }
    }

    return $templates;
}

/**
 * Every line a member reads, by its key: the member's catalogue less the operator's preview, and each line of another catalogue a member's screen names.
 *
 * @return array<string, string>
 */
function everyLineAMemberReads(): array
{
    $lines = array_filter(
        theEnglishCatalogue('household'),
        static fn(string $key): bool => ! str_starts_with($key, sprintf('household.%s.', THE_OPERATORS_PREVIEW)),
        ARRAY_FILTER_USE_KEY,
    );
    $sources = [...array_values(theMembersTemplates()), ...array_map(file_get_contents(...), Tree::filesUnder(Tree::at(A_MEMBERS_CODE), '.php'))];

    foreach ($sources as $source) {
        preg_match_all("/__\\('([a-z_]+)\\.([a-z_.]+)'/", (string) $source, $named, PREG_SET_ORDER);

        foreach ($named as [, $catalogue, $key]) {
            $dotted = sprintf('%s.%s', $catalogue, $key);
            $lines[$dotted] ??= theEnglishCatalogue($catalogue)[$dotted] ?? '';
        }
    }

    return $lines;
}

/** The words a template writes down itself, with its comments, its expressions, its directives and its tags left out. */
function theWordsATemplateWritesDown(string $source): string
{
    return (string) preg_replace(
        ['/\{\{--.*?--\}\}/s', '/\{\{.*?\}\}/s', '/\{!!.*?!!\}/s', '/@\w+(?:\s*(?<group>\((?:[^()]++|(?&group))*\)))?/s', '/<(?:"[^"]*"|\'[^\']*\'|[^\'">])*>/s'],
        ' ',
        $source,
    );
}

it('reads the core\'s list, and the list knows a name, a term, a size, an address and a version when it sees one', function (): void {
    $words = TheHouseholdsWords::read();

    expect($words->size())->toBeGreaterThan(100)
        ->and($words->unhoused('Jellyfin is not answering'))->toBe('jellyfin')
        ->and($words->unhoused('The download client stalled'))->toBe('download client')
        ->and($words->unhoused('2.5 GB left'))->toBe('2.5')
        ->and($words->unhoused('Open https://the-house.lan'))->toBe('https')
        ->and($words->unhoused('Open it at the-house.lan://'))->toBe('://')
        ->and($words->unhoused('Running v1.18'))->toBe('v1.18')
        ->and($words->unhoused('There is no room for new things in the house right now.'))->toBeNull();
});

it('names in each of the core\'s examples what it says a member may not be shown', function (): void {
    $words = TheHouseholdsWords::read();
    $missed = [];

    foreach (HouseholdWords::EXAMPLES as ['text' => $text, 'names' => $names]) {
        if ($words->unhoused($text) !== $names) {
            $missed[] = sprintf('`%s` names `%s`, not `%s`', $text, (string) $words->unhoused($text), $names);
        }
    }

    expect(HouseholdWords::EXAMPLES)->not->toBeEmpty()
        ->and($missed)->toBe([]);
});

it('finds the member\'s lines and templates it holds to the household\'s words', function (): void {
    expect(count(everyLineAMemberReads()))->toBeGreaterThan(100)
        ->and(theMembersTemplates())->toHaveKey('what-is-on-live-tv.blade.php')
        ->and(theMembersTemplates())->not->toHaveKey('what-a-member-would-see.blade.php');
});

it('shows a member no service, no domain term, no size, no address and no version in any line it reads', function (): void {
    $words = TheHouseholdsWords::read();
    $unhoused = [];

    foreach (everyLineAMemberReads() as $key => $line) {
        $found = $words->unhoused((string) preg_replace('/:[a-z_]+/', '', $line));

        if ($found !== null) {
            $unhoused[] = sprintf('%s says `%s`: %s', $key, $found, $line);
        }
    }

    foreach (theMembersTemplates() as $path => $source) {
        $found = $words->unhoused(theWordsATemplateWritesDown($source));

        if ($found !== null) {
            $unhoused[] = sprintf('%s writes `%s`', $path, $found);
        }
    }

    sort($unhoused);

    expect($unhoused)->toBe([], sprintf(
        "A member is shown these, which are the machine's words and not the household's:\n  %s\n\n"
        . 'Say what it means for the member and what they can do, and call the stack the house (G2-R16).',
        implode("\n  ", $unhoused),
    ));
});
