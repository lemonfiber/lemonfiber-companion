<?php

declare(strict_types=1);

use Modules\Wayfinding\Api\TheHouseholdsTabs;
use Modules\Wayfinding\Api\TheTabs;
use Modules\Wayfinding\Internal\TheMenu;
use Tests\Support\Tree;

// A label names what it opens or does in at most three words, and is never a
// sentence; a sentence may explain beside it.
//
// Read off where a line is drawn rather than how its key is spelled: the prop
// that names a control or a section, the slot a section heading is drawn in,
// the title of a screen's top bar, and every tab and menu item. A placeholder
// is one word, being the name or figure the label names, and a name this
// product gives its own things is one word by being one. The English catalogue
// is the one read; the others are held to it key for key next door.

/** The most words a label holds before it is a sentence. */
const A_LABEL = 3;

/**
 * Where a component is handed its label, by tag and the prop that names it.
 *
 * @var array<string, string> tag => prop
 */
const LABELLED_BY = [
    'x-design::action' => 'label',
    'x-design::link' => 'label',
    'x-design::toggle' => 'label',
    'x-design::chip' => 'label',
    'x-design::section' => 'label',
    'x-operator::offered-action' => 'label',
    'x-operator::quiet-action' => 'label',
    'x-operator::screen-opens' => 'title',
];

/** The tags whose slot is a section heading. */
const HEADED_BY = ['x-design::heading', 'x-operator::heading'];

/**
 * Every key a template draws as a label, each with where it is drawn.
 *
 * @return array<string, string> key => template
 */
function everyKeyDrawnAsALabel(): array
{
    $labels = [];

    foreach (Tree::filesUnder(Tree::at('app-modules'), '.blade.php') as $path) {
        foreach (theKeysLabelledIn((string) file_get_contents($path)) as $key) {
            $labels[$key] ??= substr($path, strlen(Tree::at('')));
        }
    }

    foreach ([...TheMenu::cases(), ...TheTabs::cases(), ...TheHouseholdsTabs::cases()] as $place) {
        $labels[$place->said()] ??= $place::class;
    }

    return $labels;
}

/**
 * The catalogue keys one template draws as labels.
 *
 * @return list<string>
 */
function theKeysLabelledIn(string $source): array
{
    $keys = [];

    foreach (LABELLED_BY as $tag => $prop) {
        preg_match_all(sprintf('/<%s\b(?:"[^"]*"|\'[^\']*\'|[^\'">])*?\s:?%s="__\(\'([a-z_.]+)\'/s', preg_quote($tag, '/'), $prop), $source, $named);
        $keys = [...$keys, ...$named[1]];
    }

    foreach (HEADED_BY as $tag) {
        preg_match_all(sprintf('/<%s\b[^>]*>\s*\{\{\s*__\(\'([a-z_.]+)\'/s', preg_quote($tag, '/')), $source, $named);
        $keys = [...$keys, ...$named[1]];
    }

    return $keys;
}

/** One English catalogue line by its dotted key, or null where there is none. */
function theEnglishLine(string $key): ?string
{
    [$catalogue, $rest] = explode('.', $key, 2);
    $path = Tree::at(sprintf('lang/en/%s.php', $catalogue));
    $line = is_file($path) ? require $path : null;

    foreach (explode('.', $rest) as $step) {
        $line = is_array($line) ? $line[$step] ?? null : null;
    }

    return is_string($line) ? $line : null;
}

/** How many words a label holds as it is read, a placeholder counting as one. */
function wordsInALabel(string $label): int
{
    $words = preg_split('/\s+/u', trim((string) preg_replace('/:[a-z_]+/', 'it', $label)), flags: PREG_SPLIT_NO_EMPTY);

    return is_array($words) ? count($words) : 0;
}

it('counts a placeholder as one word, and refuses a label grown into a sentence', function (): void {
    expect(wordsInALabel('Ask again'))->toBe(2)
        ->and(wordsInALabel('Remove :name'))->toBe(2)
        ->and(wordsInALabel('Watch one thing arrive') > A_LABEL)->toBeTrue()
        ->and(wordsInALabel('See all') > A_LABEL)->toBeFalse();
});

it('finds a label in each place one is drawn', function (): void {
    $source = <<<'BLADE'
        <x-design::action :label="__('a.one')" tap="x()" />
        <x-design::link label="{{ __('ignored') }}" :label="__('a.two')" :goes="$here" />
        <x-operator::screen-opens :title="__('a.three')" :back="true" />
        <x-design::heading>{{ __('a.four') }}</x-design::heading>
        <x-design::body>{{ __('a.five') }}</x-design::body>
        BLADE;

    expect(theKeysLabelledIn($source))->toBe(['a.one', 'a.two', 'a.three', 'a.four']);
});

it('reads enough labels for a clean sweep to mean something', function (): void {
    expect(count(everyKeyDrawnAsALabel()))->toBeGreaterThan(300);
});

it('names what each label opens or does in at most three words', function (): void {
    $sentences = [];

    foreach (everyKeyDrawnAsALabel() as $key => $where) {
        $line = theEnglishLine($key);

        if ($line !== null && wordsInALabel($line) > A_LABEL) {
            $sentences[] = sprintf('%s (%s, %d words): %s', $key, $where, wordsInALabel($line), $line);
        }
    }

    sort($sentences);

    expect($sentences)->toBe([], sprintf(
        "These are drawn as labels and have grown into sentences:\n  %s\n\n"
        . 'A label names what it opens or does in at most three words; keep the sentence as a note beside it (G2-R15).',
        implode("\n  ", $sentences),
    ));
});
