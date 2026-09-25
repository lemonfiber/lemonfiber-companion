<?php

declare(strict_types=1);

use Modules\Connection\Api\HowThePairingWent;
use Modules\Connection\Api\HowTheSignInWent;
use Modules\Connection\Api\WhereTheCodeGot;
use Modules\Kernel\Api\AgainstThePins;
use Modules\Kernel\Api\Awaiting;
use Modules\Kernel\Api\Category;
use Modules\Kernel\Api\Conclusion;
use Modules\Kernel\Api\Cost;
use Modules\Kernel\Api\HowAServiceRuns;
use Modules\Kernel\Api\HowFarItGoesBack;
use Modules\Kernel\Api\HowItEnded;
use Modules\Kernel\Api\HowItIsHosted;
use Modules\Kernel\Api\HowItWasRead;
use Modules\Kernel\Api\HowLemonfiberWasInstalled;
use Modules\Kernel\Api\HowLongAgo;
use Modules\Kernel\Api\HowMuchIsShown;
use Modules\Kernel\Api\HowMuchItMatters;
use Modules\Kernel\Api\HowOften;
use Modules\Kernel\Api\HowSureTheTraceIs;
use Modules\Kernel\Api\HowTheLineWasMeasured;
use Modules\Kernel\Api\HowTheStackIsRunning;
use Modules\Kernel\Api\HowToUndoIt;
use Modules\Kernel\Api\Medium;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Overall;
use Modules\Kernel\Api\Permission;
use Modules\Kernel\Api\RateUnit;
use Modules\Kernel\Api\Severity;
use Modules\Kernel\Api\SizeUnit;
use Modules\Kernel\Api\Stage;
use Modules\Kernel\Api\Stance;
use Modules\Kernel\Api\Stream;
use Modules\Kernel\Api\Undoing;
use Modules\Kernel\Api\Waiting;
use Modules\Kernel\Api\WhatACapDoes;
use Modules\Kernel\Api\WhatALineIsAbout;
use Modules\Kernel\Api\WhatAVolumeHolds;
use Modules\Kernel\Api\WhatBecameOfIt;
use Modules\Kernel\Api\WhatGettingItBackCosts;
use Modules\Kernel\Api\WhatHappenedToIt;
use Modules\Kernel\Api\WhatItWouldNeed;
use Modules\Kernel\Api\WhatKeepsItRunning;
use Modules\Kernel\Api\WhatLemonfiberAsksFor;
use Modules\Kernel\Api\WhatToDoWithIt;
use Modules\Kernel\Api\WhereADownloadStands;
use Modules\Kernel\Api\WhereTheLineStands;
use Modules\Kernel\Api\WhereTheMonthStands;
use Modules\Kernel\Api\WhereTheRoomStands;
use Modules\Kernel\Api\WhereThisCopyStands;
use Modules\Kernel\Api\WhetherItGoesThroughTheTunnel;
use Modules\Kernel\Api\WhetherItHoldsASecret;
use Modules\Kernel\Api\WhetherItIsAllowed;
use Modules\Kernel\Api\WhetherItIsHeard;
use Modules\Kernel\Api\WhoSetIt;
use Modules\Kernel\Api\WhyNothingWasScanned;
use Modules\Kernel\Api\WhyNothingWasShared;
use Modules\Operator\Internal\WhereTheFirstRunIs;
use Tests\Support\Catalogue;
use Tests\Support\Tree;

// `L7`'s blind half — the keys no regex can see.
//
// `EveryKeyTheAppNamesResolvesTest` reads catalogue keys out of the *text* of
// the sources, which is the right way to catch the ones nothing executes. It
// says, correctly, that the cure for a mistyped literal is usually to derive the
// key from a closed set instead — `Permission::reason()` builds
// `device.camera_reason` from the case, so there is one spelling and it cannot
// drift.
//
// Taking that cure removes the key from L7's sight. `sprintf('connection.%s',
// $this->value)` matches none of its three patterns, so an enum that gained a
// case and no catalogue line would pass every rule in the suite and show the
// operator `connection.the_new_case` on the glass.
//
// So each enum that builds its own keys is asked here, the way
// `PermissionsAreExplainedTest` asks it of `Permission`: every case, every
// locale, a line that is there and is not empty. The keys come from the case
// rather than being rebuilt in this file — a rule that builds its own copy of a
// key is a rule that can pass while the screen shows the key itself.
//
// **Adding an enum to the table below is the maintenance this asks for**, and it
// is deliberately a table rather than a scan: a scan would have to guess which
// methods return keys, and guessing wrong in the quiet direction is a rule that
// silently stops covering something.

