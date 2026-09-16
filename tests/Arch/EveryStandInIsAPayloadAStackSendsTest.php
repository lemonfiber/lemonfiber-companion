<?php

declare(strict_types=1);

use Modules\Dx\Internal\WhatTheContractDeclares;
use Tests\Support\Calls;
use Tests\Support\Module;
use Tests\Support\Tree;

// G12 — a suite that stands a payload in for a stack reads it against the
// contract.
//
// `G2` runs both implementations against the same assertions, which proves they
// agree with each other and not that either agrees with the machine. The
// payload they are both run against is hand-written, by whoever wrote the
// reader — so when the reader looks for a field at a path the contract has not
// got and the payload obliges, both halves pass and the application is broken
// against every real stack.
//
// `WhatTheContractAccepts` closes that, and this is what keeps it closed
// everywhere rather than in whichever suite last remembered. The row in the
// document says *every* suite, so one suite outside the check would have that
// row claim a guarantee a single file's assertion was carrying.
//
// **A wire body is written two ways here, and the rule saw one of them.** A
// suite that writes the body out whole spells the version field, which nothing
// but an envelope writes. The SDK reader suites build theirs positionally, by
// handing three arguments to the envelope type, so no field of an envelope is
// named anywhere in the file and that mark never matched. Ten files wrote a
// payload that way, including the one whose payload is the reason this rule
// exists. They were not skipped — they were never seen, which is why the
// register read as complete while half of it had never been looked at.
//
// Read rather than run, for `G10`'s reason: what is being asked is whether the
// file makes the check at all, and a suite that had to be loaded to answer that
// is a suite the Guards harness could not plant a violation of — a real
// violation of this rule is an ordinary green suite, and the fixture for it has
// to sit where no testsuite collects it. Read over its tokens rather than over
// its text, for the reason {@see everyBodyOneStandInBuilds()} gives.

/**
 * How a stand-in says it stands a body in and deliberately does not judge it.
 *
 * Per kind rather than per file, and with the reason held inside the match,
 * because an exemption nobody can read is how a rule stops covering what it was
 * written for. A file that later builds a second kind of body is asked about
 * that one on its own, which a blanket "this file is excused" would not do.
 */
const STANDS_IN_UNJUDGED = '/`([a-z][a-z-]*)` is stood in for and not judged: (\S[^\n]*)/';

/** What the wire calls the field that carries the version an envelope is in. */
const THE_WIRE_VERSION_FIELD = 'api_version';

/** What the wire calls the field that carries which envelope a body is. */
const THE_WIRE_KIND_FIELD = 'kind';

/**
 * Every test file, by the path it is at, with what it says.
 *
 * Its own reading rather than `TestConventionsTest`'s `testSources()`, which is
 * the same shape one directory along. Calling that one would tie this file to
 * whether the other has been loaded yet, and `G10` is what stops the obvious
 * alternative of declaring a second function by that name.
 *
 * @return array<string, string>
 */
function whatEveryTestFileSays(): array
{
    $said = [];

    foreach (Tree::testFiles() as $path) {
        $contents = file_get_contents($path);

        if (is_string($contents)) {
            $said[str_replace(sprintf('%s/', Tree::root()), '', $path)] = $contents;
        }
    }

    return $said;
}

/**
 * The word a string token holds, without the quotes it is written in.
 *
 * @param array{int, string, int}|string $token
 */
function theWordAStringTokenHolds(array|string $token): string
{
    return is_array($token) && $token[0] === T_CONSTANT_ENCAPSED_STRING
        ? substr($token[1], 1, -1)
        : '';
}

/**
 * Whether a token is one a reader of code has to look at.
 *
 * Asked of `Calls` rather than answered here, so that what counts as noise is
 * settled in one place — a reading that had its own list would go on skipping
 * what that one learned to stop skipping.
 *
 * @param array{int, string, int}|string $token
 */
function isWorthReading(array|string $token): bool
{
    return Calls::meaningful([$token]) !== [];
}

/**
 * The token before `$at` that is worth reading.
 *
 * @param list<array{int, string, int}|string> $tokens
 *
 * @return array{int, string, int}|string
 */
