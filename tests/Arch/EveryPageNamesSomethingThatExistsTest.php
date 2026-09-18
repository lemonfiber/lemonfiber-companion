<?php

declare(strict_types=1);

use Tests\Support\OurCode;
use Tests\Support\Tree;

// The pages the numbers moved to still point at code that exists.
//
// The argument for taking a requirement identifier out of a comment is that a
// comment rots silently: nothing reads it, so nothing notices when the thing it
// described was renamed. `.docs/requirements/` is where those numbers went, and
// it is made of exactly the same material. A page rots the same way and just as
// fast — and it is worse, because a page is the thing a reader was sent to.
//
// **This is not hypothetical.** Two rows named `WhatIsRunningElsewhere` and
// `HowTheStacksStandingReads` within days of being written. Neither has ever
// been the name of anything here. The move is only an improvement while
// something reads the page, which is what this is.
//
// It reads the *what keeps it* column and nothing else. The first column is a
// requirement and the gate resolves those against the spec; the second is a
// sentence, and a sentence is not checkable. The third is a promise about this
// repository, and this repository is right here.

/**
 * Every page in the requirements layer, less the index.
 *
 * `README.md` names the pages rather than the code, so its links are a
 * different question — and one the link checker already asks.
 *
 * @return list<string>
 */
function everyRequirementsPage(): array
{
    $pages = Tree::filesUnder(Tree::at('.docs/requirements'), '.md');

    $found = array_values(array_filter(
        $pages,
        static fn(string $path): bool => basename($path) !== 'README.md',
    ));

    sort($found);

    return $found;
}

/**
 * The names one page promises keep its requirements.
 *
 * A row is `| requirement | what it asks | what keeps it |`, so the third cell
 * is the one that makes a claim about this tree. Rows are recognised by opening
 * with a backticked identifier, which is what every requirement row does and
 * what a header row and a separator do not.
 *
 * @return list<string>
 */
function theNamesOnePageGives(string $path): array
{
    $source = file_get_contents($path);
    $found = [];

    foreach (explode("\n", is_string($source) ? $source : '') as $line) {
        if (! str_starts_with($line, '| `')) {
            continue;
        }

        $cells = array_map(trim(...), explode('|', trim($line, "| \t")));

        $found = [...$found, ...theNamesInOneCell($cells[2] ?? '')];
    }

    return $found;
}

/**
 * The backticked names in one cell, less the ones nothing could resolve.
 *
 * Two shapes are checkable and the rest are prose. A path ending `.php` is a
 * file or it is not. A name in the shape a class is written in — a capital, then
 * letters, at least one of them lower — is a word this repository either uses or
 * does not.
 *
 * Everything else in backticks is left alone, and that is where the rule stops
 * rather than where it was convenient to stop: `rollback`, `Info.plist`,
 * `FLAG_SECURE` and a requirement identifier all sit in these cells, and a rule
 * that tried to resolve them would be refusing prose.
 *
 * @return list<string>
 */
function theNamesInOneCell(string $cell): array
{
    $matched = preg_match_all('/`([^`]+)`/', $cell, $ticked);
    $found = [];

    foreach (is_int($matched) ? $ticked[1] : [] as $name) {
        if (str_ends_with($name, '.php') || aNameShapedLikeAClass($name)) {
            $found[] = $name;
        }
    }

    return $found;
}

/** A capital, then letters and digits, with at least one of them lower-case. */
function aNameShapedLikeAClass(string $name): bool
{
    return preg_match('/^[A-Z][A-Za-z0-9]*$/', $name) === 1
        && $name !== strtoupper($name);
}

/**
 * Every name this repository answers to, as a page might write one.
 *
 * Two sources, because a page names two kinds of thing. A capitalised word in
 * the source text covers a class, an enum case or a constant, and it is read
 * from the text rather than from the class map deliberately: `ConfigEnvelope` is
 * the SDK's, and a rule resolving only what this repository declares would
 * refuse a row for being right about somebody else's code.
 *
 * A file's own name covers the other kind. A rule is named by its file and a
 * Pest file declares no class, so `WhatPairingMaterialCannotSayYetTest` is
 * written nowhere inside any PHP — which had the first draft of this rule refuse
 * a row that was correct.
 *
 * @return array<string, true>
 */
function everyNameWeAnswerTo(): array
{
    $found = [];

    foreach (OurCode::phpFiles() as $path) {
        // Not itself. The prose above names the two rows that rotted, in order
        // to say what this rule is for — and a rule that read its own
        // description would answer *yes, this repository has that* about every
        // name it was written to refuse. The planted case passed until this
        // line existed, which is the only reason anybody would find out.
        if ($path === Tree::at('tests/Arch/EveryPageNamesSomethingThatExistsTest.php')) {
            continue;
        }

        $found[basename($path, '.php')] = true;

        $source = file_get_contents($path);
        $matched = preg_match_all('/\b[A-Z][A-Za-z0-9]*\b/', is_string($source) ? $source : '', $words);

        foreach (is_int($matched) ? $words[0] : [] as $word) {
            $found[$word] = true;
        }
    }

    return $found;
}

/**
 * Every name the pages give, against the page and whether this tree has it.
 *
 * @return list<array{string, string, bool}>
 */
function whatThePagesPromise(): array
{
    $written = everyNameWeAnswerTo();
    $tracked = array_flip(array_map(
        static fn(string $path): string => str_replace(sprintf('%s/', Tree::root()), '', $path),
        OurCode::phpFiles(),
    ));

    $found = [];

    foreach (everyRequirementsPage() as $page) {
        $named = basename($page);

        foreach (theNamesOnePageGives($page) as $name) {
            $has = str_ends_with($name, '.php')
                ? array_key_exists($name, $tracked)
                : array_key_exists($name, $written);

            $found[] = [$named, $name, $has];
        }
    }

    return $found;
}

it('GOV-R6 — every page names something this repository has', function (): void {
    $missing = array_map(
        static fn(array $row): string => sprintf('%s says `%s`', $row[0], $row[1]),
        array_values(array_filter(whatThePagesPromise(), static fn(array $row): bool => ! $row[2])),
    );

    sort($missing);

    expect($missing)->toBe([], sprintf(
        "These pages promise something this repository does not have:\n  %s\n\n"
        . 'The *what keeps it* column is what makes the requirements layer worth more than a '
        . 'second copy of the spec: it is how a reader gets from a requirement to the code and '
        . 'back. A name nothing answers to sends them nowhere, and does it silently — which is '
        . 'the whole of what taking these numbers out of code comments was meant to end '
        . "(GOV-R6).",
        implode("\n  ", $missing),
    ));
});

it('reads the rows it is written to read', function (): void {
    // The floor every rule of this shape owes. A page whose table stopped
    // matching — a fourth column, a heading change, a row written without
    // backticks — leaves this walking nothing and passing, and a green run over
    // nothing looks exactly like a green run over everything.
    expect(count(whatThePagesPromise()))->toBeGreaterThan(50)
        ->and(count(everyRequirementsPage()))->toBeGreaterThan(5);
});
