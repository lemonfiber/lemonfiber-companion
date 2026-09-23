<?php

declare(strict_types=1);

use Modules\Kernel\Api\Awaiting;
use Modules\Kernel\Api\Category;
use Modules\Kernel\Api\Conclusion;
use Modules\Kernel\Api\Cost;
use Modules\Kernel\Api\HowAServiceRuns;
use Modules\Kernel\Api\HowCurrent;
use Modules\Kernel\Api\HowItEnded;
use Modules\Kernel\Api\HowItIsHosted;
use Modules\Kernel\Api\HowMuchItMatters;
use Modules\Kernel\Api\HowTheStackIsRunning;
use Modules\Kernel\Api\HowToUndoIt;
use Modules\Kernel\Api\Medium;
use Modules\Kernel\Api\Overall;
use Modules\Kernel\Api\Severity;
use Modules\Kernel\Api\Stage;
use Modules\Kernel\Api\Stance;
use Modules\Kernel\Api\Standing;
use Modules\Kernel\Api\Stream;
use Modules\Kernel\Api\Waiting;
use Modules\Kernel\Api\WhatKeepsItRunning;
use Tests\Support\ApiSurface;
use Tests\Support\Module;
use Tests\Support\Tree;

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

/** The generated envelope that carries a report, as text. */
function theGeneratedDoctorEnvelope(): string
{
    return theGeneratedEnvelope('DoctorEnvelope');
}

/** The generated envelope that carries what has stopped coming in, as text. */
function theGeneratedStuckEnvelope(): string
{
    return theGeneratedEnvelope('StuckEnvelope');
}

/** The generated envelope that carries what a member may watch, as text. */
function theGeneratedHeldEnvelope(): string
{
    return theGeneratedEnvelope('HeldEnvelope');
}

/** The generated envelope that carries a service's scrollback, as text. */
function theGeneratedLogEnvelope(): string
{
    return theGeneratedEnvelope('LogEnvelope');
}

/** The generated envelope that carries what a stack is set to, as text. */
function theGeneratedConfigEnvelope(): string
{
    return theGeneratedEnvelope('ConfigEnvelope');
}

/** The generated envelope that carries what the whole stack is doing, as text. */
function theGeneratedStatusEnvelope(): string
{
    return theGeneratedEnvelope('StatusEnvelope');
}

/** The generated envelope that carries what the machine keeps running, as text. */
function theGeneratedHostingEnvelope(): string
{
    return theGeneratedEnvelope('HostingEnvelope');
}

/**
 * One generated envelope, as text.
 *
 * Named rather than spelled at each caller once there were several: the path is
 * one fact about where the generator writes, and a copy per envelope would let a
 * regenerated tree move one and leave the others reading a file that is no longer
 * there — which returns `''`, and an empty source makes every union it is asked
 * about come back empty. The rules below assert they found something for exactly
 * that reason, but they would name the union rather than the path.
 */
function theGeneratedEnvelope(string $called): string
{
    $said = file_get_contents(
        Tree::at(sprintf('vendor/lemonfiber/sdk-php/src/Generated/%s.php', $called)),
    );

    return is_string($said) ? $said : '';
}

/**
 * The generated envelope that carries the household, as text.
 *
 * A second file rather than a search across `src/Generated`, and deliberately.
 * `state` is declared in both this envelope and the doctor one, with different
 * unions — a problem's standing and a request's — so a reader that swept every
 * generated file would find two occurrences that disagree and answer nothing,
 * which is `unionIn`'s honest refusal applied to a question that is not really
 * ambiguous. The ambiguity is in the wire's choice of name, not in the contract,
 * and naming the envelope is how this side says which `state` it means.
 */
function theGeneratedHouseholdEnvelope(): string
{
    $said = file_get_contents(
        Tree::at('vendor/lemonfiber/sdk-php/src/Generated/HouseholdEnvelope.php'),
    );

    return is_string($said) ? $said : '';
}

/**
 * The literals a named field of the envelope is declared as.
 *
 * Every occurrence is collected rather than the first, and two that disagree
 * make this answer nothing. A field name appearing twice with different unions
 * is a question this cannot answer, and answering it with whichever came first
 * would be the quiet half-right result these rules exist to refuse.
 *
 * @return list<string>
 */
function wireUnion(string $field): array
{
    return unionIn(theGeneratedDoctorEnvelope(), $field);
}

/**
 * The same reading, over text it is handed rather than text it goes and finds.
 *
 * Split from `wireUnion` because this is the half that decides, and it had only
 * ever been asked about a generated file where every answer is the right one.
 * Every property the five rules below demonstrate in that state is equally true
 * of a reader that always answers with the enum it is being compared to — and
 * the `[]` two disagreeing unions produce is a shape nobody had watched it take.
 *
 * @return list<string>
 */
