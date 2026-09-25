<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Credential;
use Modules\Kernel\Api\Effects;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Held;
use Modules\Kernel\Api\IdempotencyKey;
use Modules\Kernel\Api\Interrupted;
use Modules\Kernel\Api\Notification;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Pairing;
use Modules\Kernel\Api\Problem;
use Modules\Kernel\Api\Reach;
use Modules\Kernel\Api\Release;
use Modules\Kernel\Api\Releases;
use Modules\Kernel\Api\Repair;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\TakingAnUpdate;
use Modules\Kernel\Api\Undoing;
use Modules\Kernel\Api\Upkeep;
use Native\Mobile\Edge\NativeComponent;
use Tests\Support\ApiSurface;
use Tests\Support\Module;

/**
 * Requirements that are satisfied by a path not existing.
 *
 * Several of the companion's security requirements are not about what a type
 * does — they are about what it must never be able to reach. A surviving pairing is one:
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
            Session::class,
            [Address::class, Reach::class],
            'N1-R14',
            'The app talks to a stack over the LAN today and over an overlay later, and '
            . 'neither change may require a change to how it authenticates. What makes '
            . 'that true is that admission does not know where the stack is: a session is '
            . 'a token for a header (N1-R8), and a token that could be handed an address '
            . 'is one somebody scopes to an address the first time two stacks are '
            . 'reachable at once — which is a change to authentication, made for a '
            . 'transport reason, exactly as the requirement forbids.',
        ],
        [
            Credential::class,
            [Address::class, Reach::class],
            'N1-R14',
            'The same argument one step earlier. A credential is exchanged for a session '
            . 'and is spent doing it (N1-R7); where that exchange happened is the '
            . "transport's business. A credential that could name an address is the "
            . 'beginning of an exchange that means something different over an overlay '
            . 'than over the LAN.',
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
        [
            Notification::class,
            [Code::class, Problem::class, Obstacle::class],
            'N4-R11',
            'The app raises no alerts of its own. A notification carrying no text is only '
            . 'half of that, because the app has codes: Obstacle::code() mints '
            . 'COMPANION-NO-ANSWER about a server that did not answer, and Problem::code() '
            . 'carries what a stack said when it refused a request the operator made. '
            . 'Either one handed to fromTheCore() is an alert nobody in the core decided '
            . 'to raise, so the identifier is a WhatTheCoreDecided and a Code cannot '
            . 'become one.',
        ],
    ];
}

it('N1-R17 — every type this table refuses is one this application has', function (): void {
    // `Foo::class` is a string the compiler builds out of the `use` above it,
    // and it resolves whether or not anything of that name exists. So a renamed
    // type leaves a row here that matches nothing, and the rule below goes on
    // passing while protecting one fewer thing than it says it does — which is
    // worse than not listing it, because a reader stops checking.
    //
    // The subject half is caught already: `ApiSurface::reflect()` raises on a
    // name nothing declares. The forbidden half is not, and it is the half that
    // matters — it is the list a rule is read against, and an empty
    // intersection is indistinguishable from a rule that holds.
    //
    // This is the floor and it cannot be edited down, because it counts nothing
    // of its own: what it reads is the table, and a row removed takes its own
    // check with it rather than leaving a number that no longer adds up.
    $gone = [];

    foreach (typesThatMustNotMeet() as [$subject, $forbidden, $requirement, $why]) {
        foreach ([$subject, ...$forbidden] as $named) {
            if (! class_exists($named) && ! interface_exists($named) && ! enum_exists($named)) {
                $gone[] = sprintf('%s — %s', $requirement, $named);
            }
        }
    }

    expect(array_values(array_unique($gone)))->toBe([], sprintf(
        "This table refuses types this application does not have:\n  %s\n\n"
        . 'Find what each was renamed to and name it here. A row that matches nothing is a '
        . "requirement that is written down and not enforced.\n",
        implode("\n  ", $gone),
    ));
});

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
        foreach (ApiSurface::namedBy(ApiSurface::reflect($subject)) as [$where, $named]) {
            foreach (array_intersect($named, $forbidden) as $reached) {
                $found[] = sprintf(
                    "%s — %s names %s\n    %s",
                    $requirement,
                    $where,
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
                $found[] = ApiSurface::reflect($name);
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
        foreach (ApiSurface::namedBy($screen) as [$where, $types]) {
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
    // down.
    //
    // `does` is the one clause with no type of its own — it is prose, and the
    // only prose a repair holds — so the string is the shape to look for. This
    // read `[Repair::answers()]` while a check was a string too, which meant
    // the rule could not tell the identifier from the sentence and had to name
    // the one method allowed to answer either. `answers()` is a {@see Check}
    // now, for the reason `Check` exists at all: a check and a sentence both
    // arrive on the same row and a call with them the wrong way round compiled.
    // With that gone the list is empty, and an empty list is a stronger claim —
    // *no string leaves a repair on its own* rather than *no string but this
    // one*.
    $clauses = [];
    $strings = [];

    foreach (ApiSurface::answeredBy(ApiSurface::reflect(Repair::class)) as [$where, $answers]) {
        foreach (array_intersect($answers, [Effects::class, Undoing::class]) as $clause) {
            $clauses[] = sprintf('%s answers %s', $where, $clause);
        }

        if (in_array('string', $answers, strict: true)) {
            $strings[] = $where;
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

    // Asserted before the emptiness, because an empty list is also what a rule
    // reading nothing produces: a `Repair` that answered with no type at all
    // would satisfy the expectation below and prove nothing.
    expect(ApiSurface::answeredBy(ApiSurface::reflect(Repair::class)))
        ->not->toBe([], 'Repair answers nothing, so this rule proved nothing');

    expect($strings)->toBe([], sprintf(
        "A repair answers with a string:\n  %s\n\n"
        . 'The only prose a repair holds is `does`, and `N2-R4` has it leave with the '
        . 'other two clauses or not at all — so a string coming out on its own is '
        . "`does()` by another name.\nWhich check it answers is a `Check` and is "
        . 'published alone, because that is not something the operator reads: it is how '
        . 'a screen files the repair under a finding (N2-R4).',
        implode("\n  ", $strings),
    ));
});

it('N2-R20 — the apply path takes a reading, and no release', function (): void {
    // The requirement's first clause: an update the stack did not report as
    // available is never applied. The stack moves services onto its own build's
    // pins, so no release is chosen, and a `Release` reaching the apply path
    // would be a release history read as a menu of offers — which is what the
    // screen once did with every release the stack had ever shipped.
    //
    // So the only way in is a reading: `TakingAnUpdate::offeredBy()` takes an
    // `Upkeep` and refuses one that offered nothing. Asked of the named
    // constructors' parameters, because those are the doors.
    $applyPath = [];

    foreach (ApiSurface::publicMethodsOf(ApiSurface::reflect(TakingAnUpdate::class)) as $method) {
        if (! ApiSurface::isNamedConstructor($method)) {
            continue;
        }

        foreach ($method->getParameters() as $parameter) {
            $applyPath = [...$applyPath, ...ApiSurface::namesIn($parameter->getType())];
        }
    }

    // The floor, and it is a type rather than a count on purpose: what is
    // worth pinning is that a reading is still the currency of applying one.
    // The day it is not, this rule is watching a door that moved.
    expect(in_array(Upkeep::class, $applyPath, strict: true))->toBeTrue(
        'the apply path no longer takes a reading, so this rule is watching a door that moved',
    );

    $releases = array_values(array_intersect($applyPath, [Release::class, Releases::class]));

    expect($releases)->toBe([], sprintf(
        "The apply path takes a release:\n  %s\n\n"
        . 'An update moves the services onto the versions the stack\'s build pins; the release '
        . 'history says where those pins came from and is not a list of offers. Agreeing to an '
        . 'update is agreeing to what a reading offered, so `TakingAnUpdate` is built from an '
        . '`Upkeep` and nothing else (N2-R20).',
        implode("\n  ", $releases),
    ));
});
