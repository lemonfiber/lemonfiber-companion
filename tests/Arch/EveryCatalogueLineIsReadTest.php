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
// What this rule does *not* do is refuse a catalogue written ahead of its
// screen. Writing the words first is how this product decides what a screen
// will say before deciding how it looks, and `backups.`, `updates.` and
// `stacks.` are whole groups of it — a group nothing reads is a feature nobody
// has started, and that is allowed without a word.
//
// A line inside a group that *is* read is the harder case, because the two
// shapes look identical from here: a sentence waiting for its screen, and a
// sentence its screen stopped saying. So those are named below, one line each,
// with the screen they are waiting for. Adding a line to that table is cheap
// and is the point — it costs one sentence of justification, and "it says the
// same as the line above it" is not one.

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
        // `N1-R48`: a stack can refuse pairing material outright, which is a
        // different sentence from material this app could not read. No road
        // reaches it yet — both refuse before the stack is asked.
        'connection.pairing_refused' => 'a pairing that the stack itself turns down',

        // `N4-R4`: the app must not ask again for a permission somebody
        // declined. The sentence for the screen they land on afterwards is
        // written; the screen is the one that offers the typed road instead.
        'device.permission_refused' => 'the screen shown after a permission is declined',

        // `N2-R5`: a repair an operator asked for and the stack would not do.
        // The whole repair flow is specified and unbuilt — `Repair`,
        // `Undoing` and `RepairWasConfirmedAgainstAnOldReading` are all here
        // and all unreached.
        'health.repair_refused' => 'a repair the stack refused',
        'health.undoing.permanent' => 'a repair being confirmed, which says whether it can be taken back',
        'health.undoing.possible' => 'a repair being confirmed, which says whether it can be taken back',

        // `N2-R2`: how old a reading is. The report carries no timestamp today,
        // so this waits on the wire as much as on a screen.
        'health.stale' => 'a report that says when it was taken',
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
    preg_match_all("/'([a-z][a-z0-9_.]*\\.[a-z0-9_]*)%s([a-z0-9_]*)'/", $said, $shapes, PREG_SET_ORDER);
    preg_match_all("/case \\w+ = '([a-z0-9_]+)'/", $said, $cases);

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
    $groups = [];

    foreach (everyLineHeld() as $key) {
        $group = explode('.', $key)[0];
        $read = str_contains($said, sprintf("'%s'", $key)) || in_array($key, $reachable, strict: true);
        $groups[$group][$read ? 'read' : 'unread'][] = $key;
    }

    $orphans = [];

    foreach ($groups as $lines) {
        // A group nothing reads at all is a catalogue written ahead of its
        // screen, which is how this product decides what a screen will say
        // before deciding how it looks. A group with some readers and not
        // others is the other thing: work that moved on and left a sentence.
        if (($lines['read'] ?? []) === []) {
            continue;
        }

        $orphans = [...$orphans, ...($lines['unread'] ?? [])];
    }

    $waiting = writtenBeforeItsScreen();
    $orphans = array_values(array_filter(
        $orphans,
        static fn(string $key): bool => ! array_key_exists($key, $waiting),
    ));

    sort($orphans);

    expect($orphans)->toBe([], sprintf(
        "These lines are in a group the application reads, and nothing reads them:\n  %s\n\n"
        . 'Either something should show them or they should go. A line nobody reads is '
        . 'still translated into every locale, still reviewed, and still read by whoever '
        . "comes to change the sentence beside it.\n"
        . 'Two shapes produce them: a sentence written twice under different names, and a '
        . "sentence left behind when a screen started saying it another way.\n"
        . 'A group with no readers at all is exempt — that is a catalogue written before '
        . "its screen, which is deliberate. So is a line named in `writtenBeforeItsScreen()`, \n"
        . "which costs one sentence saying which screen is coming for it (L7).\n",
        implode("\n  ", $orphans),
    ));
});
