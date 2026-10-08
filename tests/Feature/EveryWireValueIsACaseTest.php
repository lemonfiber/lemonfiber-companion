<?php

declare(strict_types=1);

use Modules\Kernel\Api\Availability;
use Modules\Kernel\Api\Category;
use Modules\Kernel\Api\Conclusion;
use Modules\Kernel\Api\Cost;
use Modules\Kernel\Api\HowAServiceRuns;
use Modules\Kernel\Api\HowAVolumeWasRead;
use Modules\Kernel\Api\HowDriftWasJudged;
use Modules\Kernel\Api\HowFarItGoesBack;
use Modules\Kernel\Api\HowFarTheRemovalReached;
use Modules\Kernel\Api\HowItIsHosted;
use Modules\Kernel\Api\HowItSettled;
use Modules\Kernel\Api\HowItStands;
use Modules\Kernel\Api\HowItStopped;
use Modules\Kernel\Api\HowItWasReached;
use Modules\Kernel\Api\HowLemonfiberWasInstalled;
use Modules\Kernel\Api\HowMuchItMatters;
use Modules\Kernel\Api\HowSeriousAConnectionIs;
use Modules\Kernel\Api\HowSeriousALineIs;
use Modules\Kernel\Api\HowSureTheTraceIs;
use Modules\Kernel\Api\HowTheDoorWasChosen;
use Modules\Kernel\Api\HowTheImportLinked;
use Modules\Kernel\Api\HowTheLineWasMeasured;
use Modules\Kernel\Api\HowTheStackIsRunning;
use Modules\Kernel\Api\HowWellADeviceIsServed;
use Modules\Kernel\Api\Medium;
use Modules\Kernel\Api\Overall;
use Modules\Kernel\Api\Severity;
use Modules\Kernel\Api\Stage;
use Modules\Kernel\Api\Stance;
use Modules\Kernel\Api\Standing;
use Modules\Kernel\Api\Stream;
use Modules\Kernel\Api\Waiting;
use Modules\Kernel\Api\WalkthroughStep;
use Modules\Kernel\Api\WhatACapDoes;
use Modules\Kernel\Api\WhatALineIsAbout;
use Modules\Kernel\Api\WhatAVolumeHolds;
use Modules\Kernel\Api\WhatBecameOfIt;
use Modules\Kernel\Api\WhatBecameOfTheChoice;
use Modules\Kernel\Api\WhatBecomesOfUnrated;
use Modules\Kernel\Api\WhatGettingItBackCosts;
use Modules\Kernel\Api\WhatGoingBackDoes;
use Modules\Kernel\Api\WhatHappenedToIt;
use Modules\Kernel\Api\WhatItFaces;
use Modules\Kernel\Api\WhatItWouldNeed;
use Modules\Kernel\Api\WhatKeepsItRunning;
use Modules\Kernel\Api\WhatLemonfiberAsksFor;
use Modules\Kernel\Api\WhatSortItIs;
use Modules\Kernel\Api\WhatTheHandoffNeedsNext;
use Modules\Kernel\Api\WhatToDoNext;
use Modules\Kernel\Api\WhereAConnectionStands;
use Modules\Kernel\Api\WhereACredentialStands;
use Modules\Kernel\Api\WhereADownloadStands;
use Modules\Kernel\Api\WhereTheAskingStands;
use Modules\Kernel\Api\WhereTheFrontDoorStands;
use Modules\Kernel\Api\WhereTheHandoffStands;
use Modules\Kernel\Api\WhereTheInvitationStands;
use Modules\Kernel\Api\WhereTheLineStands;
use Modules\Kernel\Api\WhereTheMonthStands;
use Modules\Kernel\Api\WhereTheRoomStands;
use Modules\Kernel\Api\WhereTheUninstallStands;
use Modules\Kernel\Api\WhereTheWalkthroughIs;
use Modules\Kernel\Api\WhereThisCopyStands;
use Modules\Kernel\Api\WhetherTheyCanAsk;
use Modules\Kernel\Api\WhichRemoval;
use Modules\Kernel\Api\WhichWalk;
use Modules\Kernel\Api\WhoMadeACredential;
use Modules\Kernel\Api\WhoSettledIt;
use Modules\Kernel\Api\WhyTheWalkthroughStopped;
use Tests\Support\ApiSurface;
use Tests\Support\Module;
use Tests\Support\TheGeneratedEnvelopes;
use Tests\Support\TheWireUnions;

/**
 * The app reads every value the contract says a stack may send.
 *
 * Five enums here are the wire's values rather than this application's words:
 * `Category`, `Conclusion`, `Overall`, `Severity` and `Standing` each exist to
 * name what a report carries, and each is written out by hand. `OverallTest` says so plainly —
 * "the values are the wire's" — and pins them, which holds the enum against
 * whoever edits it and against nothing else.
 *
 * What that leaves open is the contract moving. A stack that grows a tenth
 * health category sends it in every report; `Category::from()` is handed a value
 * it has no case for and raises, and the failure arrives as a crash on a screen
 * rather than as this file going red. The enum is correct about a contract that
 * is no longer the one being spoken.
 *
 * So the values are read from the generated envelope, which is the contract as
 * this application has it: `src/Generated` is produced from
 * `contract/web-api.contract.json` and regenerating is what proves it. The
 * comparison is on sets — the order each enum declares is its own decision, made
 * for a reader and pinned by its own test.
 *
 * Read as tokens rather than as prose: the shapes matched are a quoted literal
 * union after a named field and a quoted literal after `outcome:`, in a file
 * written by a generator. Each extraction is asserted to have found something,
 * so a change to the generator's format fails here rather than quietly matching
 * nothing.
 */

