<?php

declare(strict_types=1);

use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Interrupted;
use Modules\Kernel\Api\Pairing;
use Modules\Kernel\Api\Session;
use Tests\Support\ApiSurface;

/**
 * Requirements that are satisfied by a path not existing.
 *
 * Several of the companion's security requirements are not about what a type
 * does — they are about what it must never be able to reach. `N1-R45` is one:
 * a session ending must not discard the pairing, and the way to be sure is that
 * nothing on that path can name a pairing at all.
 *
 * Written once, as a table, rather than as a reflection loop inside each type's
 * own test. The first version of this was exactly that loop, written by hand in
 * `InterruptedTest`, and it checked `ReflectionNamedType` only — so a return
 * type of `Pairing|null`, which is the shape somebody would actually add, went
 * past it reporting a pass. {@see ApiSurface::namesIn()} flattens unions and
 * intersections, and now there is one place to fix when a third shape appears
 * rather than one per subject.
 */

/**
 * The pairs, each with the requirement that forbids them meeting.
 *
 * @return list<array{class-string, list<class-string>, string, string}>
 */
function typesThatMustNotMeet(): array
{
    return [
        [
            Interrupted::class,
            [Pairing::class, Fingerprint::class],
            'N1-R45',
            'A credential expiring is not the machine changing (ADR-0018). A path from an '
            . 'interruption to a pairing is a path to re-pinning a certificate that was never '
            . 'wrong, and then asking the operator to accept a new one — which is a habit an '
            . 'attacker would like them to have.',
        ],
    ];
}

it('a type that must not reach another cannot name it in any signature', function (): void {
    $found = [];

    foreach (typesThatMustNotMeet() as [$subject, $forbidden, $requirement, $why]) {
        $class = new ReflectionClass($subject);
        $methods = [...ApiSurface::publicMethodsOf($class), ...$class->getMethods()];

        foreach ($methods as $method) {
            $named = ApiSurface::namesIn($method->getReturnType());

            foreach ($method->getParameters() as $parameter) {
                $named = [...$named, ...ApiSurface::namesIn($parameter->getType())];
            }

            foreach (array_intersect($named, $forbidden) as $reached) {
                $found[] = sprintf(
                    "%s — %s names %s\n    %s",
                    $requirement,
                    ApiSurface::describe($method),
                    $reached,
                    $why,
                );
            }
        }
    }

    expect(array_values(array_unique($found)))->toBe([], sprintf(
        "A type reached one it must not:\n  %s\n\n"
        . 'These requirements are satisfied by a path not existing, so the check is on the '
        . 'signatures rather than on the behaviour: a method that can be handed one of these, '
        . 'or can answer with one, is a path somebody will take.',
        implode("\n  ", $found),
    ));
});
