<?php

declare(strict_types=1);

use Tests\Support\ApiSurface;

// What a module publishes, and the shape it publishes it in.
//
// Every rule here is checked by reflection rather than by an `arch()`
// expectation, because an expectation reads names and imports and none of
// these are visible there: whether a return type is nullable, whether a
// parameter is a primitive, how many public methods a class has. Pest also
// raises rather than passing when asked about a namespace holding no classes,
// so a reflection sweep is what lets these rules exist before the module they
// govern does.

it('C1 — no Api method changes something and says nothing', function (): void {
    $offenders = [];

    foreach (ApiSurface::classesIn() as $class) {
        foreach (ApiSurface::publicMethodsOf($class) as $method) {
            if (ApiSurface::namesIn($method->getReturnType()) === ['void']) {
                $offenders[] = ApiSurface::describe($method);
            }
        }
    }

    expect($offenders)->toBe([], sprintf(
        "These cross a module boundary and answer with nothing:\n  %s\n\n"
        . 'This application spends its life talking to a machine that may be off, '
        . 'asleep, on another network or mid-update, so unreachable is a normal '
        . 'Tuesday rather than an exception. A method that returns void has no way to '
        . 'say it was refused except by throwing, which makes the common case the one '
        . 'the compiler cannot see you forgot. Answer with an Outcome and let the '
        . 'caller open it (C1).',
        implode("\n  ", $offenders),
    ));
});

it('C2 — no Api method answers with null', function (): void {
    $offenders = [];

    foreach (ApiSurface::classesIn() as $class) {
        foreach (ApiSurface::publicMethodsOf($class) as $method) {
            $returns = $method->getReturnType();

            if ($returns?->allowsNull() === true) {
                $offenders[] = ApiSurface::describe($method);
            }
        }
    }

    expect($offenders)->toBe([], sprintf(
        "These answer with null, which is the check that gets forgotten:\n  %s\n\n"
        . 'Null cannot say which of "not read yet" and "read, and there is nothing" '
        . 'it means, so the caller guesses. Answer with a type that says which — an '
        . 'absence type, an empty typed collection, or an Outcome carrying the refusal (C2).',
        implode("\n  ", $offenders),
    ));
});

it('D3 — no Api signature says mixed', function (): void {
    // The rule is "No `mixed` in public signatures" and its stated mechanism —
    // level max plus 100% type coverage — cannot see it. Type coverage counts
    // whether a type is *declared*, and `mixed` is a declared type; `mixed
    // $said): mixed` is fully covered by that measure and passes level max,
    // because `mixed` is legal PHP.
    //
    // So the rule read as enforced and nothing checked it. Nothing in the
    // repository does it today, which is why: the gap was invisible until the
    // first one, and by then it would have been the example to copy.
    //
    // `mixed` is the return of D1 and D2 wearing a different word. An array in
    // a signature says "some shape, work it out"; `mixed` says the same thing
    // about every value, and the caller works it out with `is_string`.
    $offenders = [];

    foreach (ApiSurface::classesIn() as $class) {
        foreach (ApiSurface::publicMethodsOf($class) as $method) {
            $types = ApiSurface::namesIn($method->getReturnType());

            foreach ($method->getParameters() as $parameter) {
                $types = [...$types, ...ApiSurface::namesIn($parameter->getType())];
            }

            if (in_array('mixed', $types, strict: true)) {
                $offenders[] = ApiSurface::describe($method);
            }
        }
    }

    expect($offenders)->toBe([], sprintf(
        "These publish a signature that says mixed:\n  %s\n\n"
        . '`mixed` is what D1 and D2 refuse, wearing a different word. An array in a '
        . 'signature says "some shape, work it out"; `mixed` says it about every value, '
        . "and whoever receives one works it out with `is_string`.\n"
        . 'Name the type. Where a value really can be several things, that is a sum type '
        . 'with an `either()`, which is how this codebase says it everywhere else (D3).',
        implode("\n  ", $offenders),
    ));
});

it('D1 — no Api signature is an untyped bag', function (): void {
    $offenders = [];

    foreach (ApiSurface::classesIn() as $class) {
        foreach (ApiSurface::publicMethodsOf($class) as $method) {
            $types = ApiSurface::namesIn($method->getReturnType());

            foreach ($method->getParameters() as $parameter) {
                $types = [...$types, ...ApiSurface::namesIn($parameter->getType())];
            }

            if (array_intersect($types, ['array', 'iterable']) !== []) {
                $offenders[] = ApiSurface::describe($method);
            }
        }
    }

    expect($offenders)->toBe([], sprintf(
        "These pass an array across a module boundary:\n  %s\n\n"
        . 'An array has no name, no invariants and nowhere to put the rules, so the '
        . 'knowledge of what is in it lives in whoever last wrote a foreach. Publish a '
        . 'typed collection that says what it holds — Findings, not array (D1).',
        implode("\n  ", $offenders),
    ));
});

