<?php

declare(strict_types=1);

use Modules\Kernel\Api\Category;
use Modules\Kernel\Api\Conclusion;
use Modules\Kernel\Api\Overall;
use Modules\Kernel\Api\Severity;
use Modules\Kernel\Api\Standing;
use Tests\Support\ApiSurface;
use Tests\Support\Module;
use Tests\Support\Tree;

/**
 * `N1-R13` — the app reads every value the contract says a stack may send.
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
    $said = file_get_contents(
        Tree::at('vendor/lemonfiber/sdk-php/src/Generated/DoctorEnvelope.php'),
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
    $pattern = sprintf("/\\b%s\\??: ((?:'[a-z_-]+'\\|)+'[a-z_-]+')/", preg_quote($field, '/'));

    preg_match_all($pattern, theGeneratedDoctorEnvelope(), $found);

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
    preg_match_all("/outcome: '([a-z_-]+)'/", theGeneratedDoctorEnvelope(), $found);

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
    Standing::class => 'state',
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