/**
 * Every enum that derives catalogue keys, and the keys each case derives.
 *
 * @return array<string, list<string>> the enum's name => every key it builds
 */
function everyDerivedKey(): array
{
    return [
        HowTheSignInWent::class => aPairPerCase(
            HowTheSignInWent::cases(),
            static fn(HowTheSignInWent $went): array => [$went->said(), $went->remedy()],
        ),
        // `NotYet` is skipped: what a screen says before anything has happened
        // is what that screen is *for*, and the two pairing roads are for
        // different things — so each screen spells its own opening pair and
        // this enum answers only once there is an outcome.
        HowThePairingWent::class => aPairPerCase(
            array_values(array_filter(
                HowThePairingWent::cases(),
                static fn(HowThePairingWent $went): bool => ! $went->isNotYet(),
            )),
            static fn(HowThePairingWent $went): array => [$went->said(), $went->remedy()],
        ),
        // What the screen on each road is for, which no outcome can name.
        HowItWasRead::class => aPairPerCase(
            HowItWasRead::cases(),
            static fn(HowItWasRead $road): array => [$road->askedFor(), $road->howToStart()],
        ),
        WhyNothingWasScanned::class => aPairPerCase(
            WhyNothingWasScanned::cases(),
            static fn(WhyNothingWasScanned $why): array => [$why->saidOnTheScreen(), $why->remedy()],
        ),
        Obstacle::class => aPairPerCase(
            Obstacle::cases(),
            static fn(Obstacle $why): array => [$why->said(), $why->remedy()],
        ),
        WhereTheCodeGot::class => aPairPerCase(
            WhereTheCodeGot::cases(),
            static fn(WhereTheCodeGot $got): array => [$got->saidUnderTheField()],
        ),
        Awaiting::class => aPairPerCase(
            Awaiting::cases(),
            static fn(Awaiting $awaiting): array => [$awaiting->saidOnTheScreen()],
        ),
        Cost::class => aPairPerCase(
            Cost::cases(),
            static fn(Cost $cost): array => [$cost->saidOnTheScreen()],
        ),
        Stance::class => aPairPerCase(
            Stance::cases(),
            static fn(Stance $stance): array => [$stance->saidOnTheScreen()],
        ),
        AgainstThePins::class => aPairPerCase(
            AgainstThePins::cases(),
            static fn(AgainstThePins $pins): array => [$pins->saidOnTheScreen()],
        ),
        Medium::class => aPairPerCase(
            Medium::cases(),
            static fn(Medium $medium): array => [$medium->saidOnTheScreen()],
        ),
        HowItEnded::class => aPairPerCase(
            HowItEnded::cases(),
            static fn(HowItEnded $ending): array => [$ending->saidOnTheScreen()],
        ),
        HowToUndoIt::class => aPairPerCase(
            HowToUndoIt::cases(),
            static fn(HowToUndoIt $undo): array => [$undo->saidOnTheScreen()],
        ),
        Conclusion::class => aPairPerCase(
            Conclusion::cases(),
            static fn(Conclusion $conclusion): array => [$conclusion->saidOnTheScreen()],
        ),
        Severity::class => aPairPerCase(
            Severity::cases(),
            static fn(Severity $severity): array => [$severity->saidOnTheScreen()],
        ),
        HowLongAgo::class => aPairPerCase(
            HowLongAgo::cases(),
            static fn(HowLongAgo $unit): array => [$unit->saidOnTheScreen()],
        ),
        Overall::class => aPairPerCase(
            Overall::cases(),
            static fn(Overall $overall): array => [$overall->saidOnTheScreen()],
        ),
        Category::class => aPairPerCase(
            Category::cases(),
            static fn(Category $category): array => [$category->saidOnTheScreen()],
        ),
        WhatToDoWithIt::class => aPairPerCase(
            WhatToDoWithIt::cases(),
            static fn(WhatToDoWithIt $doing): array => [$doing->saidOnTheScreen()],
        ),
        HowAServiceRuns::class => aPairPerCase(
            HowAServiceRuns::cases(),
            static fn(HowAServiceRuns $runs): array => [$runs->saidOnTheScreen()],
        ),
        HowMuchItMatters::class => aPairPerCase(
            HowMuchItMatters::cases(),
            static fn(HowMuchItMatters $matters): array => [$matters->saidOnTheScreen()],
        ),
        HowTheStackIsRunning::class => aPairPerCase(
            HowTheStackIsRunning::cases(),
            static fn(HowTheStackIsRunning $running): array => [$running->saidOnTheScreen()],
        ),
        SizeUnit::class => aPairPerCase(
            SizeUnit::cases(),
            static fn(SizeUnit $unit): array => [$unit->saidOnTheScreen()],
        ),
        RateUnit::class => aPairPerCase(
            RateUnit::cases(),
            static fn(RateUnit $unit): array => [$unit->saidOnTheScreen()],
        ),
        Stage::class => aPairPerCase(
            Stage::cases(),
            static fn(Stage $stage): array => [$stage->saidOnTheScreen()],
        ),
        HowMuchIsShown::class => aPairPerCase(
            HowMuchIsShown::cases(),
            static fn(HowMuchIsShown $shown): array => [$shown->saidOnTheScreen()],
        ),
        HowOften::class => aPairPerCase(
            HowOften::cases(),
            static fn(HowOften $often): array => [$often->saidOnTheScreen()],
        ),
        Stream::class => aPairPerCase(
            Stream::cases(),
            static fn(Stream $stream): array => [$stream->saidOnTheScreen()],
        ),
        WhatBecameOfIt::class => aPairPerCase(
            WhatBecameOfIt::cases(),
            static fn(WhatBecameOfIt $became): array => [$became->saidOnTheScreen()],
        ),
        Undoing::class => aPairPerCase(
            Undoing::cases(),
            static fn(Undoing $undoing): array => [$undoing->saidOnTheScreen()],
        ),
        Waiting::class => aPairPerCase(
            Waiting::cases(),
            static fn(Waiting $standing): array => [$standing->saidOnTheScreen()],
        ),
        HowItIsHosted::class => aPairPerCase(
            HowItIsHosted::cases(),
            static fn(HowItIsHosted $standing): array => [$standing->saidOnTheScreen()],
        ),
        WhatKeepsItRunning::class => aPairPerCase(
            WhatKeepsItRunning::cases(),
            static fn(WhatKeepsItRunning $manager): array => [$manager->saidOnTheScreen()],
        ),
        HowFarItGoesBack::class => aPairPerCase(
            HowFarItGoesBack::cases(),
            static fn(HowFarItGoesBack $reversal): array => [$reversal->saidOnTheScreen()],
        ),
        WhatLemonfiberAsksFor::class => aPairPerCase(
            WhatLemonfiberAsksFor::cases(),
            static fn(WhatLemonfiberAsksFor $asks): array => [$asks->saidOnTheScreen()],
        ),
        WhetherItIsAllowed::class => aPairPerCase(
            WhetherItIsAllowed::cases(),
            static fn(WhetherItIsAllowed $allowed): array => [$allowed->saidOnTheScreen()],
        ),
        WhetherItIsHeard::class => aPairPerCase(
            WhetherItIsHeard::cases(),
            static fn(WhetherItIsHeard $heard): array => [$heard->saidOnTheScreen()],
        ),
        WhereTheLineStands::class => aPairPerCase(
            WhereTheLineStands::cases(),
            static fn(WhereTheLineStands $stands): array => [$stands->saidOnTheScreen()],
        ),
        HowTheLineWasMeasured::class => aPairPerCase(
            HowTheLineWasMeasured::cases(),
            static fn(HowTheLineWasMeasured $measured): array => [$measured->saidOnTheScreen()],
        ),
        WhatACapDoes::class => aPairPerCase(
            WhatACapDoes::cases(),
            static fn(WhatACapDoes $does): array => [$does->saidOnTheScreen()],
        ),
        WhereTheMonthStands::class => aPairPerCase(
            WhereTheMonthStands::cases(),
            static fn(WhereTheMonthStands $month): array => [$month->saidOnTheScreen()],
        ),
        WhetherItGoesThroughTheTunnel::class => aPairPerCase(
            WhetherItGoesThroughTheTunnel::cases(),
            static fn(WhetherItGoesThroughTheTunnel $tunnel): array => [$tunnel->saidOnTheScreen()],
        ),
        WhetherItHoldsASecret::class => aPairPerCase(
            WhetherItHoldsASecret::cases(),
            static fn(WhetherItHoldsASecret $secret): array => [$secret->saidOnTheScreen()],
        ),
        WhatItWouldNeed::class => aPairPerCase(
            WhatItWouldNeed::cases(),
            static fn(WhatItWouldNeed $case): array => [$case->saidOnTheScreen()],
        ),
        HowSureTheTraceIs::class => aPairPerCase(
            HowSureTheTraceIs::cases(),
            static fn(HowSureTheTraceIs $case): array => [$case->saidOnTheScreen()],
        ),
        WhatHappenedToIt::class => aPairPerCase(
            WhatHappenedToIt::cases(),
            static fn(WhatHappenedToIt $case): array => [$case->saidOnTheScreen()],
        ),
        HowLemonfiberWasInstalled::class => aPairPerCase(
            HowLemonfiberWasInstalled::cases(),
            static fn(HowLemonfiberWasInstalled $case): array => [$case->saidOnTheScreen()],
        ),
        WhereThisCopyStands::class => aPairPerCase(
            WhereThisCopyStands::cases(),
            static fn(WhereThisCopyStands $case): array => [$case->saidOnTheScreen()],
        ),
        WhereTheRoomStands::class => aPairPerCase(
            WhereTheRoomStands::cases(),
            static fn(WhereTheRoomStands $case): array => [$case->saidOnTheScreen()],
        ),
        WhatAVolumeHolds::class => aPairPerCase(
            WhatAVolumeHolds::cases(),
            static fn(WhatAVolumeHolds $case): array => [$case->saidOnTheScreen()],
        ),
        WhatALineIsAbout::class => aPairPerCase(
            WhatALineIsAbout::cases(),
            static fn(WhatALineIsAbout $case): array => [$case->saidOnTheScreen()],
        ),
        WhatGettingItBackCosts::class => aPairPerCase(
            WhatGettingItBackCosts::cases(),
            static fn(WhatGettingItBackCosts $case): array => [$case->saidOnTheScreen()],
        ),
        WhereADownloadStands::class => aPairPerCase(
            WhereADownloadStands::cases(),
            static fn(WhereADownloadStands $case): array => [$case->saidOnTheScreen()],
        ),
        // Both found by the scan below rather than by hand, which is what the
        // scan is for. `Permission` is also asked by
        // `PermissionsAreExplainedTest`, which checks more than the lines
        // existing — the duplicate costs a few assertions and removes the
        // special case that let the other two through.
        Permission::class => aPairPerCase(
            Permission::cases(),
            static fn(Permission $permission): array => [$permission->reason(), $permission->alternative()],
        ),
        WhyNothingWasShared::class => aPairPerCase(
            WhyNothingWasShared::cases(),
            static fn(WhyNothingWasShared $why): array => [$why->saidOnTheScreen(), $why->remedy()],
        ),
        // The first run, which is the one sequence whose steps are copy and
        // nothing else — a step with no sentence behind it is a blank frame
        // between two that read, and the sequence is what it breaks.
        WhereTheFirstRunIs::class => aPairPerCase(
            WhereTheFirstRunIs::cases(),
            static fn(WhereTheFirstRunIs $at): array => [$at->said(), $at->explained()],
        ),
        WhoSetIt::class => aPairPerCase(
            WhoSetIt::cases(),
            static fn(WhoSetIt $who): array => [$who->ofASetting(), $who->ofACheck(), $who->ofAService()],
        ),
    ];
}