it('the reading that decides all five can say something else', function (): void {
    // The five rules below have only ever asked this reader about a generated
    // file where every answer is the right one, and nothing can be planted for
    // them: the envelope is somebody else's file in `vendor/`, restored by
    // composer rather than by this harness. So the reading is handed the text.
    $envelope = <<<'PHP'
        <?php
        /**
         * @phpstan-type Finding array{
         *   category: 'storage'|'network'|'weather',
         *   severity?: 'note'|'warning',
         * }
         */
        PHP;

    expect(TheWireUnions::unionIn($envelope, 'category'))->toBe(['network', 'storage', 'weather']);

    // Sorted, because the comparison is on sets: the order the contract writes
    // them in is the generator's business and the order an enum declares them in
    // is a decision made for a reader.
    expect(TheWireUnions::unionIn($envelope, 'severity'))->toBe(['note', 'warning']);

    // A field the envelope does not mention is nothing found, not an empty union
    // — and the five rules below each assert that separately, which is what makes
    // a change to the generator's format fail rather than quietly match nothing.
    expect(TheWireUnions::unionIn($envelope, 'nothing'))->toBe([]);

    // A single literal is not a union. The pattern wants at least one `|`, so a
    // field the contract has narrowed to one value is reported as unreadable
    // rather than as a one-case enum.
    expect(TheWireUnions::unionIn("state: 'settled',", 'state'))->toBe([]);
});

it('a field declared twice with different unions is refused', function (): void {
    // The path the comment on `TheWireUnions::unionIn()` describes and nothing had taken. Two
    // occurrences that disagree are a question this cannot answer, and answering
    // with whichever came first would be the quiet half-right result these rules
    // exist to refuse — it would compare the enum against half a contract and
    // pass.
    $disagreeing = "category: 'storage'|'network',\ncategory: 'storage'|'weather',";

    expect(TheWireUnions::unionIn($disagreeing, 'category'))->toBe([]);

    // And the same field twice saying the same thing is one answer, not none:
    // the generator repeats a shape wherever it is used.
    $agreeing = "category: 'storage'|'network',\ncategory: 'storage'|'network',";

    expect(TheWireUnions::unionIn($agreeing, 'category'))->toBe(['network', 'storage']);
});

it('every state a stack declares a capability in has a case', function (): void {
    // Read from the capabilities envelope by name, and as the value of a map
    // rather than a field: each path is a key, and the union is what any key
    // may say. The reading below is `unionIn`'s, shaped for that one place.
    preg_match_all(
        "/\\bcapabilities: array<string, ((?:'[a-z_-]+'\\|)+'[a-z_-]+')>/",
        TheGeneratedEnvelopes::theGeneratedEnvelope('CapabilitiesEnvelope'),
        $found,
    );
    $union = [];

    if (count(array_unique($found[1])) === 1) {
        preg_match_all("/'([a-z_-]+)'/", $found[1][0], $literals);
        $union = $literals[1];
        sort($union);
    }

    expect($union)->not->toBe([], 'no capability state union was found in the generated envelope');
    expect(TheWireUnions::valuesOf(Availability::cases()))->toBe($union);
});

it('every request standing the contract describes has a case', function (): void {
    // The contract calls this `state` too, in a different envelope and with a
    // different union — a problem's standing and a request's. Read from the
    // household envelope by name rather than by sweeping the generated files,
    // because a sweep would find two occurrences that disagree and honestly
    // answer nothing about either.
    $union = TheWireUnions::unionIn(TheGeneratedEnvelopes::theGeneratedHouseholdEnvelope(), 'state');

    expect($union)->not->toBe([], 'no request state union was found in the generated envelope');
    expect(TheWireUnions::valuesOf(Waiting::cases()))->toBe($union);
});

it('the verdict outcomes are collected across arms', function (): void {
    // `outcome` is read as single literals rather than as a union, because the
    // verdict is a union of object shapes and each arm fixes it to one value of
    // its own. Two arms is the case that distinguishes this from `unionIn`.
    $verdict = "array{outcome: 'passed', at: string}|array{outcome: 'failed', why: string}";

    expect(TheWireUnions::outcomesIn($verdict))->toBe(['failed', 'passed']);
    expect(TheWireUnions::outcomesIn('nothing here'))->toBe([]);
});

it('every health category the contract describes has a case', function (): void {
    expect(TheWireUnions::wireUnion('category'))->not->toBe([], 'no category union was found in the generated envelope');
    expect(TheWireUnions::valuesOf(Category::cases()))->toBe(TheWireUnions::wireUnion('category'));
});

it('every verdict the contract describes has a case', function (): void {
    expect(TheWireUnions::wireOutcomes())->not->toBe([], 'no verdict outcome was found in the generated envelope');
    expect(TheWireUnions::valuesOf(Conclusion::cases()))->toBe(TheWireUnions::wireOutcomes());
});