function theTokenBefore(array $tokens, int $at): array|string
{
    for ($here = $at - 1; $here >= 0; $here--) {
        if (isWorthReading($tokens[$here])) {
            return $tokens[$here];
        }
    }

    return '';
}

/**
 * The next few tokens after `$at` that are worth reading.
 *
 * @param list<array{int, string, int}|string> $tokens
 *
 * @return list<array{int, string, int}|string>
 */
function theTokensAfter(array $tokens, int $at, int $howMany): array
{
    $found = [];
    $total = count($tokens);

    for ($here = $at + 1; $here < $total && count($found) < $howMany; $here++) {
        if (isWorthReading($tokens[$here])) {
            $found[] = $tokens[$here];
        }
    }

    return $found;
}

/**
 * Whether the name at `$at` is the envelope type being built rather than named.
 *
 * Built is the question, not mentioned: a return type, an import and an
 * annotation all spell the type and none of them is a payload.
 *
 * @param list<array{int, string, int}|string> $tokens
 */
function isTheEnvelopeTypeBeingBuilt(array $tokens, int $at): bool
{
    $token = $tokens[$at] ?? '';

    if (! is_array($token) || ! in_array($token[0], [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED], strict: true)) {
        return false;
    }

    $named = explode('\\', $token[1]);
    $before = theTokenBefore($tokens, $at);

    return $named[count($named) - 1] === 'Envelope' && is_array($before) && $before[0] === T_NEW;
}

/**
 * The kind a positionally built body was handed, where that is a word.
 *
 * The second argument, because that is where the envelope type takes it. An
 * empty answer is a kind this cannot read — a variable, a constant, an
 * expression — which is a different fact from a kind that resolved to nothing.
 *
 * @param list<array{int, string, int}|string> $tokens
 */
function theKindHandedToAnEnvelope(array $tokens, int $at): string
{
    $arguments = Calls::argumentsAt($tokens, $at);
    $second = $arguments[1] ?? [];

    return Calls::isALoneString($second)
        ? theWordAStringTokenHolds(Calls::meaningful($second)[0] ?? '')
        : '';
}

/**
 * Whether the string at `$at` is a field of a body written out whole.
 *
 * @param list<array{int, string, int}|string> $tokens
 */
function namesAWireField(array $tokens, int $at, string $field): bool
{
    if (theWordAStringTokenHolds($tokens[$at] ?? '') !== $field) {
        return false;
    }

    $next = theTokensAfter($tokens, $at, 1)[0] ?? '';

    return is_array($next) && $next[0] === T_DOUBLE_ARROW;
}

/**
 * The kind written beside a body that was written out whole.
 *
 * The next one after the version field rather than the one in the same array
 * literal, which would mean matching brackets to find where that literal ends.
 * A body carries both fields together, so the next kind along is that body's —
 * and a version field with no kind after it at all answers with nothing, which
 * is reported rather than passed over.
 *
 * @param list<array{int, string, int}|string> $tokens
 */
function theKindWrittenBesideIt(array $tokens, int $at): string
{
    $total = count($tokens);

    for ($here = $at + 1; $here < $total; $here++) {
        if (namesAWireField($tokens, $here, THE_WIRE_KIND_FIELD)) {
            return theWordAStringTokenHolds(theTokensAfter($tokens, $here, 2)[1] ?? '');
        }
    }

    return '';
}

/**
 * Every wire body one test file builds, as the word each was built under.
 *
 * Read over tokens rather than over the text, for `Tree::declaresAClass()`'s
 * reason: every comment in this codebase quotes code, so a search for the way a
 * body is written finds the paragraph explaining this rule before it finds a
 * body. This file would be the first thing the rule reported.
 *
 * An entry of `''` is a body whose kind is not a word at all. That is not a
 * violation of anything — it is the one thing this cannot resolve — and it is
 * carried out rather than dropped, so that it can be reported instead of read
 * as a body that resolved fine.
 *
 * @return list<string>
 */