function unionIn(string $source, string $field): array
{
    $pattern = sprintf("/\\b%s\\??: ((?:'[a-z_-]+'\\|)+'[a-z_-]+')/", preg_quote($field, '/'));

    preg_match_all($pattern, $source, $found);

    $unions = array_values(array_unique($found[1]));

    if (count($unions) !== 1) {
        return [];
    }

    preg_match_all("/'([a-z_-]+)'/", $unions[0], $literals);

    sort($literals[1]);

    return $literals[1];
}

/**
 * Every value a check's verdict may carry as its outcome.
 *
 * Read as single literals rather than as a union: the verdict is a union of
 * object shapes and each arm fixes `outcome` to one value of its own.
 *
 * @return list<string>
 */
function wireOutcomes(): array
{
    return outcomesIn(theGeneratedDoctorEnvelope());
}

/**
 * The same reading, over text it is handed. Split for the reason `unionIn` is.
 *
 * @return list<string>
 */
function outcomesIn(string $source): array
{
    preg_match_all("/outcome: '([a-z_-]+)'/", $source, $found);

    $values = array_values(array_unique($found[1]));

    sort($values);

    return $values;
}

/**
 * An enum's values, sorted, for comparing as a set.
 *
 * @param list<BackedEnum> $cases
 *
 * @return list<string>
 */
function valuesOf(array $cases): array
{
    $values = array_map(static fn(BackedEnum $case): string => (string) $case->value, $cases);

    sort($values);

    return $values;
}

it('N1-R13 — the reading that decides all five can say something else', function (): void {
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

    expect(unionIn($envelope, 'category'))->toBe(['network', 'storage', 'weather']);

    // Sorted, because the comparison is on sets: the order the contract writes
    // them in is the generator's business and the order an enum declares them in
    // is a decision made for a reader.
    expect(unionIn($envelope, 'severity'))->toBe(['note', 'warning']);

    // A field the envelope does not mention is nothing found, not an empty union
    // — and the five rules below each assert that separately, which is what makes
    // a change to the generator's format fail rather than quietly match nothing.
    expect(unionIn($envelope, 'nothing'))->toBe([]);

    // A single literal is not a union. The pattern wants at least one `|`, so a
    // field the contract has narrowed to one value is reported as unreadable
    // rather than as a one-case enum.
    expect(unionIn("state: 'settled',", 'state'))->toBe([]);
});

it('N1-R13 — a field declared twice with different unions is refused', function (): void {
    // The path the comment above `unionIn` describes and nothing had taken. Two
    // occurrences that disagree are a question this cannot answer, and answering
    // with whichever came first would be the quiet half-right result these rules
    // exist to refuse — it would compare the enum against half a contract and
    // pass.
    $disagreeing = "category: 'storage'|'network',\ncategory: 'storage'|'weather',";

    expect(unionIn($disagreeing, 'category'))->toBe([]);

    // And the same field twice saying the same thing is one answer, not none:
    // the generator repeats a shape wherever it is used.
    $agreeing = "category: 'storage'|'network',\ncategory: 'storage'|'network',";

    expect(unionIn($agreeing, 'category'))->toBe(['network', 'storage']);
});

it('N1-R13 — every request standing the contract describes has a case', function (): void {
    // The contract calls this `state` too, in a different envelope and with a
    // different union — a problem's standing and a request's. Read from the
    // household envelope by name rather than by sweeping the generated files,
    // because a sweep would find two occurrences that disagree and honestly
    // answer nothing about either.
    $union = unionIn(theGeneratedHouseholdEnvelope(), 'state');

    expect($union)->not->toBe([], 'no request state union was found in the generated envelope');
    expect(valuesOf(Waiting::cases()))->toBe($union);
});

it('N1-R13 — the verdict outcomes are collected across arms', function (): void {
    // `outcome` is read as single literals rather than as a union, because the
    // verdict is a union of object shapes and each arm fixes it to one value of
    // its own. Two arms is the case that distinguishes this from `unionIn`.
    $verdict = "array{outcome: 'passed', at: string}|array{outcome: 'failed', why: string}";

    expect(outcomesIn($verdict))->toBe(['failed', 'passed']);
    expect(outcomesIn('nothing here'))->toBe([]);
});

it('N1-R13 — every health category the contract describes has a case', function (): void {
    expect(wireUnion('category'))->not->toBe([], 'no category union was found in the generated envelope');
    expect(valuesOf(Category::cases()))->toBe(wireUnion('category'));
});

it('N1-R13 — every verdict the contract describes has a case', function (): void {
    expect(wireOutcomes())->not->toBe([], 'no verdict outcome was found in the generated envelope');
    expect(valuesOf(Conclusion::cases()))->toBe(wireOutcomes());
});

