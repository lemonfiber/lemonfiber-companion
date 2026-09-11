<?php

declare(strict_types=1);

// The rules that are not about module boundaries — shape, naming, and the
// habits that make a class hard to test. Boundaries live in
// ModuleBoundariesTest.php, generated from each module's declared kind.

// ---------------------------------------------------------------------------
// A — framework coupling. Every rule here exists so a constructor tells the
// truth about what a class needs.
// ---------------------------------------------------------------------------

arch('a class asks for what it needs rather than reaching for it')
    ->expect(['app', 'resolve', 'Illuminate\Support\Facades', 'Illuminate\Container'])
    ->not->toBeUsedIn(['App', 'Modules']);

arch('configuration is read from config, never from the environment')
    ->expect('env')
    ->toOnlyBeUsedIn('Config');

arch('no Eloquent anywhere')
    ->expect(['Illuminate\Database\Eloquent', 'Illuminate\Database\Query'])
    ->not->toBeUsedIn(['App', 'Modules']);

// Mutable global state is checked in NoGlobalStateTest.php by reflection —
// Pest's architecture expectations have no rule for it.

// ---------------------------------------------------------------------------
// B — the untestable primitives. PHPStan's disallowed-calls catches the call
// sites; these catch the imports that would precede them.
// ---------------------------------------------------------------------------

arch('time arrives through the clock port')
    ->expect(['Carbon', 'Illuminate\Support\Carbon', 'DateTime', 'DateTimeImmutable'])
    ->not->toBeUsedIn('Modules\Kernel\Api');

// ---------------------------------------------------------------------------
// C/D — errors and data shape.
// ---------------------------------------------------------------------------

arch('nothing is silenced')
    ->expect(['App', 'Modules'])
    ->not->toUse('@');

// Scoped to production code: the test support classes read manifests off disk
// and a bare RuntimeException is the honest answer when one is unreadable.
arch('no exception is thrown that says nothing')
    ->expect(['Exception', 'RuntimeException', 'LogicException', 'InvalidArgumentException'])
    ->not->toBeUsedIn(['App', 'Modules']);

// ---------------------------------------------------------------------------
// H — naming and size. A name that permits anything is how a class acquires
// twenty methods.
// ---------------------------------------------------------------------------

arch('no class is named for nothing in particular')
    ->expect(['Modules', 'App'])
    ->not->toHaveSuffix('Manager')
    ->not->toHaveSuffix('Helper')
    ->not->toHaveSuffix('Util')
    ->not->toHaveSuffix('Utils')
    ->not->toHaveSuffix('Service')
    ->not->toHaveSuffix('Data')
    ->not->toHaveSuffix('Info');

arch('an interface is named for what it does, not for being an interface')
    ->expect(['Modules', 'App'])
    ->not->toHaveSuffix('Interface')
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

arch('nothing mocks a type we do not own')
    ->expect(['Mockery', 'PHPUnit\Framework\MockObject'])
    ->not->toBeUsed();

// ---------------------------------------------------------------------------
// The framework's own presets, which catch a long tail cheaply.
// ---------------------------------------------------------------------------

arch()->preset()->php();
arch()->preset()->security();
