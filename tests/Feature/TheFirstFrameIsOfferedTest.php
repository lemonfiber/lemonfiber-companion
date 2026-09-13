<?php

declare(strict_types=1);

use Illuminate\Support\Facades\View;
use Modules\Operator\Internal\Screens\NoStackYet;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\Fakes\StacksInMemory;

// N1-R35 — a launch with no stack configured reaches a screen, not an empty
// surface.
//
// The route is declared by the operator surface's own service provider, which
// Laravel discovers through that module's manifest, after every provider has
// booted because the macro comes from another package. That is three mechanisms
// deep and each of them fails silently: a provider that is not discovered
// registers nothing, a callback that fires too early throws where nobody looks,
// and the application boots perfectly in both cases with no screen behind `/`.
//
// Asked of the navigation stack rather than over HTTP. `NativeRouter` is what
// the device consults, and a request in a test never enters the runloop — so an
// HTTP assertion would be testing NativePHP's test-mode stub rather than this
// application's wiring.

it('N1-R35 — the first frame is registered, and it is this screen', function (): void {
    $resolved = NativeRouter::resolve('/');

    expect($resolved)->not->toBeNull(
        'Nothing is registered for `/`. A launch with no stack configured would reach '
        . 'an empty surface, which N1-R35 refuses by name. Check that the operator '
        . "module's provider is discovered and that its booted callback ran.",
    );

    expect($resolved['class'] ?? null)->toBe(NoStackYet::class);
});

it('resolves the screen\'s view name to the surface\'s own template', function (): void {
    // `render()` answering the right name proves nothing on its own: a view
    // name is a string until something resolves it. The mutation run said so —
    // removing `loadViewsFrom` entirely, or walking up one directory too few,
    // left every other test in this file passing.
    //
    // Asked of the finder rather than of `view()->exists()`, which larastan
    // resolves at analysis time and narrows to `true` — the assertion reads as
    // dead where it is written, and the analyser says so. The finder answers
    // with the path instead of a yes, which is the stronger claim anyway: not
    // that *something* is registered under the hint, but that this file is.
    //
    // What the template then says is governed over every template at once by
    // `tests/Templates` — the vocabulary, the logic and L1's refusal of an
    // English sentence. None of that is repeated here, and none of it fires if
    // the name never reaches the file.
    expect(View::getFinder()->find('operator::no-stack-yet'))
        ->toEndWith('app-modules/operator/resources/views/no-stack-yet.blade.php');
});

it('draws the frame the surface registered, by name', function (): void {
    // A screen that answers with a view is one `tests/Templates` can read; one
    // that assembled an element tree in PHP would be invisible to every rule in
    // that suite — the vocabulary check, the screen-reader check and L1's
    // refusal of an English sentence all work over the text of a template.
    //
    // Here rather than in the operator module's own suite, which is deliberate
    // and worth knowing: `tests/Pest.php` boots the application for `Feature`,
    // `Templates` and `Contract` only. A capability is pure and needs no
    // application; a screen is the opposite of that.
    // Constructed here rather than resolved, because a test body is a closure
    // and `make()` raises a checked exception — which is the same rule that
    // put the container behind a method in the composition root.
    //
    // A fake rather than the adapter: what this asserts is the frame's name,
    // and a screen that had to reach a keychain to answer it would be a
    // different test failing for a different reason.
    expect(new NoStackYet(StacksInMemory::working())->render()->name())
        ->toBe('operator::no-stack-yet');
});