it('every repair outcome the contract describes has a case', function (): void {
    // A union of object shapes, each arm fixing `outcome` to a literal of its
    // own, as a verdict is — so the literal-union rule below cannot see it, and
    // for as long as this rule did not exist the stack's `unmanaged` arrived
    // at an enum with no case for it and refused the whole answer.
    $outcomes = TheWireUnions::outcomesIn(TheGeneratedEnvelopes::theGeneratedRepairEnvelope());

    expect($outcomes)->not->toBe([], 'no repair outcome was found in the generated envelope');
    expect(TheWireUnions::valuesOf(WhatBecameOfIt::cases()))->toBe($outcomes);
});

it('every word the health summary may stand at has a case', function (): void {
    $standings = TheWireUnions::unionIn(TheGeneratedEnvelopes::theGeneratedHealthSummary(), 'standing');

    expect($standings)->not->toBe([], 'no standing union was found in the generated health summary');
    expect(TheWireUnions::valuesOf(HowItStands::cases()))->toBe($standings);
});

it('every overall the contract describes has a case', function (): void {
    expect(TheWireUnions::wireUnion('overall'))->not->toBe([], 'no overall union was found in the generated envelope');
    expect(TheWireUnions::valuesOf(Overall::cases()))->toBe(TheWireUnions::wireUnion('overall'));
});

it('every severity the contract describes has a case', function (): void {
    expect(TheWireUnions::wireUnion('severity'))->not->toBe([], 'no severity union was found in the generated envelope');
    expect(TheWireUnions::valuesOf(Severity::cases()))->toBe(TheWireUnions::wireUnion('severity'));
});

it('every settler the contract describes has a case', function (): void {
    // Read from the wiring envelope, where the word is declared. `whose` says
    // which of the stack and the operator resolved a contested capability, and
    // the two may not be flattened — so an arm the contract grew that nothing
    // here has a case for must fail rather than render as the other one.
    $settlers = TheWireUnions::unionIn(TheGeneratedEnvelopes::theGeneratedWiringEnvelope(), 'whose');

    expect($settlers)->not->toBe([], 'no whose union was found in the generated envelope');
    expect(TheWireUnions::valuesOf(WhoSettledIt::cases()))->toBe($settlers);
});

it('every settlement the contract describes has a case', function (): void {
    // The five are tags on objects rather than one union, so they are gathered
    // by tag. A word the contract adds and this does not have is the failure
    // that matters: `contested` is the core declining to choose, and an arm
    // nothing reads would render as whichever arm the reader fell through to.
    $settlements = TheWireUnions::theSettlementsIn(TheGeneratedEnvelopes::theGeneratedWiringEnvelope());

    expect($settlements)->not->toBe([], 'no settlement tag was found in the generated envelope');
    expect(TheWireUnions::valuesOf(HowItSettled::cases()))->toBe($settlements);
});

it('every way one service reaches another has a case', function (): void {
    // Whose decision it was: a capability the core resolved, or a name somebody
    // gave. A plugin may not create the second, so an arm the contract grew
    // that nothing here reads would render an instruction as a deduction.
    $reaches = TheWireUnions::theReachesIn(TheGeneratedEnvelopes::theGeneratedWiringEnvelope());

    expect($reaches)->not->toBe([], 'no reach tag was found in the generated envelope');
    expect(TheWireUnions::valuesOf(HowItWasReached::cases()))->toBe($reaches);
});

it('every kind of stopped the dashboard describes has a case', function (): void {
    // Read from the dashboard, whose `stuck` rows carry it. The cases are the
    // stack's order as well as its words, worst first, so a kind added
    // anywhere but the end fails here too.
    $stalls = TheWireUnions::unionIn(TheGeneratedEnvelopes::theGeneratedEnvelope('DashboardEnvelope'), 'stall');

    expect($stalls)->not->toBe([], 'no stall union was found in the generated dashboard');
    expect(TheWireUnions::valuesOf(HowItStopped::cases()))->toBe($stalls);
});

it('every stage the contract describes has a case', function (): void {
    // Read from the stuck envelope rather than the doctor one, which is the
    // first time these rules have looked at a second file. The union is only
    // declared where it is used, so asking the doctor envelope about `stage`
    // answers `[]` — which is the same answer a renamed field gives, and is why
    // the assertion below insists something was found before comparing.
    $stages = TheWireUnions::unionIn(TheGeneratedEnvelopes::theGeneratedStuckEnvelope(), 'stage');

    expect($stages)->not->toBe([], 'no stage union was found in the generated envelope');
    expect(TheWireUnions::valuesOf(Stage::cases()))->toBe($stages);
});

it('every medium the contract describes has a case', function (): void {
    // Read from the held envelope, the way the stage and the stream are read
    // from theirs: a union is declared only where it is used, so asking any
    // other envelope about `medium` answers `[]` — the same answer a renamed
    // field gives, which is why something must be found before comparing.
    $media = TheWireUnions::unionIn(TheGeneratedEnvelopes::theGeneratedHeldEnvelope(), 'medium');

    expect($media)->not->toBe([], 'no medium union was found in the generated envelope');
    expect(TheWireUnions::valuesOf(Medium::cases()))->toBe($media);
});

