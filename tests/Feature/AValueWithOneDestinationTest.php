<?php

declare(strict_types=1);

use Tests\Support\ApiSurface;
use Tests\Support\OneDestination;

/**
 * Values that may be read for one purpose, and are held to it by their surface.
 *
 * Three types here each carry a string that has exactly one place to go, and
 * each publishes exactly one way to reach it, named for that place:
 * `forTheHeader`, `forTheExchange`, `forTheClient`. The naming is deliberate and
 * every one of the three docblocks says so — reading it for any other purpose is
 * meant to read wrong at the call site.
 *
 * Nothing held them to it. A second accessor — `value()`, `token()`,
 * `forTheQuery()` — compiles, passes every architecture rule, and is the whole
 * of how `N1-R8` gets broken: nobody puts a session in a query string on
 * purpose; somebody adds a plain getter because a template wanted the string,
 * and six months later a different caller uses it to build a URL. The rule that
 * the name is the guard is only a rule while there is one name.
 *
 * The table itself lives in {@see OneDestination}, because a second rule needs
 * the same three facts: this one asks whether a second accessor has appeared
 * beside the one, and `NothingSecretReachesAScreenTest` asks whether a template
 * calls the one. Two copies would go stale separately, and a template rule
 * naming an accessor that had since been renamed would find nothing and report
 * a clean run.
 *
 * So the surface is counted rather than trusted. A method whose signature an
 * interface dictates is not counted: `jsonSerialize` answers a string because
 * `JsonSerializable` says it must, and the string it answers here is
 * `(a session, hidden)`. That is the same argument {@see ApiSurface} makes about
 * the magic methods PHP shapes.
 */

/**
 * Whether an interface this class implements is what dictates the signature.
 *
 * @param ReflectionClass<object> $class
 */
function dictatedByAnInterface(ReflectionClass $class, string $method): bool
{
    return array_any($class->getInterfaces(), fn(ReflectionClass $interface): bool => $interface->hasMethod($method));
}

/**
 * Every published method of a class that answers with a string.
 *
 * @param ReflectionClass<object> $class
 *
 * @return list<string>
 */
function answeringAString(ReflectionClass $class): array
{
    $found = [];

    foreach (ApiSurface::publicMethodsOf($class) as $method) {
        $answers = ApiSurface::namesIn($method->getReturnType());

        if (in_array('string', $answers, strict: true) && ! dictatedByAnInterface($class, $method->getName())) {
            $found[] = $method->getName();
        }
    }

    // A public property is read without a call, so it answers too. Kept apart
    // from the loop above rather than merged through a helper: what a member is
    // called is the thing this compares, and recovering it from a formatted
    // description is a parse nobody should have to read.
    foreach ($class->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
        if (in_array('string', ApiSurface::namesIn($property->getType()), strict: true)) {
            $found[] = sprintf('$%s', $property->getName());
        }
    }

    sort($found);

    return $found;
}

it('a value with one destination publishes one way to reach it', function (): void {
    $wrong = [];

    foreach (OneDestination::all() as [$subject, $accessor, $requirement, $why]) {
        $answering = answeringAString(reflectValue($subject));

        if ($answering !== [$accessor]) {
            $wrong[] = sprintf(
                "%s — %s answers a string from %s, and may answer from %s alone\n    %s",
                $requirement,
                $subject,
                $answering === [] ? 'nothing' : implode(', ', $answering),
                $accessor,
                $why,
            );
        }
    }

    expect($wrong)->toBe([], sprintf(
        "A value that may be read for one purpose can be read for another:\n  %s\n\n"
        . 'Each of these is named for where its value goes, so that reading it anywhere '
        . 'else reads wrong at the call site. That is a guard while there is one name '
        . "and nothing at all once there are two.\n"
        . 'If something needs the value for a second purpose, the question is whether it '
        . 'should have it — not what to call the getter.',
        implode("\n  ", $wrong),
    ));
});

/**
 * A class by name, as the support helpers will take it.
 *
 * @param class-string $name
 *
 * @return ReflectionClass<object>
 */
function reflectValue(string $name): ReflectionClass
{
    return new ReflectionClass($name);
}