/**
 * Every key a set of cases builds, flattened.
 *
 * One helper rather than eight loops, which the complexity gate asked for and
 * which reads better anyway: what differs per enum is *which* keys a case
 * builds, and that is now the only thing written per entry above.
 *
 * @template TCase of UnitEnum
 *
 * @param list<TCase>                $cases
 * @param Closure(TCase): list<string> $keys
 *
 * @return list<string>
 */
function aPairPerCase(array $cases, Closure $keys): array
{
    return array_merge(...array_map($keys, $cases));
}

it('L7 — every key an enum builds for itself is a line the catalogue holds', function (): void {
    $missing = [];
    $asked = [];

    foreach (Catalogue::locales() as $locale) {
        $lines = Catalogue::all($locale);

        foreach (everyDerivedKey() as $enum => $keys) {
            foreach ($keys as $key) {
                $asked[] = sprintf('%s — %s', $key, $locale);

                if (($lines[$key] ?? '') === '') {
                    $missing[] = sprintf('%s — %s (%s)', $key, $locale, $enum);
                }
            }
        }
    }

    // Every entry in the table goes through one helper, so the helper handing
    // back no keys leaves the whole rule asking the catalogue nothing — and a
    // catalogue asked nothing is a catalogue with nothing missing from it.
    // The locale list is not the risk here: it raises where it is empty.
    expect($asked)->not->toBe([], 'no key was asked of any locale, so this rule read nothing');

    sort($missing);

    expect($missing)->toBe([], sprintf(
        "These keys are built from a case and have no line behind them:\n  %s\n\n"
        . 'A derived key cannot be mistyped, which is why deriving it is the cure L7 '
        . 'recommends — but it can be built for a case nobody wrote a sentence for, and '
        . 'no regex over the sources can see that. The operator is shown the key. '
        . "Add the line to every locale under `lang/<locale>/`.\n",
        implode("\n  ", $missing),
    ));
});