it('every stream the contract describes has a case', function (): void {
    // A third generated envelope, read the way the stuck one above is. A union
    // is only declared where it is used, so asking any other envelope about
    // `stream` answers `[]` — the same answer a renamed field gives, which is
    // why the assertion insists something was found before comparing.
    $streams = TheWireUnions::unionIn(TheGeneratedEnvelopes::theGeneratedLogEnvelope(), 'stream');

    expect($streams)->not->toBe([], 'no stream union was found in the generated envelope');
    expect(TheWireUnions::valuesOf(Stream::cases()))->toBe($streams);
});

it('every severity a log line can declare has a case', function (): void {
    // Read off the log envelope, where the union is declared; the space
    // envelope's `level` is a different field under the same word.
    $levels = TheWireUnions::unionIn(TheGeneratedEnvelopes::theGeneratedLogEnvelope(), 'level');

    expect($levels)->not->toBe([], 'no level union was found in the generated envelope');
    expect(TheWireUnions::valuesOf(HowSeriousALineIs::cases()))->toBe($levels);
});

it('every cost the contract describes has a case', function (): void {
    $costs = TheWireUnions::unionIn(TheGeneratedEnvelopes::theGeneratedConfigEnvelope(), 'cost');

    expect($costs)->not->toBe([], 'no cost union was found in the generated envelope');
    expect(TheWireUnions::valuesOf(Cost::cases()))->toBe($costs);
});

it('every stance the contract describes has a case', function (): void {
    // The one where a missing case would be worst. A stance this app did not
    // know would be refused as unreadable, which is correct — but the reason
    // to hold the set to the wire here is the pair the enum exists to keep
    // apart: `unchanged` and `applied` both mean the setting holds what was
    // asked for, and a contract that grew a third of those would need reading
    // rather than guessing at.
    $stances = TheWireUnions::unionIn(TheGeneratedEnvelopes::theGeneratedConfigEnvelope(), 'stance');

    expect($stances)->not->toBe([], 'no stance union was found in the generated envelope');
    expect(TheWireUnions::valuesOf(Stance::cases()))->toBe($stances);
});

it('every way a service can be running has a case', function (): void {
    $states = TheWireUnions::unionIn(TheGeneratedEnvelopes::theGeneratedStatusEnvelope(), 'state');

    expect($states)->not->toBe([], 'no service state union was found in the generated envelope');
    expect(TheWireUnions::valuesOf(HowAServiceRuns::cases()))->toBe($states);
});

it('every criticality the contract describes has a case', function (): void {
    $matters = TheWireUnions::unionIn(TheGeneratedEnvelopes::theGeneratedStatusEnvelope(), 'criticality');

    expect($matters)->not->toBe([], 'no criticality union was found in the generated envelope');
    expect(TheWireUnions::valuesOf(HowMuchItMatters::cases()))->toBe($matters);
});

it('every condition the whole stack can be in has a case', function (): void {
    $conditions = TheWireUnions::unionIn(TheGeneratedEnvelopes::theGeneratedStatusEnvelope(), 'condition');

    expect($conditions)->not->toBe([], 'no condition union was found in the generated envelope');
    expect(TheWireUnions::valuesOf(HowTheStackIsRunning::cases()))->toBe($conditions);
});

it('every way a command can be hosted has a case', function (): void {
    // `standing` rather than `state`, which is the `hosting` envelope's own
    // word for it and is a third union again — a problem's standing and a
    // household request's are already two, under the wire's `state`. Naming the
    // envelope here is what keeps the three from being read as one.
    $hosted = TheWireUnions::unionIn(TheGeneratedEnvelopes::theGeneratedHostingEnvelope(), 'standing');

    expect($hosted)->not->toBe([], 'no hosting standing union was found in the generated envelope');
    expect(TheWireUnions::valuesOf(HowItIsHosted::cases()))->toBe($hosted);
});

it('every way a change can be put back has a case', function (): void {
    // `reversal` became a named union in the contract, so the three words this
    // app reads are held to the wire rather than to a sentence describing it.
    $reversals = TheWireUnions::unionIn(TheGeneratedEnvelopes::theGeneratedHistoryEnvelope(), 'reversal');

    expect($reversals)->not->toBe([], 'no reversal union was found in the generated envelope');
    expect(TheWireUnions::valuesOf(HowFarItGoesBack::cases()))->toBe($reversals);
});

it('everything putting one change back can do has a case', function (): void {
    // `does` tags each arm of the action a reversal carries, so the words are
    // gathered by arm rather than read as one union.
    $does = TheWireUnions::theArmsIn(TheGeneratedEnvelopes::theGeneratedEnvelope('UndoEnvelope'), 'does');

    expect($does)->not->toBe([], 'no reversal arm was found in the generated envelope');
    expect(TheWireUnions::valuesOf(WhatGoingBackDoes::cases()))->toBe($does);
});

it('every request lemonfiber makes on its own account has a case', function (): void {
    // `reach` on the wire, and the closed set is the stack's claim: an eighth
    // request is one somebody decided to add, and this is where the app hears
    // of it rather than drawing it under the nearest name.
    $asks = TheWireUnions::unionIn(TheGeneratedEnvelopes::theGeneratedOutboundEnvelope(), 'reach');

    expect($asks)->not->toBe([], 'no reach union was found in the generated envelope');
    expect(TheWireUnions::valuesOf(WhatLemonfiberAsksFor::cases()))->toBe($asks);
});

