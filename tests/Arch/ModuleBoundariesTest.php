<?php

declare(strict_types=1);

use Modules\Kernel\Api\Credential;
use Tests\Support\Imports;
use Tests\Support\Kind;
use Tests\Support\Module;

// The dependency rules in ARCHITECTURE.md, generated from what each module
// declares itself to be.
//
// Written as a loop rather than as one arch() call per module pair on purpose.
// A hand-written list has to be extended every time a module is added, and the
// failure mode of forgetting is silence: the new module simply has no rules and
// nothing says so. Deriving them from the manifests means a module is governed
// the moment it exists.

$modules = Module::populated();

// Not a guard against an empty repository — a guard against this file quietly
// testing nothing. If every module is empty, that is a fact worth stating
// rather than a green tick.
it('has modules to check', function () use ($modules): void {
    expect($modules)->not->toBeEmpty(
        'No module holds a class yet, so every boundary rule below is vacuous.',
    );
})->skip(
    $modules === [],
    'No module holds a class yet. These rules apply as soon as one does.',
);

// The three boundary questions are asked by reading each file's imports rather
// than by a Pest namespace expectation. The expectation resolves a string
// against the autoloader's registered PSR-4 prefixes, so a string that is not
// one of them matches no files and reports nothing — `Native` is silent where
// `Native\Mobile` reports, and the two look identical from here. These are the
// rules the rest of the architecture rests on, so they are answered exactly.
/**
 * The values whose whole purpose is that they change, each with the reason.
 *
 * Written out rather than inferred, and kept to the smallest possible list. An
 * exemption that can be earned by a naming convention is an exemption anybody
 * can take; one that has to be added here is a line somebody has to justify in
 * a diff.
 */
const MUTABLE_BY_DESIGN = [
    // `N1-R7` — a credential is exchanged for a session once and nothing is
    // kept to re-send. Forgetting is a mutation, and it is the entire point: a
    // readonly credential is one that still holds its secret after the
    // exchange, which is the thing the requirement forbids. The immutability
    // that is right for every other value here is exactly wrong for this one.
    Credential::class,
];

foreach ($modules as $module) {
    it(sprintf('A7/E4 — %s stays inside what a %s module may name', $module->name, $module->kind->value), function () use ($module): void {
        $offenders = reachesOutside($module, $module->kind->forbiddenVendors());

        expect($offenders)->toBe([], sprintf(
            "%s names something its kind may not:\n  %s\n\n"
            . 'A %s module is defined by what it cannot reach. Take what this needs as a '
            . 'port in Modules\\Kernel\\Api and let the composition root decide which '
            . 'implementation arrives (A7, E4).',
            $module->name,
            implode("\n  ", $offenders),
            $module->kind->value,
        ));
    });

    it(sprintf("E1 — %s respects the other modules' boundaries", $module->name), function () use ($module): void {
        $offenders = reachesOutside($module, $module->forbiddenModuleNamespaces());

        expect($offenders)->toBe([], sprintf(
            "%s reaches into a module it may not:\n  %s\n\n"
            . "A module's kind decides which other kinds it may name, and a permitted "
            . 'module is still only reachable through its published Api (E1, E2).',
            $module->name,
            implode("\n  ", $offenders),
        ));
    });

    it(sprintf('E2 — %s publishes an Api and keeps the rest to itself', $module->name), function () use ($module): void {
        $internal = sprintf('%s\\Internal', $module->namespace);
        $intruders = [];

        foreach (Module::all() as $other) {
            if ($other->name === $module->name) {
                continue;
            }

            foreach ($other->classes() as $file) {
                if (Imports::anyUnder(Imports::of($file), $internal)) {
                    $intruders[] = $file;
                }
            }
        }

        expect($intruders)->toBe([], sprintf(
            "These reach into %s's internals:\n  %s\n\n"
            . 'Anything under Internal can be renamed, split or deleted without reading '
            . 'another module, and that guarantee is the whole reason the directory '
            . 'exists. Publish what is needed under Api, or move the caller (E2).',
            $module->name,
            implode("\n  ", $intruders),
        ));
    });

    if (! $module->kind->renders()) {
        // Only a surface holds state the renderer re-reads. Everything else is
        // a value or a decision, and both are safer readonly.
        //
        // Asked by reflection rather than with `->toBeReadonly()`, which refuses
        // an interface. `kernel` is the module of ports, so that expectation
        // fails every port it publishes for declaring no state at all — a gate
        // that blocks its own cure. Interfaces and enums are skipped by name
        // below: an interface holds no state and an enum cannot be changed.
        //
        // A Throwable is skipped for a third reason, and it is the language's
        // rather than a judgement: a readonly class may only extend a readonly
        // one, and `Exception` declares `$message`, `$code`, `$file`, `$line`
        // and `$trace` as ordinary properties. `final readonly class X extends
        // InvalidArgumentException` is a fatal error, so requiring it here
        // would put C3 — every thrown exception is module-owned — permanently
        // out of reach. Nothing is exempted that could have been caught: the
        // mutability belongs to PHP's base class, not to anything written here.
        it(sprintf('%s holds no mutable state', $module->name), function () use ($module): void {
            $mutable = [];

            foreach ($module->classNames() as $name) {
                $class = new ReflectionClass($name);

                if ($class->isInterface() || $class->isEnum() || $class->implementsInterface(Throwable::class)) {
                    continue;
                }

                if (in_array($name, MUTABLE_BY_DESIGN, strict: true)) {
                    continue;
                }

                if (! $class->isReadOnly()) {
                    $mutable[] = $name;
                }
            }

            expect($mutable)->toBe([], sprintf(
                "These can be changed after they are built:\n  %s\n\n"
                . 'Only a surface holds mutable state, because only a surface is re-read by '
                . 'the renderer. Everywhere else a value that can change after construction '
                . 'is a value whose invariants were checked once and can be false by the time '
                . 'anyone reads it. Mark the class readonly.',
                implode("\n  ", $mutable),
            ));
        });
    }
}

