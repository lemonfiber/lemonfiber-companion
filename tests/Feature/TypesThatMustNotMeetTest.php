<?php

declare(strict_types=1);

use Closure;
use Modules\Kernel\Api\Credential;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Held;
use Modules\Kernel\Api\IdempotencyKey;
use Modules\Kernel\Api\Interrupted;
use Modules\Kernel\Api\Pairing;
use Modules\Kernel\Api\SecureStorage;
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
        [
            Pairing::class,
            [Session::class, Credential::class],
            'N1-R49',
            'Redeeming pairing material must not be what admits the app — admission stays the '
            . 'exchange of the operator own credential for a session (N1-R7). Material is '
            . 'carried across a gap by eye or by camera and is public in the sense that '
            . 'matters, so a path from it to a session is a path from a photograph in a camera '
            . 'roll to an admitted app.',
        ],
        [
            SecureStorage::class,
            [IdempotencyKey::class],
            'N1-R42',
            'A key serves retry within a single attempt and must not replay an action '
            . 'across a reconnection. A storage port that could take one is a key that '
            . 'survives the attempt it belongs to, and whatever reads it back sends the '
            . 'operator earlier action again — at a moment nobody chose, against a stack '
            . 'whose state has moved on.',
        ],
    ];
}

it('N1-R38 — what a screen kept cannot be handed anything that could refetch it', function (): void {
    // The requirement's second clause: returning to a screen must not re-read
    // the stack solely to rebuild it. The easy way to satisfy the first clause
    // is to re-run whatever built the screen, which looks correct and — on a
    // desk, with a stack on the same subnet — is indistinguishable from
    // correct. What it does is discard the operator's half-finished work and
    // replace it with whatever the stack says now.
    //
    // Named types are listed in the table above; this one is a shape. Any
    // interface is a port or a collaborator that can be asked something, so a
    // `Held` that could be handed one is a `Held` that could refetch. Written
    // as a shape rather than a list because the list would need editing every
    // time a port is added, and the edit that gets forgotten is the one that
    // matters.
    $reachable = [];

    // Every method, not only the published ones: a private helper handed a
    // port is a port the type can reach, whoever may call it.
    foreach (new ReflectionClass(Held::class)->getMethods() as $method) {
        $named = ApiSurface::namesIn($method->getReturnType());

        foreach ($method->getParameters() as $parameter) {
            $named = [...$named, ...ApiSurface::namesIn($parameter->getType())];
        }

        foreach ($named as $type) {
            if (interface_exists($type) && $type !== Closure::class) {
                $reachable[] = sprintf('%s takes or answers %s', ApiSurface::describe($method), $type);
            }
        }
    }

    expect(array_values(array_unique($reachable)))->toBe([], sprintf(
        "What a screen kept can reach something it could ask:\n  %s\n\n"
        . 'N1-R38 forbids re-reading the stack to rebuild a screen. An interface here is '
        . 'a port or a collaborator that can be asked, so a `Held` that could be handed '
        . "one is a `Held` that could refetch — and refetching is how the operator's "
        . 'half-finished work gets replaced by whatever the stack says now, after a wait '
        . "they did not ask for.\nWhat a screen keeps is a value. If it needs something "
        . 'asked, that happened before it was kept (N1-R38).',
        implode("\n  ", $reachable),
    ));
});

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
