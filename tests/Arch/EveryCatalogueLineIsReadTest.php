<?php

declare(strict_types=1);

use Tests\Support\Catalogue;
use Tests\Support\Tree;

// L7's mirror: every line the catalogue holds is a line something shows.
//
// `EveryKeyTheAppNamesResolvesTest` asks whether every key the app names has a
// line, and `EveryDerivedKeyResolvesTest` asks the same of the keys no regex can
// see. Both point the same way: from the code to the catalogue. Nothing pointed
// back, and two things live in that gap.
//
// **A sentence written twice.** `health.no_findings` said "Nothing needs
// attention" and a second line was added beside it saying "Every check passed" —
// by me, while building the screen that finally read one of them. Two lines for
// one situation is two things to translate, two to keep in step, and a coin
// toss about which an operator sees.
//
// **A sentence left behind.** `health.unreachable` and its remedy said what a
// stack that cannot be reached looks like. Then the obstacle enum started
// deriving `connection.no_answer` and its `_action` for exactly that, and the
// older pair became words nobody would ever read again — still translated, still
// reviewed, still in the file.
//
// A sentence waiting for its screen and a sentence its screen stopped saying
// look identical from here. So the ones waiting are named below, one line
// each, with the screen they are waiting for. Adding a line to that table
// costs one sentence of justification, and "it says the same as the line
// above it" is not one.

/**
 * Lines written before the screen that will show them, and what that screen is.
 *
 * Not a suppression list. Each entry is a claim that a sentence exists because
 * somebody decided what a screen would say before building it, and the claim is
 * checkable — the named screen either arrives and reads the line, or the entry
 * is a sentence nobody is coming for.
 *
 * @return array<string, string> key => the screen it is waiting for
 */
function writtenBeforeItsScreen(): array
{
    return [
        // A stack can refuse pairing material outright, which is a
        // different sentence from material this app could not read. No road
        // reaches it yet — both refuse before the stack is asked.
        'connection.pairing_refused' => 'a pairing that the stack itself turns down',

        // The app must not ask again for a permission somebody
        // declined. The sentence for the screen they land on afterwards is
        // written; the screen is the one that offers the typed road instead.
        'device.permission_refused' => 'the screen shown after a permission is declined',

        // A repair an operator agreed to and the stack would not do.
        // `WhatWouldBePutRight` sends the agreement through `Confirmed::against`,
        // and `Mending::agreeTo` answers with a job or an `Obstacle`. Nothing
        // tells a refused repair apart from the other obstacles.
        'health.repair_refused' => 'a repair the stack refused',

        // How old a reading is. The report carries no timestamp today,
        // so this waits on the wire as much as on a screen.
    ];
}

/**
 * Every key the catalogue holds, across every group and every locale.
 *
 * Every locale rather than one, because a line added to a single language is
 * the same orphan with a parity problem on top — and the parity rule would
 * catch that second half but not this one.
 *
 * @return list<string>
 */
function everyLineHeld(): array
{
    $held = [];

    foreach (Catalogue::locales() as $locale) {
        $held = [...$held, ...array_keys(Catalogue::all($locale))];
    }

    return array_values(array_unique($held));
}

/**
 * Everything the application says, as one body of text.
 *
 * Read rather than executed, the same way `L7` reads: a key on a branch that
 * only runs on a handset is exactly where an unread line would hide, and a rule
 * that had to run the line would never see it.
 */
function everythingTheAppSays(): string
{
    $read = [];

    foreach (['app-modules', 'bootstrap'] as $directory) {
        foreach (Tree::filesUnder(Tree::at($directory), '.php') as $file) {
            $read[] = file_get_contents($file);
        }
    }

    return implode("\n", $read);
}

/**
 * Every key a `sprintf` in the sources could build, given every enum case.
 *
 * The derivations are read out of the text rather than listed here, so a ninth
 * enum deriving its keys tomorrow is covered by existing. Cases come from every
 * backed enum the modules publish, which over-approximates — a stem one enum's
 * value produces is treated as reachable whatever enum names the group. That is
 * the safe direction: this rule refuses a line, and an over-approximation makes
 * it refuse fewer rather than more.
 *
 * @return list<string>
 */
