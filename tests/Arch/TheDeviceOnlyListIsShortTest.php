<?php

declare(strict_types=1);

// Every file excluded from coverage is one a suite genuinely cannot reach.
//
// `AGENTS.md` says the list is "listed by name in phpunit.xml with its reason,
// and a test asserts that list does not grow silently". This is that test, and
// it was written when the list gained its first entry — which is the only
// moment writing it costs nothing.
//
// The failure it guards against is not a wrong entry. It is a *right-looking*
// one: "hard to cover" and "cannot be covered" are indistinguishable from
// inside a file, and the first is a design problem that an exclusion turns into
// a permanent one. So this refuses anything but a named file, refuses an entry
// with no reason beside it, and refuses one naming a path that is gone — the
// three shapes a list rots into.
//
// **The block is found by the parser and an entry is read as an element**, and
// both of those are the difference between this holding and this reading as
// though it held.
//
// `exclude` is a name a `<testsuite>` may carry as well, and every testsuite in
// this file is declared above `<source>` — so the first block in the document
// is not necessarily the one this rule is about. A search would read an
// unrelated block and report nothing wrong with a list it never opened. And a
// text search for `<source` finds the paragraph above the settings before it
// finds the element, because every comment in this repository quotes the thing
// it explains.
//
// An entry carries attributes — `<directory suffix=".php">` — so matching the
// bare tag walks past the one form that matters, and nothing else here would
// see it either: it is not a `<file>`, so it falls outside the count, outside
// the ratchet, and outside the reason check, which then compares a comment
// total against zero entries. One line, four assertions, all green, and a
// module's whole source tree unmeasured.

/**
 * How many files may be excluded from coverage.
 *
 * Flush with the list, because a ceiling above it is a budget wearing a
 * ratchet's name: at three against one entry, two more files could be excluded
 * — the whole of what this rule is about — without anything going red. The
 * number falls with the list and rises only where somebody argues for it here,
 * which is where a reviewer meets it.
 */
const THE_MOST_THAT_CAN_BE_EXCLUDED = 1;

/** Where this repository is, from a file two directories inside it. */
function theRepositoryRoot(): string
{
    return dirname(__DIR__, 2);
}

/**
 * The `<exclude>` block of the `<source>` element, as the text that wrote it.
 *
 * Located by the parser and handed on as text, because the two halves of this
 * rule need different things: the entries want an element and the reasons want
 * the comments a parser drops. `asXML()` is where those meet — it serialises
 * the element the xpath found, comments and all.
 */
function theExclusions(): string
{
    $configuration = simplexml_load_file(sprintf('%s/phpunit.xml', theRepositoryRoot()));

    expect($configuration)->not->toBeFalse('phpunit.xml could not be parsed, so nothing below was read');

    $found = $configuration === false ? [] : $configuration->xpath('/phpunit/source/exclude');
    $blocks = is_array($found) ? array_values($found) : [];

    expect($blocks)->toHaveCount(1, implode(PHP_EOL, [
        'phpunit.xml does not hold exactly one <exclude> under <source>.',
        '',
        'None means nothing is excluded and every rule below passes having read no list.',
        'More than one means this rule reads whichever came first and says nothing about',
        'the rest.',
    ]));

    return implode('', array_map(
        static fn(SimpleXMLElement $block): string => (string) $block->asXML(),
        $blocks,
    ));
}

/**
 * What one `<exclude>` block names, as elements rather than as text.
 *
 * Every child, whatever it is called, because the rule below is that each one
 * is a `<file>` — and a check that only looked for the tags it expected could
 * not report the tag it did not.
 *
 * @return list<array{tag: string, path: string}>
 */
function exclusionsIn(string $block): array
{
    $element = simplexml_load_string($block);
    $entries = $element === false ? null : $element->children();

    if ($entries === null) {
        return [];
    }

    $found = [];

    foreach ($entries as $tag => $entry) {
        $found[] = ['tag' => $tag, 'path' => trim((string) $entry)];
    }

    return $found;
}