it('a case value is the catalogue stem, so the two cannot drift apart', function (): void {
    // The property that makes the derivation worth having. Asserted rather than
    // assumed, because an enum could build `connection.<name>` from something
    // other than its own value — a `match`, a second table, a transformation —
    // and then be exactly the two-spellings-of-one-name this pattern removes.
    foreach (HowTheSignInWent::cases() as $went) {
        expect($went->said())->toBe(sprintf('connection.%s', $went->value), $went->name)
            ->and($went->remedy())->toBe(sprintf('connection.%s_action', $went->value), $went->name);
    }

    foreach (HowThePairingWent::cases() as $went) {
        // `NotYet` is exempt for the reason the table above gives: an outcome
        // cannot say what a screen is for, so it has no key and must not be
        // asked for one.
        if ($went->isNotYet()) {
            continue;
        }

        expect($went->said())->toBe(sprintf('connection.%s', $went->value), $went->name)
            ->and($went->remedy())->toBe(sprintf('connection.%s_action', $went->value), $went->name);
    }

    foreach (HowItWasRead::cases() as $road) {
        expect($road->askedFor())->toBe(sprintf('connection.%s_the_code', $road->value), $road->name)
            ->and($road->howToStart())->toBe(sprintf('connection.%s_the_code_action', $road->value), $road->name);
    }

    foreach (WhereTheCodeGot::cases() as $got) {
        expect($got->saidUnderTheField())->toBe(sprintf('connection.the_code_is_%s', $got->value), $got->name);
    }

    foreach (Conclusion::cases() as $conclusion) {
        expect($conclusion->saidOnTheScreen())
            ->toBe(sprintf('health.conclusion.%s', $conclusion->value), $conclusion->name);
    }

    foreach (Overall::cases() as $overall) {
        expect($overall->saidOnTheScreen())
            ->toBe(sprintf('health.overall.%s', $overall->value), $overall->name);
    }

    foreach (Category::cases() as $category) {
        expect($category->saidOnTheScreen())
            ->toBe(sprintf('health.category.%s', $category->value), $category->name);
    }

    foreach (WhyNothingWasScanned::cases() as $why) {
        expect($why->saidOnTheScreen())->toBe(sprintf('connection.%s', $why->value), $why->name)
            ->and($why->remedy())->toBe(sprintf('connection.%s_action', $why->value), $why->name);
    }

    foreach (WhereTheFirstRunIs::cases() as $at) {
        expect($at->said())->toBe(sprintf('onboarding.%s', $at->value), $at->name)
            ->and($at->explained())->toBe(sprintf('onboarding.%s_explained', $at->value), $at->name);
    }
});