it('N1-R13 — every overall the contract describes has a case', function (): void {
    expect(wireUnion('overall'))->not->toBe([], 'no overall union was found in the generated envelope');
    expect(valuesOf(Overall::cases()))->toBe(wireUnion('overall'));
});

it('N1-R13 — every severity the contract describes has a case', function (): void {
    expect(wireUnion('severity'))->not->toBe([], 'no severity union was found in the generated envelope');
    expect(valuesOf(Severity::cases()))->toBe(wireUnion('severity'));
});

it('N1-R13 — every stage the contract describes has a case', function (): void {
    // Read from the stuck envelope rather than the doctor one, which is the
    // first time these rules have looked at a second file. The union is only
    // declared where it is used, so asking the doctor envelope about `stage`
    // answers `[]` — which is the same answer a renamed field gives, and is why
    // the assertion below insists something was found before comparing.
    $stages = unionIn(theGeneratedStuckEnvelope(), 'stage');

    expect($stages)->not->toBe([], 'no stage union was found in the generated envelope');
    expect(valuesOf(Stage::cases()))->toBe($stages);
});

it('N1-R13 — every medium the contract describes has a case', function (): void {
    // Read from the held envelope, the way the stage and the stream are read
    // from theirs: a union is declared only where it is used, so asking any
    // other envelope about `medium` answers `[]` — the same answer a renamed
    // field gives, which is why something must be found before comparing.
    $media = unionIn(theGeneratedHeldEnvelope(), 'medium');

    expect($media)->not->toBe([], 'no medium union was found in the generated envelope');
    expect(valuesOf(Medium::cases()))->toBe($media);
});

it('N1-R13 — every stream the contract describes has a case', function (): void {
    // A third generated envelope, read the way the stuck one above is. A union
    // is only declared where it is used, so asking any other envelope about
    // `stream` answers `[]` — the same answer a renamed field gives, which is
    // why the assertion insists something was found before comparing.
    $streams = unionIn(theGeneratedLogEnvelope(), 'stream');

    expect($streams)->not->toBe([], 'no stream union was found in the generated envelope');
    expect(valuesOf(Stream::cases()))->toBe($streams);
});

it('N1-R13 — every cost the contract describes has a case', function (): void {
    $costs = unionIn(theGeneratedConfigEnvelope(), 'cost');

    expect($costs)->not->toBe([], 'no cost union was found in the generated envelope');
    expect(valuesOf(Cost::cases()))->toBe($costs);
});

it('N1-R13 — every stance the contract describes has a case', function (): void {
    // The one where a missing case would be worst. A stance this app did not
    // know would be refused as unreadable, which is correct — but the reason
    // to hold the set to the wire here is the pair the enum exists to keep
    // apart: `unchanged` and `applied` both mean the setting holds what was
    // asked for, and a contract that grew a third of those would need reading
    // rather than guessing at.
    $stances = unionIn(theGeneratedConfigEnvelope(), 'stance');

    expect($stances)->not->toBe([], 'no stance union was found in the generated envelope');
    expect(valuesOf(Stance::cases()))->toBe($stances);
});

it('N1-R13 — every way a service can be running has a case', function (): void {
    $states = unionIn(theGeneratedStatusEnvelope(), 'state');

    expect($states)->not->toBe([], 'no service state union was found in the generated envelope');
    expect(valuesOf(HowAServiceRuns::cases()))->toBe($states);
});

it('N1-R13 — every criticality the contract describes has a case', function (): void {
    $matters = unionIn(theGeneratedStatusEnvelope(), 'criticality');

    expect($matters)->not->toBe([], 'no criticality union was found in the generated envelope');
    expect(valuesOf(HowMuchItMatters::cases()))->toBe($matters);
});

it('N1-R13 — every condition the whole stack can be in has a case', function (): void {
    $conditions = unionIn(theGeneratedStatusEnvelope(), 'condition');

    expect($conditions)->not->toBe([], 'no condition union was found in the generated envelope');
    expect(valuesOf(HowTheStackIsRunning::cases()))->toBe($conditions);
});

it('N1-R13 — every way a command can be hosted has a case', function (): void {
    // `standing` rather than `state`, which is the `hosting` envelope's own
    // word for it and is a third union again — a problem's standing and a
    // household request's are already two, under the wire's `state`. Naming the
    // envelope here is what keeps the three from being read as one.
    $hosted = unionIn(theGeneratedHostingEnvelope(), 'standing');

    expect($hosted)->not->toBe([], 'no hosting standing union was found in the generated envelope');
    expect(valuesOf(HowItIsHosted::cases()))->toBe($hosted);
});

