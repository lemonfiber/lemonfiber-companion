<?php

declare(strict_types=1);

use Modules\Kernel\Api\Reading;
use Tests\Support\ApiSurface;

/**
 * A remembered value cannot be shown without its age.
 *
 * Both requirements say the same thing about different screens: anything shown
 * that was not read in this session carries when it was read, and the other adds
 * that this includes the opening verdict, which is the screen most likely to be
 * drawn from something held over.
 *
 * {@see Reading} answers them by construction. There is no `value()`: the only
 * way to reach what is inside is {@see Reading::either()}, whose retained arm is
 * handed the value and the moment it was read together. A screen that renders it
 * without the age has to have been given the age and dropped it.
 *
 * That holds exactly as long as `either()` is the only way in. A `value()` added
 * beside it — for a screen that "just needs the report" — leaves the timestamp
 * available and not at hand where the screen is written, which is the shape both
 * requirements are broken by: not a disagreement, an omission.
 *
 * Checked as a shape rather than as a name, so it is not a list of forbidden
 * accessors that a twelfth spelling walks past: a method answering the held
 * value must make the caller say what happens in both cases.
 */
it('N1-R9, N2-R13 — the value is reachable only by saying what happens either way', function (): void {
    $reachable = [];

    $class = ApiSurface::reflect(Reading::class);

    foreach (ApiSurface::publicMethodsOf($class) as $method) {
        if (! in_array('object', ApiSurface::namesIn($method->getReturnType()), strict: true)) {
            continue;
        }

        $asks = array_any(
            $method->getParameters(),
            static fn(ReflectionParameter $parameter): bool => in_array(
                Closure::class,
                ApiSurface::namesIn($parameter->getType()),
                strict: true,
            ),
        );

        if (! $asks) {
            $reachable[] = ApiSurface::describe($method);
        }
    }

    // A public property holding the value asks for nothing at all, so it can
    // never be the shape this requires and is reported without looking.
    foreach ($class->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
        if (in_array('object', ApiSurface::namesIn($property->getType()), strict: true)) {
            $reachable[] = sprintf('%s::$%s', Reading::class, $property->getName());
        }
    }

    expect($reachable)->toBe([], sprintf(
        "A reading hands over what it holds without asking for the retained case:\n  %s\n\n"
        . 'The timestamp is not something a screen has to remember to ask for — that is '
        . 'the whole design, and it is the reason there is no `value()`. A method that '
        . 'answers the held value and takes no closure gives a caller the value with the '
        . "age left behind, which is N1-R9 broken by omission, the way it was before.\n"
        . 'If something needs what is inside, it needs to say what it does when the '
        . 'reading is retained (N1-R9, N2-R13).',
        implode("\n  ", $reachable),
    ));
});

it('N1-R9 — the retained arm is handed the moment it was read', function (): void {
    // The other half, and the one the shape rule cannot see: `either()` could
    // take two closures and hand the retained one nothing but the value. The
    // annotation is what a screen is written against, so it is what is checked.
    $either = new ReflectionMethod(Reading::class, 'either');
    $said = (string) $either->getDocComment();

    expect($said)->toContain('@param Closure(object, Instant): TRetained');
    expect($said)->toContain('@param Closure(object): TLive');
});