/**
 * Every file in the module that names one of the forbidden namespaces.
 *
 * @param list<string> $forbidden
 *
 * @return list<string>
 */
function reachesOutside(Module $module, array $forbidden): array
{
    $offenders = [];

    foreach ($module->classes() as $file) {
        $names = Imports::of($file);

        foreach ($forbidden as $namespace) {
            if (Imports::anyUnder($names, $namespace)) {
                $offenders[] = sprintf('%s names %s', $file, $namespace);
            }
        }
    }

    return $offenders;
}

// ---------------------------------------------------------------------------
// The exceptions, asserted by name so they cannot be widened by accident.
// ---------------------------------------------------------------------------

// `N1-R16` and `N1-R1`. The composer manifests already make this true —
// modules/sdk is the only one requiring lemonfiber/sdk-php — but that only fails
// when the dependency analyser runs. This fails in the test suite, which runs
// first.
//
// `N1-R1` is the same fact from the other side: the app speaks the published web
// API contract and does not implement a second client of its own (`ADR-0013`).
// One module naming the SDK is what makes a second client impossible to write
// without this failing.
arch('E3 — the SDK is named in exactly one module')
    ->expect('Lemonfiber\Sdk')
    ->toOnlyBeUsedIn('Modules\Sdk');

it('E3 — only the sdk adapter speaks HTTP', function (): void {
    $offenders = [];

    foreach (Module::all() as $module) {
        if ($module->name === 'sdk') {
            continue;
        }

        $offenders = [...$offenders, ...reachesOutside($module, ['GuzzleHttp', 'Saloon', 'Symfony\\Component\\HttpClient'])];
    }

    expect($offenders)->toBe([], sprintf(
        "These speak HTTP without being the SDK adapter:\n  %s\n\n"
        . 'Every call to lemonfiber goes through lemonfiber/sdk-php. A second client in '
        . 'this application is a fourth consumer the contract does not know it has, and '
        . 'the first thing it will get wrong is the envelope (E3, N1-R1, N1-R16).',
        implode("\n  ", $offenders),
    ));
});

// `N1-R17` — where the SDK does not expose something, the app waits.
//
// Three things must not happen, and two of them are already impossible here.
// Reaching past the SDK and re-implementing the call both mean speaking HTTP
// from outside `modules/sdk`, which the rule above refuses, or naming
// `Lemonfiber\Sdk` from outside it, which the one above that refuses.
//
// The third — approximating the answer from another endpoint — is deliberately
// *not* given a rule, and the reason is worth writing down because the absence
// looks like an oversight.
//
// A checker for it would have to read prose. The first attempt matched comments
// containing "work around", "approximate" and "derive it from", and it found
// two files: `Finding`, whose docblock explains that a payload designed before
// its screen would be one the first screen has to *work around*, and `Wire`,
// which says the translator will ask `WireVersion` rather than *re-derive it
// from* an integer. Both are the rule being explained, not broken. A rule that
// fires on its own documentation is a rule somebody deletes, and the deletion
// takes the real coverage with it — which is exactly why `Vocabulary` reads
// tokens rather than text.
//
// So this one is held by review, and by `EveryPortIsProvenTwiceTest`: an
// approximation has to live somewhere, and somewhere is a class that would need
// a port, a contract test and an adapter before it could reach anything. That
// is a long way to go without somebody asking why.

arch('nothing opens a socket by hand')
    ->expect(['curl_init', 'curl_exec', 'fsockopen', 'stream_socket_client'])
    ->not->toBeUsed();

// The composition root is the one place a port is allowed to meet an adapter.
//
// One rule per adapter. A list on the left of `toBeUsedIn` is read as "uses all
// of these", so three adapters in one expectation would report only a module
// that named every one of them — and a module naming a single adapter, which is
// the whole failure being guarded against, would pass.
it('E1 — only the composition root names an adapter', function (): void {
    $adapters = array_map(
        static fn(Module $m): string => $m->namespace,
        array_values(array_filter(
            Module::all(),
            static fn(Module $m): bool => $m->kind === Kind::Adapter,
        )),
    );

    $offenders = [];

    foreach (Module::all() as $module) {
        if ($module->kind === Kind::Adapter) {
            continue;
        }

        $offenders = [...$offenders, ...reachesOutside($module, $adapters)];
    }

    expect($offenders)->toBe([], sprintf(
        "These name an adapter from outside the composition root:\n  %s\n\n"
        . 'A port meets its adapter in bootstrap/Composition and nowhere else. A module that '
        . 'names one has decided which implementation it gets, which is the decision '
        . 'that makes it untestable without the thing the adapter talks to (E1).',
        implode("\n  ", $offenders),
    ));
});