/**
 * The entries that are not one named file.
 *
 * @param list<array{tag: string, path: string}> $entries
 *
 * @return list<string>
 */
function notOneNamedFile(array $entries): array
{
    return array_values(array_map(
        static fn(array $entry): string => sprintf('<%s>%s</%s>', $entry['tag'], $entry['path'], $entry['tag']),
        array_filter($entries, static fn(array $entry): bool => $entry['tag'] !== 'file'),
    ));
}

it('excludes files one at a time, never a directory', function (): void {
    // A directory excludes whatever is put in it later, by whoever puts it
    // there, with no decision and no reason — which is the whole mechanism
    // failing quietly rather than a mistake somebody could notice.
    $wider = notOneNamedFile(exclusionsIn(theExclusions()));

    expect($wider)->toBe([], sprintf(
        "These exclude something other than one named file:\n  %s\n\n"
        . 'A directory excludes whatever lands in it next. Name each file, with why %s',
        implode("\n  ", $wider),
        'it cannot be covered rather than why it was not.',
    ));
});

it('reads an entry as an element, not as the tag somebody happened to type', function (): void {
    // The judgement, handed the violation. There is no file to plant this in:
    // the list lives in the `phpunit.xml` of the run doing the reading, so an
    // entry added to it changes that run rather than a fixture — and what it
    // changes is a coverage report this suite does not produce.
    $withATree = <<<'XML'
        <exclude>
            <file>bootstrap/Composition/NativePHP/TheRunloop.php</file>
            <directory suffix=".php">app-modules/device/src</directory>
        </exclude>
        XML;

    expect(notOneNamedFile(exclusionsIn($withATree)))
        ->toBe(['<directory>app-modules/device/src</directory>']);

    // Counted too, so the ratchet and the reason check are asked about the
    // whole list rather than about the half of it written one way.
    expect(exclusionsIn($withATree))->toHaveCount(2);

    // And the empty answer is one it can give rather than the only one it has.
    expect(exclusionsIn('<exclude></exclude>'))->toBe([]);
    expect(notOneNamedFile(exclusionsIn('<exclude><file>a.php</file></exclude>')))->toBe([]);
});

it('names a reason beside each one', function (): void {
    // An entry with no reason records that somebody excluded it and nothing
    // about whether that is still true — which is what the next reader needs.
    $commented = substr_count(theExclusions(), '<!--');

    expect($commented)->toBeGreaterThanOrEqual(
        count(exclusionsIn(theExclusions())),
        sprintf(
            'Every excluded path needs a comment saying why a suite cannot reach it. %s',
            'Without one, nobody after you can tell a device-only file from a file somebody found awkward.',
        ),
    );
});

it('names only paths that are still here', function (): void {
    // A path that has moved leaves an exclusion excusing nothing, and the file
    // it used to name is now measured — or not — with nobody the wiser.
    foreach (exclusionsIn(theExclusions()) as $entry) {
        expect(file_exists(sprintf('%s/%s', theRepositoryRoot(), $entry['path'])))->toBeTrue(sprintf(
            '%s is excluded from coverage and is not there. Remove the entry, or point it '
            . 'at wherever it went.',
            $entry['path'],
        ));
    }
});

it('stays short, because the list is the exception and not a budget', function (): void {
    // A ratchet in the same spirit as the architecture suite's `planned` count:
    // this may fall and it may not rise without somebody arguing for the rise
    // here, in the number, where a reviewer meets it.
    expect(count(exclusionsIn(theExclusions())))->toBeLessThanOrEqual(
        THE_MOST_THAT_CAN_BE_EXCLUDED,
        sprintf(
            'Raising this is a decision about how much of this application is not measured. %s',
            'Make it deliberately: a file that is hard to cover wants a seam, and a seam is '
            . 'what keeps this list from being the answer to every awkward file.',
        ),
    );
});
