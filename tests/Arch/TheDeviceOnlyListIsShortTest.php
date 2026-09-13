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
// a permanent one. So this refuses a directory, refuses an entry with no reason
// beside it, and refuses one naming a file that is gone — the three shapes a
// list rots into.

const THE_MOST_THAT_CAN_BE_EXCLUDED = 3;

/** Where this repository is, from a file two directories inside it. */
function theRepositoryRoot(): string
{
    return dirname(__DIR__, 2);
}

/** The `<exclude>` block of `phpunit.xml`, as text. */
function theExclusions(): string
{
    $config = (string) file_get_contents(sprintf('%s/phpunit.xml', theRepositoryRoot()));

    $from = strpos($config, '<exclude>');
    $to = strpos($config, '</exclude>');

    expect($from)->not->toBeFalse('phpunit.xml has no <exclude> block to read');
    expect($to)->not->toBeFalse('phpunit.xml has an unterminated <exclude> block');

    return is_int($from) && is_int($to) ? substr($config, $from, $to - $from) : '';
}

/** @return list<string> the paths it names */
function theExcludedFiles(): array
{
    preg_match_all('~<file>([^<]+)</file>~', theExclusions(), $found);

    return $found[1];
}

it('excludes files one at a time, never a directory', function (): void {
    // A directory excludes whatever is put in it later, by whoever puts it
    // there, with no decision and no reason — which is the whole mechanism
    // failing quietly rather than a mistake somebody could notice.
    expect(theExclusions())->not->toContain(
        '<directory>',
        sprintf(
            'A directory excludes whatever lands in it next. Name each file, with why %s',
            'it cannot be covered rather than why it was not.',
        ),
    );
});

it('names a reason beside each one', function (): void {
    // An entry with no reason records that somebody excluded it and nothing
    // about whether that is still true — which is what the next reader needs.
    $commented = substr_count(theExclusions(), '<!--');

    expect($commented)->toBeGreaterThanOrEqual(
        count(theExcludedFiles()),
        sprintf(
            'Every excluded file needs a comment saying why a suite cannot reach it. %s',
            'Without one, nobody after you can tell a device-only file from a file somebody found awkward.',
        ),
    );
});

it('names only files that are still here', function (): void {
    // A path that has moved leaves an exclusion excusing nothing, and the file
    // it used to name is now measured — or not — with nobody the wiser.
    foreach (theExcludedFiles() as $excluded) {
        expect(file_exists(sprintf('%s/%s', theRepositoryRoot(), $excluded)))->toBeTrue(sprintf(
            '%s is excluded from coverage and is not there. Remove the entry, or point it '
            . 'at wherever the file went.',
            $excluded,
        ));
    }
});

it('stays short, because the list is the exception and not a budget', function (): void {
    // A ratchet in the same spirit as the architecture suite's `planned` count:
    // this may fall and it may not rise without somebody arguing for the rise
    // here, in the number, where a reviewer meets it.
    expect(count(theExcludedFiles()))->toBeLessThanOrEqual(
        THE_MOST_THAT_CAN_BE_EXCLUDED,
        sprintf(
            'Raising this is a decision about how much of this application is not measured. %s',
            'Make it deliberately: a file that is hard to cover wants a seam, and a seam is '
            . 'what keeps this list from being the answer to every awkward file.',
        ),
    );
});