it('D2 — no Api parameter is a bare primitive', function (): void {
    $offenders = [];

    foreach (ApiSurface::classesIn() as $class) {
        foreach (ApiSurface::publicMethodsOf($class) as $method) {
            if (ApiSurface::isNamedConstructor($method)) {
                continue;
            }

            foreach ($method->getParameters() as $parameter) {
                if (array_intersect(ApiSurface::namesIn($parameter->getType()), ['string', 'int', 'float']) !== []) {
                    $offenders[] = sprintf('%s, $%s', ApiSurface::describe($method), $parameter->getName());
                }
            }
        }
    }

    expect($offenders)->toBe([], sprintf(
        "These take a named concept as a primitive:\n  %s\n\n"
        . 'A stack id and a service id are both strings, and nothing stops you passing '
        . 'one where the other belongs — the mistake compiles and ships. StackId and '
        . 'ServiceId are two types and the mistake stops compiling. A primitive crosses '
        . 'into a module in exactly one place: a static named constructor answering its '
        . "own type, which is where the string is checked and given a name (D2).\n"
        . 'A primitive coming back out is not the same problem and is not refused here: '
        . 'an adapter has to put the value on the wire eventually, and the call sites '
        . 'that matter still hold the type.',
        implode("\n  ", $offenders),
    ));
});

it('M1 — a query asks and a command decides', function (): void {
    $offenders = [];

    foreach (ApiSurface::classesIn('Queries') as $class) {
        foreach (ApiSurface::publicMethodsOf($class) as $method) {
            if (in_array(ApiSurface::OUTCOME, ApiSurface::namesIn($method->getReturnType()), strict: true)) {
                $offenders[] = sprintf('%s answers with an Outcome', ApiSurface::describe($method));
            }
        }
    }

    foreach (ApiSurface::classesIn('Commands') as $class) {
        foreach (ApiSurface::publicMethodsOf($class) as $method) {
            if (ApiSurface::namesIn($method->getReturnType()) !== [ApiSurface::OUTCOME]) {
                $offenders[] = sprintf('%s does not answer with an Outcome', ApiSurface::describe($method));
            }
        }
    }

    expect($offenders)->toBe([], sprintf(
        "The split between asking and deciding is not held here:\n  %s\n\n"
        . 'A query is a question about state and cannot refuse, so an Outcome on one is '
        . 'either a lie or a command wearing the wrong name. A command changes something '
        . 'on a machine that may be off, asleep or mid-update, so it answers with an '
        . 'Outcome every time — that is what stops a caller quietly ignoring a refusal '
        . "(M1, C1).\nThere is no bus and no handler in between: the class is the "
        . 'message, and the call is the dispatch.',
        implode("\n  ", $offenders),
    ));
});

it('M2 — a command or a query does one thing', function (): void {
    $offenders = [];

    foreach ([...ApiSurface::classesIn('Commands'), ...ApiSurface::classesIn('Queries')] as $class) {
        $methods = ApiSurface::publicMethodsOf($class);

        if (count($methods) !== 1) {
            $offenders[] = sprintf(
                '%s exposes %d public methods: %s',
                $class->getName(),
                count($methods),
                implode(', ', array_map(
                    static fn(ReflectionMethod $method): string => $method->getName(),
                    $methods,
                )),
            );
        }
    }

    expect($offenders)->toBe([], sprintf(
        "These are more than one action:\n  %s\n\n"
        . 'A class with one public method is named for what it does, has a constructor '
        . 'that lists exactly what that one thing needs, and cannot accumulate a second '
        . 'responsibility without someone noticing. A second method is how a command '
        . 'becomes the Manager that H1 refuses by name (M2, H1).',
        implode("\n  ", $offenders),
    ));
});

it('M3 — a command can be sent twice', function (): void {
    $offenders = [];

    foreach (ApiSurface::classesIn('Commands') as $class) {
        $constructor = $class->getConstructor();
        $carried = [];

        foreach ($constructor?->getParameters() ?? [] as $parameter) {
            $carried = [...$carried, ...ApiSurface::namesIn($parameter->getType())];
        }

        if (! in_array(ApiSurface::IDEMPOTENCY_KEY, $carried, strict: true)) {
            $offenders[] = $class->getName();
        }
    }

    expect($offenders)->toBe([], sprintf(
        "These commands carry no idempotency key:\n  %s\n\n"
        . 'This application sends commands over a link that drops: a phone loses wifi '
        . 'mid-request and has no way to know whether the stack applied the update or '
        . 'never heard the question. Without a key the safe answer is to do nothing and '
        . 'ask the operator, which is the worst screen in the app. With one, retrying is '
        . 'free. Take a Modules\\Kernel\\Api\\IdempotencyKey in the constructor (M3).',
        implode("\n  ", $offenders),
    ));
});