it('N1-R13 — every service manager the contract describes has a case', function (): void {
    $managers = unionIn(theGeneratedHostingEnvelope(), 'manager');

    expect($managers)->not->toBe([], 'no manager union was found in the generated envelope');
    expect(valuesOf(WhatKeepsItRunning::cases()))->toBe($managers);
});

it('N1-R13 — every standing the contract describes has a case', function (): void {
    // The contract calls this `state`. The enum is named for what it says about
    // a problem rather than for the field it arrives in, which is why the two
    // names are written down together here.
    expect(wireUnion('state'))->not->toBe([], 'no state union was found in the generated envelope');
    expect(valuesOf(Standing::cases()))->toBe(wireUnion('state'));
});

/**
 * An enum's backing values, sorted, for comparing as a set.
 *
 * Read off the class constants, which is where PHP puts an enum's cases. The
 * obvious `ReflectionEnum` wants a `class-string<UnitEnum>` where this has a
 * `class-string`, and its constructor throws a checked exception — which a Pest
 * body, being a closure, may not.
 *
 * @param ReflectionClass<object> $class
 *
 * @return list<string>
 */
function backedValuesOf(ReflectionClass $class): array
{
    $values = [];

    foreach ($class->getConstants() as $case) {
        if ($case instanceof BackedEnum) {
            $values[] = (string) $case->value;
        }
    }

    sort($values);

    return $values;
}

/** The enums checked above, against the contract field each mirrors. */
const CHECKED_AGAINST_THE_WIRE = [
    Category::class => 'category',
    Conclusion::class => 'outcome',
    Overall::class => 'overall',
    Severity::class => 'severity',
    HowAServiceRuns::class => 'state',
    HowMuchItMatters::class => 'criticality',
    HowTheStackIsRunning::class => 'condition',
    Stage::class => 'stage',
    HowCurrent::class => 'state',
    HowItEnded::class => 'ending',
    HowToUndoIt::class => 'reversal',
    Awaiting::class => 'until',
    Standing::class => 'state',
    Stream::class => 'stream',
    Medium::class => 'medium',
    Cost::class => 'cost',
    Stance::class => 'stance',

    HowItIsHosted::class => 'standing',
    WhatKeepsItRunning::class => 'manager',

    // `state` twice, and that is the wire's name rather than a mistake here:
    // a problem's standing and a household request's are different unions in
    // different envelopes. Each has a rule above naming which envelope it reads.
    Waiting::class => 'state',
];

/**
 * Every literal union any generated envelope declares, as a set of values.
 *
 * @return list<list<string>>
 */
function everyWireUnion(): array
{
    $found = [];

    foreach (Tree::filesUnder(Tree::at('vendor/lemonfiber/sdk-php/src/Generated'), '.php') as $path) {
        $said = file_get_contents($path);

        if (! is_string($said)) {
            continue;
        }

        preg_match_all("/(?:'[a-z_-]+'\\|)+'[a-z_-]+'/", $said, $unions);

        foreach ($unions[0] as $union) {
            preg_match_all("/'([a-z_-]+)'/", $union, $literals);

            sort($literals[1]);

            $found[] = $literals[1];
        }
    }

    return $found;
}

it('N1-R13 — an enum that is a wire union is checked against it', function (): void {
    // The five above are the ones that mirror a union today, established by
    // reading rather than by remembering. What this refuses is the sixth: an
    // enum written from a contract field, with nothing holding it to that field,
    // added by somebody who had no reason to open this file.
    //
    // It reads literal unions only, and that is a real limit rather than an
    // oversight. `Conclusion` is not one: the verdict is a union of object
    // shapes and each arm fixes `outcome` to a literal of its own, which is why
    // it has `wireOutcomes()` instead. An enum written from that shape — the
    // repair outcomes are the same — would pass here. Name the shape a rule can
    // see, or add the rule.
    $unions = everyWireUnion();

    expect($unions)->not->toBe([], 'no literal union was found in any generated envelope');

    $unchecked = [];

    foreach (Module::all() as $module) {
        foreach ($module->classNames() as $name) {
            $class = ApiSurface::reflect($name);

            if (! $class->isEnum() || array_key_exists($name, CHECKED_AGAINST_THE_WIRE)) {
                continue;
            }

            // Read through reflection rather than `$name::cases()`. A class name
            // held in a variable is a set the analyser cannot see, which `P2`
            // refuses — and it is right: what the rule reads has to be something
            // a reader can follow to a declaration.
            $values = backedValuesOf($class);

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
        . 'Add it to `CHECKED_AGAINST_THE_WIRE` with the field it mirrors, and give it '
        . 'a rule above (N1-R13).',
        implode("\n  ", $unchecked),
    ));
});