/**
 * The one enum a source file declares, fully qualified. Named for this file.
 *
 * By text rather than by loading the file, because the scan below runs over
 * every module's sources and requiring them would make a rule about catalogue
 * keys depend on every one of them being loadable in this process.
 */
function theEnumDeclaredIn(string $source): ?string
{
    if (preg_match('/^namespace ([^;]+);/m', $source, $under) !== 1) {
        return null;
    }

    if (preg_match('/^enum (\\w+)/m', $source, $called) !== 1) {
        return null;
    }

    return sprintf('%s\\%s', $under[1], $called[1]);
}

it('L7 — every enum that builds a catalogue key is asked above', function (): void {
    // The gap the table's own comment leaves open. `everyDerivedKey()` is
    // maintained by hand for a good reason — a scan would have to guess which
    // methods return keys — but an enum written after the table and never added
    // to it is invisible to every rule in this suite, including the one above.
    //
    // `Waiting` was exactly that: seven cases each building `household.<value>`
    // against a catalogue file that did not exist, passing the whole suite. So
    // the scan does not try to guess *which* keys an enum builds — that is what
    // the table is for — it only asks whether an enum that plainly builds one
    // has been written down at all.
    $stems = array_map(
        static fn(string $file): string => basename($file, '.php'),
        Tree::filesUnder(Tree::at(sprintf('lang/%s', Catalogue::locales()[0])), '.php'),
    );

    // Anything between the group and the placeholder, because a key may be
    // nested: `Undoing` builds `health.undoing.%s` and `WhatBecameOfIt` builds
    // `health.mended.%s`, and a pattern demanding the placeholder immediately
    // after the group saw neither. That is the same under-approximation the
    // catalogue mirror had with hyphens, in the same unsafe direction — a rule
    // that cannot see an enum reports it as absent, and absent here means
    // nobody checks its lines exist.
    $building = sprintf("/sprintf\\(\n?\\s*'(%s)\\.[a-z0-9_.-]*%%s/", implode('|', array_map(preg_quote(...), $stems)));
    $asked = array_keys(everyDerivedKey());
    $missing = [];
    $found = [];

    // The pattern is built out of the catalogue's own file names, so a locale
    // directory that moved leaves it with an empty alternation that matches no
    // enum at all — and an enum this scan cannot see is reported as absent,
    // which here means nobody checks its lines exist.
    expect($stems)->not->toBe([], 'the catalogue holds no file, so the pattern below matches nothing');

    foreach (Tree::filesUnder(Tree::at('app-modules'), '.php') as $file) {
        $source = file_get_contents($file);

        if ($source === false || preg_match('/^enum \w+/m', $source) !== 1) {
            continue;
        }

        if (preg_match($building, $source) !== 1) {
            continue;
        }

        $found[] = $file;
        $name = theEnumDeclaredIn($source);

        if ($name !== null && ! in_array($name, $asked, strict: true)) {
            $missing[] = $name;
        }
    }

    expect($found)->not->toBe([], 'no enum builds a catalogue key, so this rule read nothing');

    sort($missing);

    expect($missing)->toBe([], sprintf(
        "These enums build a catalogue key and are not in `everyDerivedKey()`:\n  %s\n\n"
        . 'Until one is listed there, nothing checks that the lines it names exist — it '
        . 'can ship with no catalogue entry at all and the operator is shown the key. '
        . "Add an entry naming every key each case builds.\n",
        implode("\n  ", $missing),
    ));
});
