<?php

declare(strict_types=1);

use Tests\Support\OurCode;

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
// So the list is derived, and from the widest honest answer rather than from
// the manifests: every PSR-4 prefix pointing into a tree `phpunit.xml` holds to
// a coverage floor. A module added tomorrow is covered without anyone editing
// this line, and a module holding no classes yet is left out because Pest
// raises rather than passing when asked about an empty namespace.
//
// The manifests were the answer before, and they were the wrong width in both
// directions. `Bootstrap\Composition` is registered, measured, and was in no
// rule here — a non-final class named `ThingManager` sat in the composition
// root, the directory this architecture calls its sharpest case, and the whole
// Arch suite passed. `Lemonfiber\Native` was outside for the same reason: a
// path package is not a module, so `app-modules` did not reach it. And `App`
// was *in*, resolving to the formatter's own source under `vendor` (`R4`).
// ---------------------------------------------------------------------------

$ourCode = OurCode::namespaces();

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

// Everywhere but the composition root, which is where a facade is the
// composition rather than a reach into one: `Route::native()` is how a screen
// gets declared, and there is no constructor for a router to arrive through
// before the application is built. `phpstan.neon` grants the same exemption by
// path, and a rule here that did not would refuse what the analyser allows —
// which is the shape of exemption somebody switches a rule off over.
$outsideTheComposition = array_values(array_filter(
    $ourCode,
    static fn(string $namespace): bool => $namespace !== OurCode::THE_COMPOSITION_ROOT,
));

foreach (['Illuminate\Support\Facades', 'Illuminate\Container'] as $global) {
    arch(sprintf('A2/A4 — %s is a global lookup no constructor mentions', $global))
        ->expect($global)
        ->not->toBeUsedIn($outsideTheComposition);
}

arch('A5 — configuration is read from config, never from the environment')
    ->expect('env')
    ->toOnlyBeUsedIn('Config');

foreach (['Illuminate\Database\Eloquent', 'Illuminate\Database\Query'] as $orm) {
    arch(sprintf('A1 — no %s anywhere', $orm))
        ->expect($orm)
        ->not->toBeUsedIn($ourCode);
}

// Mutable global state is checked in NoGlobalStateTest.php by reflection —
// Pest's architecture expectations have no rule for it.

// ---------------------------------------------------------------------------
// B — the untestable primitives. PHPStan's disallowed-calls catches the call
// sites; these catch the imports that would precede them.
// ---------------------------------------------------------------------------

foreach (['Carbon', 'Illuminate\Support\Carbon', 'DateTime', 'DateTimeImmutable'] as $ambient) {
    arch(sprintf('B1 — %s does not reach the kernel', $ambient))
        ->expect($ambient)
        ->not->toBeUsedIn('Modules\Kernel\Api');
}

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
foreach (['Exception', 'RuntimeException', 'LogicException', 'InvalidArgumentException'] as $bare) {
    arch(sprintf('C3 — %s says nothing a catch block can act on', $bare))
        ->expect($bare)
        ->not->toBeUsedIn($ourCode);
}

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

// `->classes()` rather than the bare list, because Pest's `toBeFinal` answers
// false for an enum by construction — `! enum_exists($name) && ...` — so an
// enum can never satisfy it. D4 requires a closed set to be an enum, which
// would make the two rules together unsatisfiable the moment anyone obeyed the
// first one. Nothing is exempted by this: an enum is final in the language and
// `final enum` is a parse error, so there is no unsealed enum for the rule to
// have caught.
arch('every class is final')
    ->expect($ourCode)
    ->classes()
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

// G1 is checked over the text of the test files in TestConventionsTest. A Pest
// namespace expectation cannot see it: `PHPUnit\` is not a registered PSR-4
// prefix in this installation, so an expectation naming it resolves to no files
// and reports nothing — and the other mocking library is not installed, which
// makes an expectation naming it silent for a second, different reason.

// ---------------------------------------------------------------------------
// The framework's own presets, which catch a long tail cheaply.
// ---------------------------------------------------------------------------

arch()->preset()->php();
arch()->preset()->security();