it('every state the shared line can be in has a case', function (): void {
    $words = TheWireUnions::unionIn(TheGeneratedEnvelopes::theGeneratedBandwidthEnvelope(), 'restraint');

    expect($words)->not->toBe([], 'no restraint union was found in the generated envelope');
    expect(TheWireUnions::valuesOf(WhereTheLineStands::cases()))->toBe($words);
});

it('every way the line\'s capacity can have been arrived at has a case', function (): void {
    $words = TheWireUnions::unionIn(TheGeneratedEnvelopes::theGeneratedBandwidthEnvelope(), 'source');

    expect($words)->not->toBe([], 'no source union was found in the generated envelope');
    expect(TheWireUnions::valuesOf(HowTheLineWasMeasured::cases()))->toBe($words);
});

it('everything reaching a cap can do has a case', function (): void {
    $words = TheWireUnions::unionIn(TheGeneratedEnvelopes::theGeneratedBandwidthEnvelope(), 'exceeded');

    expect($words)->not->toBe([], 'no exceeded union was found in the generated envelope');
    expect(TheWireUnions::valuesOf(WhatACapDoes::cases()))->toBe($words);
});

it('everywhere a month can stand against its cap has a case', function (): void {
    $words = TheWireUnions::unionIn(TheGeneratedEnvelopes::theGeneratedBandwidthEnvelope(), 'reached');

    expect($words)->not->toBe([], 'no reached union was found in the generated envelope');
    expect(TheWireUnions::valuesOf(WhereTheMonthStands::cases()))->toBe($words);
});

it('everywhere a machine or a volume can stand for room has a case', function (): void {
    $words = TheWireUnions::unionIn(TheGeneratedEnvelopes::theGeneratedSpaceEnvelope(), 'level');

    expect($words)->not->toBe([], 'no level union was found in the generated envelope');
    expect(TheWireUnions::valuesOf(WhereTheRoomStands::cases()))->toBe($words);
});

it('every volume the stack watches has a case', function (): void {
    $words = TheWireUnions::unionIn(TheGeneratedEnvelopes::theGeneratedSpaceEnvelope(), 'role');

    expect($words)->not->toBe([], 'no role union was found in the generated envelope');
    expect(TheWireUnions::valuesOf(WhatAVolumeHolds::cases()))->toBe($words);
});

it('everything getting room back can cost has a case', function (): void {
    $words = TheWireUnions::unionIn(TheGeneratedEnvelopes::theGeneratedSpaceEnvelope(), 'reclaim');

    expect($words)->not->toBe([], 'no reclaim union was found in the generated envelope');
    expect(TheWireUnions::valuesOf(WhatGettingItBackCosts::cases()))->toBe($words);
});

it('every category a line of the account can be has a case', function (): void {
    $words = TheWireUnions::theArmsIn(TheGeneratedEnvelopes::theGeneratedSpaceEnvelope(), 'of');

    expect($words)->not->toBe([], 'no category arm was found in the generated envelope');
    expect(TheWireUnions::valuesOf(WhatALineIsAbout::cases()))->toBe($words);
});

it('everywhere a download can stand has a case', function (): void {
    $words = TheWireUnions::theArmsIn(TheGeneratedEnvelopes::theGeneratedSpaceEnvelope(), 'standing');

    expect($words)->not->toBe([], 'no standing arm was found in the generated envelope');
    expect(TheWireUnions::valuesOf(WhereADownloadStands::cases()))->toBe($words);
});

it('every kind of reading a volume can have has a case', function (): void {
    $words = TheWireUnions::theArmsIn(TheGeneratedEnvelopes::theGeneratedSpaceEnvelope(), 'as');

    expect($words)->not->toBe([], 'no reading arm was found in the generated envelope');
    expect(TheWireUnions::valuesOf(HowAVolumeWasRead::cases()))->toBe($words);
});

it('everything a profile left out of a start can need has a case', function (): void {
    $words = TheWireUnions::unionIn(TheGeneratedEnvelopes::theGeneratedEnvelope('PreviewEnvelope'), 'needs');

    expect($words)->not->toBe([], 'no needs union was found in the generated envelope');
    expect(TheWireUnions::valuesOf(WhatItWouldNeed::cases()))->toBe($words);
});

it('every way a trace can be sure of its item has a case', function (): void {
    $words = TheWireUnions::unionIn(TheGeneratedEnvelopes::theGeneratedTraceEnvelope(), 'confidence');

    expect($words)->not->toBe([], 'no confidence union was found in the generated envelope');
    expect(TheWireUnions::valuesOf(HowSureTheTraceIs::cases()))->toBe($words);
});

it('everything a traced item\'s history can record has a case', function (): void {
    $words = TheWireUnions::unionIn(TheGeneratedEnvelopes::theGeneratedTraceEnvelope(), 'outcome');

    expect($words)->not->toBe([], 'no outcome union was found in the generated envelope');
    expect(TheWireUnions::valuesOf(WhatHappenedToIt::cases()))->toBe($words);
});

