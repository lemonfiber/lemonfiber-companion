<?php

declare(strict_types=1);

use Modules\Kernel\Api\Credential;
use Modules\Kernel\Api\Effects;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Held;
use Modules\Kernel\Api\IdempotencyKey;
use Modules\Kernel\Api\Interrupted;
use Modules\Kernel\Api\Pairing;
use Modules\Kernel\Api\Repair;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Undoing;
use Native\Mobile\Edge\NativeComponent;
use Tests\Support\ApiSurface;
use Tests\Support\Module;

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

/**
 * Every type a screen names, against where it names it.
 *
 * Properties as well as signatures. A promoted one is both and is found by the
 * constructor, but a screen can also declare a plain typed property, and a rule
 * that read only methods would be a rule a field walks past.
 *
 * @param ReflectionClass<object> $screen
 *
 * @return list<array{string, list<string>}>
 */
function namedByScreen(ReflectionClass $screen): array
{
    $named = [];

    foreach ($screen->getMethods() as $method) {
        $types = ApiSurface::namesIn($method->getReturnType());

        foreach ($method->getParameters() as $parameter) {
            $types = [...$types, ...ApiSurface::namesIn($parameter->getType())];
        }

        $named[] = [ApiSurface::describe($method), $types];
    }

    foreach ($screen->getProperties() as $property) {
        $named[] = [
            sprintf('%s::$%s', $screen->getName(), $property->getName()),
            ApiSurface::namesIn($property->getType()),
        ];
    }

    return $named;
}

/**
 * A class by name, as something the support helpers will take.
 *
 * `new ReflectionClass(Repair::class)` is a `ReflectionClass<Repair>`, and
 * `ReflectionClass`'s template is not covariant — so the analyser refuses it
 * where a `ReflectionClass<object>` is wanted. The table above never met this
 * because its subjects arrive as `class-string` from a return type.
 *
 * @param class-string $name
 *
 * @return ReflectionClass<object>
 */
function reflect(string $name): ReflectionClass
{
    return new ReflectionClass($name);
}

/**
 * Every class this app renders from.
 *
 * Read by what a class *is* as well as by where it sits. The directory was the
 * whole of this at first, and a rule that finds its subjects by namespace stops
 * finding them the day somebody puts one somewhere else — which is not a
 * failure anybody would see, because a rule with nothing to check passes.
 * Anything extending `NativeComponent` renders to the operator whatever
 * directory it is in, and that is the property the requirement is about.
 *
 * The directory is kept alongside it rather than replaced, for the case the
 * subclass test cannot see: a screen written before it is wired to a base class
 * still sits in `Screens` and is still something somebody is about to render.
 *
 * @return list<ReflectionClass<object>>
 */
function screens(): array
{
    $found = [];

    foreach (Module::all() as $module) {
        foreach ($module->classNames() as $name) {
            if (str_contains($name, '\\Internal\\Screens\\') || is_subclass_of($name, NativeComponent::class)) {
                $found[] = reflect($name);
            }
        }
    }

    return $found;
}

it('N2-R12 — no screen can be handed a credential', function (): void {
    // The app may say that a credential is refused, and saying so needs its
    // name and its state — which is what the contract's `held` carries, having
    // "deliberately no value here, and no field a value could be put in later
    // without the change being visible in review". The value arrives in one
    // place only, under `revealed`, and nothing on this side has any business
    // holding it.
    //
    // Checked as a path rather than as a screen, because the screen that
    // violates this does not exist yet and the point is that it never gets
    // written. A screen that can be handed a credential is a screen with a
    // field to bind one to, and binding it is the small edit somebody makes
    // while fixing something else.
    // Assert the reading before what it says. A rule whose subjects are
    // discovered has a state in which it examines nothing, and that state is
    // indistinguishable from every subject passing — so it is the state to fail
    // in rather than the one to be quietly correct in.
    expect(screens())->not->toBe([], 'no screen was found to check, so this rule proved nothing');

    $found = [];

    foreach (screens() as $screen) {
        foreach (namedByScreen($screen) as [$where, $types]) {
            if (in_array(Credential::class, $types, strict: true)) {
                $found[] = $where;
            }
        }
    }

    expect(array_values(array_unique($found)))->toBe([], sprintf(
        "A screen can reach a credential's value:\n  %s\n\n"
        . 'N2-R12 keeps this app from being a place to type a provider password into '
        . 'over a LAN. Reporting a credential needs its name and its state; the value '
        . "is never part of the answer, so a surface that can hold one is a surface "
        . 'that has already gone wrong (N2-R12, N1-R15).',
        implode("\n  ", $found),
    ));
});

it('N2-R4 — a repair cannot hand over one of its three clauses alone', function (): void {
    // The requirement is one sentence with three clauses, and the way it gets
    // broken is that a screen is written around `does` — the field that reads
    // like the label — while the other two stay in the envelope. `Repair`
    // answers that by publishing no accessor for any of them: `stated()` hands
    // over all three together, so a screen that shows one has been given all
    // three and discarded two, which somebody has to do on purpose.
    //
    // That design is worth nothing the day a getter is added for a template
    // that needed "just the one field", so it is checked rather than written
    // down. Two properties, because `does` is a string and cannot be told from
    // `answers()` by its type alone.
    $published = ApiSurface::publicMethodsOf(reflect(Repair::class));

    $clauses = [];
    $strings = [];

    foreach ($published as $method) {
        $answers = ApiSurface::namesIn($method->getReturnType());

        foreach (array_intersect($answers, [Effects::class, Undoing::class]) as $clause) {
            $clauses[] = sprintf('%s answers %s', ApiSurface::describe($method), $clause);
        }

        if (in_array('string', $answers, strict: true)) {
            $strings[] = ApiSurface::describe($method);
        }
    }

    expect($clauses)->toBe([], sprintf(
        "A repair hands over a clause on its own:\n  %s\n\n"
        . '`Effects` and `Undoing` exist only as clauses of N2-R4. A method answering '
        . 'either is a template that can render what a repair affects without saying '
        . "whether it can be undone, which is the omission the requirement names.\n"
        . 'If a screen needs them, it needs all three — that is what `stated()` is.',
        implode("\n  ", $clauses),
    ));

    expect($strings)->toBe(['Modules\Kernel\Api\Repair::answers()'], sprintf(
        "A repair answers with a string somewhere other than `answers()`:\n  %s\n\n"
        . '`answers()` is published alone because a check name is not something the '
        . 'operator reads — it is how a screen files the repair under a finding, and a '
        . 'screen holding it has learned nothing about what the repair would do. A '
        . 'second string accessor is `does()` by another name (N2-R4).',
        implode("\n  ", $strings),
    ));
});