function everyBodyOneStandInBuilds(string $contents): array
{
    $tokens = token_get_all($contents);
    $built = [];

    foreach (array_keys($tokens) as $at) {
        if (isTheEnvelopeTypeBeingBuilt($tokens, $at)) {
            $built[] = theKindHandedToAnEnvelope($tokens, $at);
        }

        if (namesAWireField($tokens, $at, THE_WIRE_VERSION_FIELD)) {
            $built[] = theKindWrittenBesideIt($tokens, $at);
        }
    }

    return $built;
}

/**
 * Every test that stands a payload in for a stack, by the path it is at.
 *
 * @return list<string>
 */
function everySuiteStandingInForAStack(): array
{
    $standing = [];

    foreach (whatEveryTestFileSays() as $path => $contents) {
        if (everyBodyOneStandInBuilds($contents) !== []) {
            $standing[] = $path;
        }
    }

    sort($standing);

    return $standing;
}

/**
 * Which generated envelope reads each kind.
 *
 * Resolved out of the package rather than out of a map written here. A map
 * would be a second copy of the contract's own answer, kept by whoever last
 * added a suite — and a kind it had not heard of would resolve to nothing and
 * be passed over, which is the silence this rule exists to refuse. Every
 * envelope declares the one kind it reads, and the enum beside them holds the
 * word that kind is on the wire.
 *
 * @return array<string, string> the word on the wire => the envelope class
 */
function whichEnvelopeReadsEachKind(): array
{
    $reads = [];

    foreach (WhatTheContractDeclares::everyEnvelope() as $envelope) {
        $kind = WhatTheContractDeclares::kindOf($envelope);

        if ($kind !== '') {
            $reads[$kind] = $envelope;
        }
    }

    return $reads;
}

/**
 * What a stand-in says it stands in for and deliberately does not judge.
 *
 * @return array<string, string> the word on the wire => why it is not judged
 */
function whatOneStandInLeavesUnjudged(string $contents): array
{
    preg_match_all(STANDS_IN_UNJUDGED, $contents, $found, PREG_SET_ORDER);

    $declared = [];

    foreach ($found as $one) {
        $declared[$one[1]] = trim($one[2]);
    }

    return $declared;
}

/**
 * Every envelope some reader in this repository unwraps.
 *
 * Grepped rather than counted into a figure here, for the reason the floor
 * below gives: a figure a person can edit is a figure that falls the day a
 * reader is written and nobody notices.
 *
 * @return list<string>
 */
function everyEnvelopeAReaderUnwraps(): array
{
    $found = [];

    foreach (Module::all() as $module) {
        foreach ($module->classes() as $path) {
            $source = file_get_contents($path);

            if (! is_string($source)) {
                continue;
            }

            preg_match_all('/\b([A-Za-z0-9_]+Envelope)::in\(/', $source, $unwrapped);

            foreach ($unwrapped[1] as $envelope) {
                $found[$envelope] = true;
            }
        }
    }

    $envelopes = array_keys($found);
    sort($envelopes);

    return $envelopes;
}

/**
 * Every envelope some stand-in holds a payload to.
 *
 * The envelope is named where the judgement is asked for, so the quoted name in
 * a file that asks is the answer. A file that never asks contributes nothing,
 * which is what lets the floor below tell a covered envelope from one that
 * merely has suites.
 *
 * @return list<string>
 */
function everyEnvelopeAStandInJudges(): array
{
    $said = whatEveryTestFileSays();
    $found = [];

    foreach (everySuiteStandingInForAStack() as $path) {
        $contents = $said[$path] ?? '';

        if (! str_contains($contents, 'WhatTheContractAccepts')) {
            continue;
        }

        preg_match_all('/\'([A-Za-z0-9_]+Envelope)\'/', $contents, $judged);

        foreach ($judged[1] as $envelope) {
            $found[$envelope] = true;
        }
    }

    $envelopes = array_keys($found);
    sort($envelopes);

    return $envelopes;
}