it('every step a walkthrough can narrate or stop at has a case', function (): void {
    $words = TheWireUnions::unionIn(TheGeneratedEnvelopes::theGeneratedEnvelope('WalkthroughEnvelope'), 'step');

    expect($words)->not->toBe([], 'no step union was found in the generated envelope');
    expect(TheWireUnions::valuesOf(WalkthroughStep::cases()))->toBe($words);
});

it('everywhere a walkthrough can end up has a case', function (): void {
    $words = TheWireUnions::unionIn(TheGeneratedEnvelopes::theGeneratedEnvelope('WalkthroughEnvelope'), 'state');

    expect($words)->not->toBe([], 'no state union was found in the generated envelope');
    expect(TheWireUnions::valuesOf(WhereTheWalkthroughIs::cases()))->toBe($words);
});

it('every walk a stack can be offered has a case', function (): void {
    $words = TheWireUnions::unionIn(TheGeneratedEnvelopes::theGeneratedEnvelope('WalkthroughEnvelope'), 'shape');

    expect($words)->not->toBe([], 'no shape union was found in the generated envelope');
    expect(TheWireUnions::valuesOf(WhichWalk::cases()))->toBe($words);
});

it('everything an import can have done with the file has a case', function (): void {
    $words = TheWireUnions::unionIn(TheGeneratedEnvelopes::theGeneratedEnvelope('WalkthroughEnvelope'), 'link');

    expect($words)->not->toBe([], 'no link union was found in the generated envelope');
    expect(TheWireUnions::valuesOf(HowTheImportLinked::cases()))->toBe($words);
});

it('every reason a walkthrough can stop for has a case', function (): void {
    $words = TheWireUnions::unionIn(TheGeneratedEnvelopes::theGeneratedEnvelope('WalkthroughEnvelope'), 'reason');

    expect($words)->not->toBe([], 'no reason union was found in the generated envelope');
    expect(TheWireUnions::valuesOf(WhyTheWalkthroughStopped::cases()))->toBe($words);
});

it('everything a walkthrough can hand over to has a case', function (): void {
    // A list of the union rather than the union itself, which `unionIn` does
    // not read, so the list is matched here.
    preg_match_all("/\\bnext: list<((?:'[a-z_-]+'\\|)+'[a-z_-]+')>/", TheGeneratedEnvelopes::theGeneratedEnvelope('WalkthroughEnvelope'), $found);
    preg_match_all("/'([a-z_-]+)'/", $found[1] === [] ? '' : $found[1][0], $literals);
    $words = $literals[1];
    sort($words);

    expect($words)->not->toBe([], 'no next union was found in the generated envelope');
    expect(TheWireUnions::valuesOf(WhatToDoNext::cases()))->toBe($words);
});

it('every way lemonfiber can have been installed has a case', function (): void {
    $words = TheWireUnions::unionIn(TheGeneratedEnvelopes::theGeneratedSelfUpdateEnvelope(), 'installed');

    expect($words)->not->toBe([], 'no installed union was found in the generated envelope');
    expect(TheWireUnions::valuesOf(HowLemonfiberWasInstalled::cases()))->toBe($words);
});

it('everywhere a copy of lemonfiber can stand has a case', function (): void {
    $words = TheWireUnions::unionIn(TheGeneratedEnvelopes::theGeneratedSelfUpdateEnvelope(), 'standing');

    expect($words)->not->toBe([], 'no standing union was found in the generated envelope');
    expect(TheWireUnions::valuesOf(WhereThisCopyStands::cases()))->toBe($words);
});

it('every state a credential can be in has a case', function (): void {
    $words = TheWireUnions::unionIn(TheGeneratedEnvelopes::theGeneratedEnvelope('CredentialsEnvelope'), 'state');

    expect($words)->not->toBe([], 'no state union was found in the generated envelope');
    expect(TheWireUnions::valuesOf(WhereACredentialStands::cases()))->toBe($words);
});

it('everybody who can have produced a credential has a case', function (): void {
    $words = TheWireUnions::unionIn(TheGeneratedEnvelopes::theGeneratedEnvelope('CredentialsEnvelope'), 'origin');

    expect($words)->not->toBe([], 'no origin union was found in the generated envelope');
    expect(TheWireUnions::valuesOf(WhoMadeACredential::cases()))->toBe($words);
});

it('every rating a device can be given has a case', function (): void {
    $words = TheWireUnions::unionIn(TheGeneratedEnvelopes::theGeneratedEnvelope('ClientsEnvelope'), 'support');

    expect($words)->not->toBe([], 'no support union was found in the generated envelope');
    expect(TheWireUnions::valuesOf(HowWellADeviceIsServed::cases()))->toBe($words);
});

it('everywhere a hand-off can stand has a case', function (): void {
    $words = TheWireUnions::unionIn(TheGeneratedEnvelopes::theGeneratedEnvelope('HandoffEnvelope'), 'state');

    expect($words)->not->toBe([], 'no state union was found in the generated envelope');
    expect(TheWireUnions::valuesOf(WhereTheHandoffStands::cases()))->toBe($words);
});

