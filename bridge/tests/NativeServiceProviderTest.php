<?php

declare(strict_types=1);

use Lemonfiber\Native\Scanning;
use Lemonfiber\Native\Screen;
use Lemonfiber\Native\Storage;
use Lemonfiber\Native\Telling;

// What this plugin publishes to the application that installs it.
//
// Four bindings, and the obvious test does not hold them. Deleting any one of
// the four leaves green every test that merely asks the container for one of
// these classes, which is the shape a mutation floor exists to find — and the
// reason is worth writing down, because it is also the reason the obvious test
// does not find it.
//
// **Resolving them is not the assertion.** None of these four takes a
// constructor argument, so Laravel's container builds each one by reflection
// whether the provider bound it or not: `app(Screen::class)` answers a Screen
// on a tree with `register()` emptied out. A test that asked for one and got
// one would have passed over four deleted lines.
//
// So what is asserted is that the container was *told* — `bound()` is true only
// where something bound it — and that is also the thing worth promising. A
// binding is this package saying which of its classes an application may ask
// for by name; auto-wiring is the container guessing, and it stops guessing
// correctly the day one of these takes an argument.

it('binds every face it publishes, rather than leaving them to be guessed at', function (): void {
    $unbound = [];

    foreach ([Screen::class, Telling::class, Scanning::class, Storage::class] as $face) {
        if (! app()->bound($face)) {
            $unbound[] = $face;
        }
    }

    expect($unbound)->toBe([], sprintf(
        "These are not bound, so an application asking for one is relying on the "
        . "container to guess:\n  %s\n\n"
        . 'Bind it in `NativeServiceProvider::register()`. Reflection answers the same '
        . 'object today and stops the day the class takes an argument, which is a failure '
        . 'at launch rather than here.',
        implode("\n  ", $unbound),
    ));
});

it('hands out a new one each time, because each is a handle to something outside', function (): void {
    // The other half of what `register()` says it does. A singleton here would
    // be a handle taken at launch and answered with for the life of a
    // long-running application, which is the state the device was in then
    // rather than the state it is in now.
    expect(app(Screen::class))->not->toBe(app(Screen::class))
        ->and(app(Telling::class))->not->toBe(app(Telling::class))
        ->and(app(Scanning::class))->not->toBe(app(Scanning::class))
        ->and(app(Storage::class))->not->toBe(app(Storage::class));
});