it('G12 — every payload stood in for a stack is read against the contract', function (): void {
    $said = whatEveryTestFileSays();
    $unchecked = [];

    foreach (everySuiteStandingInForAStack() as $path) {
        $contents = $said[$path] ?? '';

        if (str_contains($contents, 'WhatTheContractAccepts')) {
            continue;
        }

        $declared = whatOneStandInLeavesUnjudged($contents);

        foreach (array_unique(everyBodyOneStandInBuilds($contents)) as $kind) {
            if (! array_key_exists($kind, $declared)) {
                $unchecked[] = sprintf('%s — `%s`', $path, $kind);
            }
        }
    }

    sort($unchecked);

    expect($unchecked)->toBe([], sprintf(
        "These write a payload a stack is supposed to have sent, and never ask whether a stack could send it:\n  %s\n\n"
        . 'A hand-written payload is written by whoever wrote the reader, so the two agree about a field that is '
        . 'not there and the suite stays green against a machine nobody has run it against. Put the payload in a '
        . "function and read it with WhatTheContractAccepts::complaintsAbout().\n\n"
        . 'Where the body is deliberately not one a stack sends — because the case is about what happens before '
        . 'anything reads it — say so at the fixture, naming the kind and the reason, in the words '
        . "\"`<kind>` is stood in for and not judged: <why>\".\n",
        implode("\n  ", $unchecked),
    ));
});

it('G12 — a kind stood in for is one the contract has an envelope for', function (): void {
    $reads = whichEnvelopeReadsEachKind();
    $said = whatEveryTestFileSays();
    $unknown = [];

    foreach (everySuiteStandingInForAStack() as $path) {
        foreach (array_unique(everyBodyOneStandInBuilds($said[$path] ?? '')) as $kind) {
            if ($kind === '') {
                $unknown[] = sprintf('%s builds a body whose kind is not a word this rule can read', $path);
            }

            if ($kind !== '' && ! array_key_exists($kind, $reads)) {
                $unknown[] = sprintf('%s stands in for `%s`', $path, $kind);
            }
        }
    }

    sort($unknown);

    expect($unknown)->toBe([], sprintf(
        "These hand a body to an envelope under a kind the contract has not got:\n  %s\n\n"
        . 'An envelope carries a payload between two programs, and a kind neither of them describes is not a '
        . 'conversation either of them can have — so a stand-in built under one is judged against nothing and '
        . 'reads as covered. Where the envelope is standing in as a carrier for a value a test needs out of a '
        . 'closure, write a readonly class for that instead: WhatOneStuckRowSaid and WhatTheStackTurnedOutToBeOn '
        . "are the two already here.\n",
        implode("\n  ", $unknown),
    ));
});

it('G12 — the rule has something to read, so a silent pass is not one', function (): void {
    // What makes the rules above mean anything. A mark that matched no file
    // would report no violations, which reads exactly like compliance — and
    // this is the failure mode the rule exists to catch, arriving through the
    // rule itself. The package is asked the same question: an envelope list
    // that came back empty would resolve every kind to nothing, which is loud,
    // and one that came back empty with no stand-ins to read would be silent.
    expect(everySuiteStandingInForAStack())->not->toBe([])
        ->and(whichEnvelopeReadsEachKind())->not->toBe([]);
});

it('G12 — every envelope a reader unwraps has a stand-in judged against the contract', function (): void {
    // The third of the three floors under this rule, and the one about the
    // register rather than about a file. The first is the rule itself: every
    // stand-in judges the body it builds. The second is above: there is at
    // least one stand-in to have asked. This one says the set of them covers
    // every envelope this application actually reads, so a reader written for a
    // ninth envelope tomorrow fails here until something stands a payload in
    // for it.
    //
    // None of the three is a figure written down. The envelopes come from the
    // call sites in the modules, the stand-ins come from the bodies they build,
    // and both are read on every run — because a figure in a file is a figure
    // that gets edited down to whatever today's tree happens to be.
    $unwrapped = everyEnvelopeAReaderUnwraps();
    $uncovered = array_values(array_diff($unwrapped, everyEnvelopeAStandInJudges()));

    expect($unwrapped)->not->toBe([]);

    expect($uncovered)->toBe([], sprintf(
        "These envelopes are unwrapped by a reader here and no suite has stood a payload in for them:\n  %s\n\n"
        . 'Every one of them is a shape this application takes a screen from, and a shape nothing has held to '
        . 'the contract is a shape the generated types have never been asked about. The suite that reads the '
        . "envelope is the one to ask in.\n",
        implode("\n  ", $uncovered),
    ));
});