it('every remedy a hand-off can name has a case, beside the one for naming none', function (): void {
    $words = TheWireUnions::unionIn(TheGeneratedEnvelopes::theGeneratedEnvelope('HandoffEnvelope'), 'remedy');
    $named = array_values(array_filter(WhatTheHandoffNeedsNext::cases(), static fn(WhatTheHandoffNeedsNext $next): bool => $next !== WhatTheHandoffNeedsNext::Nothing));

    expect($words)->not->toBe([], 'no remedy union was found in the generated envelope');
    expect(TheWireUnions::valuesOf($named))->toBe($words);
});

it('everywhere a front door can stand has a case', function (): void {
    $words = TheWireUnions::unionIn(TheGeneratedEnvelopes::theGeneratedEnvelope('FrontDoorEnvelope'), 'standing');

    expect($words)->not->toBe([], 'no standing union was found in the generated envelope');
    expect(TheWireUnions::valuesOf(WhereTheFrontDoorStands::cases()))->toBe($words);
});

it('everything a service can be to the household has a case', function (): void {
    $words = TheWireUnions::unionIn(TheGeneratedEnvelopes::theGeneratedEnvelope('FrontDoorEnvelope'), 'facing');

    expect($words)->not->toBe([], 'no facing union was found in the generated envelope');
    expect(TheWireUnions::valuesOf(WhatItFaces::cases()))->toBe($words);
});

it('every way a wiring run can have judged drift has a case', function (): void {
    $words = TheWireUnions::unionIn(TheGeneratedEnvelopes::theGeneratedEnvelope('SeedEnvelope'), 'assessment');

    expect($words)->not->toBe([], 'no assessment union was found in the generated envelope');
    expect(TheWireUnions::valuesOf(HowDriftWasJudged::cases()))->toBe($words);
});

it('every state a wired connection can end in has a case', function (): void {
    // Fourteen arms, several with hyphens, and each drawn in a sentence of its
    // own: a state this app did not know is refused rather than read as the
    // nearest, so a new one on the wire has to be met here first.
    $words = TheWireUnions::theArmsIn(TheGeneratedEnvelopes::theGeneratedEnvelope('SeedEnvelope'), 'state');

    expect($words)->toHaveCount(14);
    expect(TheWireUnions::valuesOf(WhereAConnectionStands::cases()))->toBe($words);
});

it('every severity a wired connection can carry has a case', function (): void {
    $words = TheWireUnions::theArmsIn(TheGeneratedEnvelopes::theGeneratedEnvelope('SeedEnvelope'), 'severity');

    expect($words)->not->toBe([], 'no severity arms were found in the generated envelope');
    expect(TheWireUnions::valuesOf(HowSeriousAConnectionIs::cases()))->toBe($words);
});

it('every way a front door can have come to be has a case', function (): void {
    $words = TheWireUnions::theArmsIn(TheGeneratedEnvelopes::theGeneratedEnvelope('FrontDoorEnvelope'), 'chosen');

    expect($words)->not->toBe([], 'no chosen arms were found in the generated envelope');
    expect(TheWireUnions::valuesOf(HowTheDoorWasChosen::cases()))->toBe($words);
});

it('everything an invitation can find where it was going has a case', function (): void {
    $words = TheWireUnions::unionIn(TheGeneratedEnvelopes::theGeneratedEnvelope('InvitationEnvelope'), 'standing');

    expect($words)->not->toBe([], 'no standing union was found in the generated envelope');
    expect(TheWireUnions::valuesOf(WhereTheInvitationStands::cases()))->toBe($words);
});

it('everything the request service can have been told has a case, on the invitation and on what it granted', function (): void {
    $linked = TheWireUnions::unionIn(TheGeneratedEnvelopes::theGeneratedEnvelope('InvitationEnvelope'), 'linked');
    $requesting = TheWireUnions::unionIn(TheGeneratedEnvelopes::theGeneratedEnvelope('InvitationEnvelope'), 'requesting');

    expect($linked)->not->toBe([], 'no linked union was found in the generated envelope');
    expect(TheWireUnions::valuesOf(WhetherTheyCanAsk::cases()))->toBe($linked)
        ->and($requesting)->toBe($linked);
});

it('everything that can become of unrated material has a case', function (): void {
    $words = TheWireUnions::unionIn(TheGeneratedEnvelopes::theGeneratedEnvelope('InvitationEnvelope'), 'unrated');

    expect($words)->not->toBe([], 'no unrated union was found in the generated envelope');
    expect(TheWireUnions::valuesOf(WhatBecomesOfUnrated::cases()))->toBe($words);
});

it('everywhere taking somebody out can have reached has a case', function (): void {
    $words = TheWireUnions::unionIn(TheGeneratedEnvelopes::theGeneratedEnvelope('RemovalEnvelope'), 'revoked');

    expect($words)->not->toBe([], 'no revoked union was found in the generated envelope');
    expect(TheWireUnions::valuesOf(HowFarTheRemovalReached::cases()))->toBe($words);
});

it('every removal taking lemonfiber off can be, and every sort of thing it reaches, has a case', function (): void {
    $tiers = TheWireUnions::unionIn(TheGeneratedEnvelopes::theGeneratedEnvelope('UninstallEnvelope'), 'tier');
    $sorts = TheWireUnions::unionIn(TheGeneratedEnvelopes::theGeneratedEnvelope('UninstallEnvelope'), 'sort');

    expect($tiers)->not->toBe([], 'no tier union was found in the generated envelope')
        ->and($sorts)->not->toBe([], 'no sort union was found in the generated envelope');
    expect(TheWireUnions::valuesOf(WhichRemoval::cases()))->toBe($tiers)
        ->and(TheWireUnions::valuesOf(WhatSortItIs::cases()))->toBe($sorts);
});

