<?php

declare(strict_types=1);

use Tests\Support\Module;

// The rules that are not about module boundaries — shape, naming, and the
// habits that make a class hard to test. Boundaries live in
// ModuleBoundariesTest.php, generated from each module's declared kind.
//
// ---------------------------------------------------------------------------
// Where the namespaces below come from, and why not just `Modules`.
//
// `Modules` is not a namespace anything is registered under. Each module
// registers its own — `Modules\Health\`, `Modules\Kernel\` — and Pest resolves
// an expectation by matching a registered PSR-4 prefix. Given the bare parent
// it matches no files, finds nothing to check, and reports a green tick.
//
// The failure is silent and total: with the bare parent, a class named
// `HealthManager` passes H1, a module class calling a facade passes A3/A4, and
// a non-final class passes `toBeFinal`. The only way to tell the difference
// between a rule that holds and a rule that checked nothing is to plant a
// violation under it.
//
// So the list is derived from the manifests, the same way the boundary rules
// are. A module added tomorrow is covered without anyone editing this line,
// and a module holding no classes yet is left out because Pest raises rather
// than passing when asked about an empty namespace.
// ---------------------------------------------------------------------------

$ourCode = ['App', ...Module::namespaces()];

// ---------------------------------------------------------------------------
// A — framework coupling. Every rule here exists so a constructor tells the
// truth about what a class needs.
// ---------------------------------------------------------------------------

// One symbol per rule, and functions kept apart from namespaces: a single
// `expect()` list holding both passes vacuously. `['app',
// 'Illuminate\Support\Facades']` reports nothing at all, while either entry
// alone reports — so mixing the two kinds silently disables the whole rule.
foreach (['app', 'resolve'] as $located) {
    arch(sprintf('A3 — a class asks for what it needs rather than calling %s()', $located))
        ->expect($located)
        ->not->toBeUsedIn($ourCode);
}

foreach (['Illuminate\Support\Facades', 'Illuminate\Container'] as $global) {
    arch(sprintf('A2/A4 — %s is a global lookup no constructor mentions', $global))
        ->expect($global)
        ->not->toBeUsedIn($ourCode);
}

arch('A5 — configuration is read from config, never from the environment')
    ->expect('env')
    ->toOnlyBeUsedIn('Config');

arch('A1 — no Eloquent anywhere')
    ->expect(['Illuminate\Database\Eloquent', 'Illuminate\Database\Query'])
    ->not->toBeUsedIn($ourCode);

// Mutable global state is checked in NoGlobalStateTest.php by reflection —
// Pest's architecture expectations have no rule for it.

// ---------------------------------------------------------------------------
// B — the untestable primitives. PHPStan's disallowed-calls catches the call
// sites; these catch the imports that would precede them.
// ---------------------------------------------------------------------------

arch('B1 — time arrives through the clock port')
    ->expect(['Carbon', 'Illuminate\Support\Carbon', 'DateTime', 'DateTimeImmutable'])
    ->not->toBeUsedIn('Modules\Kernel\Api');

// ---------------------------------------------------------------------------
// C/D — errors and data shape.
// ---------------------------------------------------------------------------

// C4 lives in ergebnis's NoErrorSuppressionRule under `allRules: true`, not
// here. Pest's `not->toUse('@')` does not report a suppressed call —
// `@file_get_contents(...)` passes it — and the analyser catches it at the call
// site, which is the only place `@` exists.

// Scoped to production code: the test support classes read manifests off disk
// and a bare RuntimeException is the honest answer when one is unreadable.
//
// This catches the import. The throw itself is caught by PHPStan's ban on these
// constructors, which is the half that matters — a file can throw a global
// `\RuntimeException` without importing anything.
arch('C3 — no exception is thrown that says nothing')
    ->expect(['Exception', 'RuntimeException', 'LogicException', 'InvalidArgumentException'])
    ->not->toBeUsedIn($ourCode);

// ---------------------------------------------------------------------------
// H — naming and size. A name that permits anything is how a class acquires
// twenty methods.
// ---------------------------------------------------------------------------

// One rule per suffix rather than one chain of them. A chain reports the first
// offending suffix and stops, and `->not` cannot legally follow a completed
// expectation — the name of the failing rule is what tells you which word was
// used, so each word gets its own name.
foreach (['Manager', 'Helper', 'Util', 'Utils', 'Service', 'Data', 'Info'] as $vague) {
    arch(sprintf('H1 — no class is named %s, a name that permits anything', $vague))
        ->expect($ourCode)
        ->not->toHaveSuffix($vague);
}

arch('H2 — an interface is named for what it does, not for being an interface')
    ->expect($ourCode)
    ->not->toHaveSuffix('Interface');

arch('H2 — an abstract class is named for what it is, not for being abstract')
    ->expect($ourCode)
    ->not->toHavePrefix('Abstract');

// An exception is the one class people habitually name after its base class
// rather than after its subject. `StackUnreachableException` says twice that it
// is an exception and once what happened; `StackUnreachable` reads as a fact at
// the catch site, which is where the name is actually used. The suffix also
// hides the duplicate: StackUnreachableException and CannotReachStackException
// look like two different things in a directory listing.
arch('H6 — an exception is named for what happened, not for being an exception')
    ->expect($ourCode)
    ->not->toHaveSuffix('Exception');

arch('every class is final')
    ->expect($ourCode)
    ->toBeFinal();

// ---------------------------------------------------------------------------
// Leftovers.
// ---------------------------------------------------------------------------

arch('no debugging survives a commit')
    ->expect(['dd', 'dump', 'var_dump', 'print_r', 'ray', 'die', 'exit', 'var_export'])
    ->not->toBeUsed();

// ---------------------------------------------------------------------------
// G — tests. A fake that has drifted makes the suite green while the app is
// broken, so the rules that keep fakes honest are themselves enforced.
// ---------------------------------------------------------------------------

// ---------------------------------------------------------------------------
// D4 — a closed set is an enum.
// ---------------------------------------------------------------------------

// A string constant naming one of a fixed set of states gives the analyser
// nothing: every `string` is assignable to it, a typo compiles, and a `match`
// over it cannot be checked for the arm nobody wrote. An enum makes the set the
// type. This matters most for the screen states (Loading, Ready, Refused,
// Stale) — shipmonk's ForbidMatchDefaultArmForEnums is the other half, because
// a `default` arm silently restores the hole the enum just closed.
arch('D4 — a closed set is an enum, not a handful of string constants')
    ->expect($ourCode)
    ->not->toHaveSuffix('Status')
    ->not->toHaveSuffix('State')
    ->not->toHaveSuffix('Type')
    ->not->toHaveSuffix('Kind');

arch('G1 — nothing mocks a type we do not own')
    ->expect(['Mockery', 'PHPUnit\Framework\MockObject'])
    ->not->toBeUsed();

// ---------------------------------------------------------------------------
// The framework's own presets, which catch a long tail cheaply.
// ---------------------------------------------------------------------------

arch()->preset()->php();
arch()->preset()->security();
