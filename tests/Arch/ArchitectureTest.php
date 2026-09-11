<?php

declare(strict_types=1);

// The rules that are not about module boundaries — shape, naming, and the
// habits that make a class hard to test. Boundaries live in
// ModuleBoundariesTest.php, generated from each module's declared kind.

// ---------------------------------------------------------------------------
// A — framework coupling. Every rule here exists so a constructor tells the
// truth about what a class needs.
// ---------------------------------------------------------------------------

arch('A3/A4 — a class asks for what it needs rather than reaching for it')
    ->expect(['app', 'resolve', 'Illuminate\Support\Facades', 'Illuminate\Container'])
    ->not->toBeUsedIn(['App', 'Modules']);

arch('A5 — configuration is read from config, never from the environment')
    ->expect('env')
    ->toOnlyBeUsedIn('Config');

arch('A1 — no Eloquent anywhere')
    ->expect(['Illuminate\Database\Eloquent', 'Illuminate\Database\Query'])
    ->not->toBeUsedIn(['App', 'Modules']);

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

arch('C4 — nothing is silenced')
    ->expect(['App', 'Modules'])
    ->not->toUse('@');

// Scoped to production code: the test support classes read manifests off disk
// and a bare RuntimeException is the honest answer when one is unreadable.
arch('C3 — no exception is thrown that says nothing')
    ->expect(['Exception', 'RuntimeException', 'LogicException', 'InvalidArgumentException'])
    ->not->toBeUsedIn(['App', 'Modules']);

// ---------------------------------------------------------------------------
// H — naming and size. A name that permits anything is how a class acquires
// twenty methods.
// ---------------------------------------------------------------------------

// One rule per suffix rather than one chain of them. A chain reports the first
// offending suffix and stops, and `->not` cannot legally follow a completed
// expectation — the name of the failing rule is what tells you which word was
// used, so each word gets its own name.
foreach (['Manager', 'Helper', 'Util', 'Utils', 'Service', 'Data', 'Info'] as $vague) {
    arch("H1 — no class is named {$vague}, a name that permits anything")
        ->expect(['Modules', 'App'])
        ->not->toHaveSuffix($vague);
}

arch('H2 — an interface is named for what it does, not for being an interface')
    ->expect(['Modules', 'App'])
    ->not->toHaveSuffix('Interface');

arch('H2 — an abstract class is named for what it is, not for being abstract')
    ->expect(['Modules', 'App'])
    ->not->toHavePrefix('Abstract');

arch('every class is final')
    ->expect(['App', 'Modules'])
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
    ->expect(['Modules', 'App'])
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