it('everywhere taking lemonfiber off can have got has a case', function (): void {
    // Tags on objects of their own rather than a union joined by `|`, because
    // the four carry different fields, so they are gathered by tag.
    preg_match_all("/\\bstate: '([a-z_-]+)'/", TheGeneratedEnvelopes::theGeneratedEnvelope('UninstallEnvelope'), $found);
    $states = array_values(array_unique($found[1]));
    sort($states);

    expect($states)->not->toBe([], 'no state tag was found in the generated envelope');
    expect(TheWireUnions::valuesOf(WhereTheUninstallStands::cases()))->toBe($states);
});

it('everything a quality choice can become has a case, on both envelopes that carry it', function (): void {
    // The one where a missing case would be worst: a disposition drawn as the
    // nearest one could call a held choice recorded, and nobody would be asked
    // to confirm it.
    $quality = TheWireUnions::unionIn(TheGeneratedEnvelopes::theGeneratedEnvelope('QualityEnvelope'), 'disposition');
    $music = TheWireUnions::unionIn(TheGeneratedEnvelopes::theGeneratedEnvelope('MusicEnvelope'), 'disposition');

    expect($quality)->not->toBe([], 'no disposition union was found in the generated quality envelope');
    expect(TheWireUnions::valuesOf(WhatBecameOfTheChoice::cases()))->toBe($quality)->toBe($music);
});

it('everything asking a service about quality can come to has a case', function (): void {
    // Tags on objects of their own rather than a union, because the failing
    // one carries a detail beside it, so they are gathered by tag.
    preg_match_all("/\\bstate: '([a-z_-]+)'/", TheGeneratedEnvelopes::theGeneratedEnvelope('UpgradeEnvelope'), $found);
    $words = array_values(array_unique($found[1]));
    sort($words);

    expect($words)->not->toBe([], 'no outcome states were found in the generated upgrade envelope');
    expect(TheWireUnions::valuesOf(WhereTheAskingStands::cases()))->toBe($words);
});

it('every service manager the contract describes has a case', function (): void {
    $managers = TheWireUnions::unionIn(TheGeneratedEnvelopes::theGeneratedHostingEnvelope(), 'manager');

    expect($managers)->not->toBe([], 'no manager union was found in the generated envelope');
    expect(TheWireUnions::valuesOf(WhatKeepsItRunning::cases()))->toBe($managers);
});

it('every standing the contract describes has a case', function (): void {
    // The contract calls this `state`. The enum is named for what it says about
    // a problem rather than for the field it arrives in, which is why the two
    // names are written down together here.
    expect(TheWireUnions::wireUnion('state'))->not->toBe([], 'no state union was found in the generated envelope');
    expect(TheWireUnions::valuesOf(Standing::cases()))->toBe(TheWireUnions::wireUnion('state'));
});

it('an enum that is a wire union is checked against it', function (): void {
    // The five above are the ones that mirror a union today, established by
    // reading rather than by remembering. What this refuses is the sixth: an
    // enum written from a contract field, with nothing holding it to that field,
    // added by somebody who had no reason to open this file.
    //
    // It reads literal unions only, and that is a real limit rather than an
    // oversight. `Conclusion` is not one: the verdict is a union of object
    // shapes and each arm fixes `outcome` to a literal of its own, which is why
    // it has `wireOutcomes()` instead, and the repair outcomes are the same
    // shape with a rule of their own. An enum written from a shape like that
    // would pass here: name the shape a rule can see, or add the rule.
    $unions = TheWireUnions::everyWireUnion();

    expect($unions)->not->toBe([], 'no literal union was found in any generated envelope');

    $unchecked = [];

    foreach (Module::all() as $module) {
        foreach ($module->classNames() as $name) {
            $class = ApiSurface::reflect($name);

            if (! $class->isEnum() || array_key_exists($name, TheWireUnions::CHECKED_AGAINST_THE_WIRE)) {
                continue;
            }

            // Read through reflection rather than `$name::cases()`. A class name
            // held in a variable is a set the analyser cannot see, which `P2`
            // refuses — and it is right: what the rule reads has to be something
            // a reader can follow to a declaration.
            $values = TheWireUnions::backedValuesOf($class);

            if ($values !== [] && in_array($values, $unions, strict: true)) {
                $unchecked[] = sprintf('%s is exactly a union the contract describes', $name);
            }
        }
    }

    sort($unchecked);

    expect($unchecked)->toBe([], sprintf(
        "These name the wire's values and nothing holds them to it:\n  %s\n\n"
        . 'An enum whose cases are exactly a union the contract describes is the wire '
        . 'written out by hand, and it goes out of date the way the five above would '
        . "have: silently, and correctly about a contract nobody speaks any more.\n"
        . 'Add it to `TheWireUnions::CHECKED_AGAINST_THE_WIRE` with the field it mirrors, and give it '
        . 'a rule above (N1-R13).',
        implode("\n  ", $unchecked),
    ));
});