function everyKeyADerivationCouldBuild(string $said): array
{
    // One pattern for both shapes a derivation takes — `connection.%s` and
    // `connection.the_code_is_%s` — because what matters is the text either
    // side of the case's value, and where the dot falls in it does not.
    // Hyphens on both sides of this. A case value may carry one — `Waiting`
    // spells `waiting-for-approval`, because the wire does — and a character
    // class that left it out reconstructed none of that enum's keys. The rule
    // then read seven perfectly good lines as orphans, which is the *unsafe*
    // direction: this rule refuses lines, so under-approximating here deletes
    // sentences rather than keeping extra ones.
    preg_match_all("/'([a-z][a-z0-9_.-]*\\.[a-z0-9_-]*)%s([a-z0-9_-]*)'/", $said, $shapes, PREG_SET_ORDER);
    preg_match_all("/case \\w+ = '([a-z0-9_-]+)'/", $said, $cases);

    $built = [];

    foreach ($shapes as [, $before, $after]) {
        foreach ($cases[1] as $value) {
            $built[] = sprintf('%s%s%s', $before, $value, $after);
        }
    }

    return $built;
}

it('L7 — every line the catalogue holds is one the application can show', function (): void {
    $said = everythingTheAppSays();
    $reachable = everyKeyADerivationCouldBuild($said);
    $waiting = writtenBeforeItsScreen();
    $orphans = array_values(array_filter(
        everyLineHeld(),
        static fn(string $key): bool => ! array_key_exists($key, $waiting)
            && ! str_contains($said, sprintf("'%s'", $key))
            && ! in_array($key, $reachable, strict: true),
    ));

    sort($orphans);

    expect($orphans)->toBe([], sprintf(
        "Nothing in the application reads these lines:\n  %s\n\n"
        . 'Either something should show them or they should go. A line nobody reads is '
        . 'still translated into every locale, still reviewed, and still read by whoever '
        . "comes to change the sentence beside it.\n"
        . 'Two shapes produce them: a sentence written twice under different names, and a '
        . "sentence left behind when a screen started saying it another way.\n"
        . 'A line named in `writtenBeforeItsScreen()` is exempt, which costs one sentence '
        . "saying which screen is coming for it (L7).\n",
        implode("\n  ", $orphans),
    ));
});

it('L7 — no line waits for a screen that has already arrived', function (): void {
    // The exemption list cannot be allowed to go stale, for the reason
    // `composer-dependency-analyser.php` writes out about its own: an ignore
    // that no longer applies is a dead line somebody has to wonder about, and
    // the day it stops applying is the only day anybody could tell.
    //
    // Here the cost is worse than a dead line. Every entry is a promise that a
    // screen is coming; an entry whose screen arrived is a promise nobody can
    // tell from one still outstanding, so the list stops being readable as the
    // thing it is. `health.stale` was the first to arrive — written for a
    // report that says when it was taken, and read by the launch screen the day
    // it started opening on a held verdict.
    $said = everythingTheAppSays();
    $reachable = everyKeyADerivationCouldBuild($said);

    $arrived = array_values(array_filter(
        array_keys(writtenBeforeItsScreen()),
        static fn(string $key): bool => str_contains($said, sprintf("'%s'", $key))
            || in_array($key, $reachable, strict: true),
    ));

    sort($arrived);

    expect($arrived)->toBe([], sprintf(
        "These lines are named in `writtenBeforeItsScreen()` and something now reads them:\n  %s\n\n"
        . 'The screen each was waiting for has arrived, so the entry has done its job and '
        . "should go.\n"
        . 'Left in, it is indistinguishable from an entry still waiting — and a list where '
        . "the two read alike stops being evidence of anything.\n",
        implode("\n  ", $arrived),
    ));
});
