<?php

declare(strict_types=1);

use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\SealedPayload;
use Modules\Kernel\Api\SealedStack;
use Modules\Kernel\Api\Shape;
use Tests\Support\Stores;

// A12 — a store is handed only what is sealed, and hands back nothing else.
//
// The capability seals before it asks its store, so a store holds nothing it
// could read and writes nothing to disk that the framework's own key, beside
// the database on Android, could open. The signatures are what make that true
// rather than hoped: a store method that took a summary, a stack's identity or
// a string could be handed a plain value, and one day would be. So every public
// method a store class has takes a sealed payload, a stack's keyed hash, or the
// bookkeeping a query needs beside them — the shape a value was written in and
// the moment it was read — and answers with a value whose every named
// constructor takes those and nothing else, or a count.
//
// Read by reflection over the store classes themselves, so the port each
// implements is judged through them: a port method is a store method.

/**
 * The only types a store may be handed.
 *
 * @var list<class-string>
 */
const WHAT_A_STORE_MAY_BE_HANDED = [SealedPayload::class, SealedStack::class, Shape::class, Instant::class];

/** A number of rows, which an answer may carry because it says nothing of what they held. */
const A_COUNT = 'int';

/**
 * Whether a declared type is one of the given names, and one only.
 *
 * @param list<string> $names
 */
function isOneOf(?ReflectionType $type, array $names): bool
{
    return $type instanceof ReflectionNamedType && in_array($type->getName(), $names, strict: true);
}

/**
 * The public static methods that build a value, which are what decide what it can hold.
 *
 * @param class-string $class
 *
 * @return list<ReflectionMethod>
 */
function theNamedConstructorsOf(string $class): array
{
    return array_values(array_filter(
        new ReflectionClass($class)->getMethods(ReflectionMethod::IS_STATIC),
        static fn(ReflectionMethod $method): bool => $method->isPublic(),
    ));
}

/**
 * Where an answer a store gives could carry something other than sealed bookkeeping.
 *
 * An answer that is not a class, or one whose named constructors take
 * anything else, is one a store could hand back something readable in.
 *
 * @return list<string>
 */
function whatAnAnswerCouldCarry(ReflectionMethod $method): array
{
    $answer = $method->getReturnType();
    $class = $answer instanceof ReflectionNamedType ? $answer->getName() : '';

    if (! class_exists($class)) {
        return [sprintf('%s::%s() answers %s, which is not a value built from sealed bookkeeping', $method->class, $method->name, (string) $answer)];
    }

    $found = [];

    foreach (theNamedConstructorsOf($class) as $constructor) {
        foreach ($constructor->getParameters() as $parameter) {
            if (! isOneOf($parameter->getType(), [...WHAT_A_STORE_MAY_BE_HANDED, A_COUNT])) {
                $found[] = sprintf('%s::%s() answers %s, which %s() builds from %s', $method->class, $method->name, $class, $constructor->name, (string) $parameter->getType());
            }
        }
    }

    return $found;
}

/**
 * Every place a store class takes or gives something other than sealed bookkeeping.
 *
 * @param class-string $store
 *
 * @return list<string>
 */
function whereAStoreCouldSeeAPlainValue(string $store): array
{
    $found = [];

    foreach (new ReflectionClass($store)->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
        if ($method->isConstructor()) {
            continue;
        }

        foreach ($method->getParameters() as $parameter) {
            if (! isOneOf($parameter->getType(), WHAT_A_STORE_MAY_BE_HANDED)) {
                $found[] = sprintf('%s::%s() takes $%s as %s', $store, $method->name, $parameter->name, (string) $parameter->getType());
            }
        }

        $found = [...$found, ...whatAnAnswerCouldCarry($method)];
    }

    return $found;
}

/**
 * Every class under a capability's store.
 *
 * @return list<class-string>
 */
function everyStoreClass(): array
{
    $found = [];

    foreach (Stores::all() as $store) {
        $found = [...$found, ...Stores::classesOf($store)];
    }

    return $found;
}

it('finds the store classes it judges', function (): void {
    // The floor: a rule over no class passes about nothing.
    expect(everyStoreClass())->not->toBe([]);
});

it('A12 — a store class takes and gives only sealed payloads, keyed hashes and their bookkeeping', function (): void {
    $offenders = [];

    foreach (everyStoreClass() as $store) {
        $offenders = [...$offenders, ...whereAStoreCouldSeeAPlainValue($store)];
    }

    expect($offenders)->toBe([], sprintf(
        "These stores could be handed, or hand back, something they could read:\n  %s\n\n"
        . 'The capability seals a value before it asks its store, so a store takes a SealedPayload, '
        . 'a SealedStack and the Shape and Instant beside them, and answers with values built from '
        . 'those or a count of rows. A store that could be handed a plain value would one day write '
        . 'one to disk beside the framework\'s own key (A12).',
        implode("\n  ", $offenders),
    ));
});
