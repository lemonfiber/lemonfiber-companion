<?php

declare(strict_types=1);

use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;
use Tests\Support\Module;

// F4 — a screen says how it paints (N1-R25, N1-R27, ADR-0019).
//
// A NativeComponent that reaches a port during setup cannot draw until the
// answer arrives, and the answer is coming from a machine that may be asleep on
// another network. Without `#[Lazy]` the operator gets a held frame with no
// explanation; with it they get the screen's own chrome and a placeholder while
// `mount()` runs, which is the difference between "slow" and "broken".
//
// Only the half that can be decided from a signature is checked here. Whether a
// screen's content changes while it is open is a fact about the stack, not
// about the class, so `#[Poll]` is on the review list rather than pretended at.

it('F4 — a screen that waits on a port paints something first', function (): void {
    $offenders = [];

    foreach (Module::all() as $module) {
        foreach ($module->classNames() as $name) {
            $class = new ReflectionClass($name);

            if (! $class->isSubclassOf(NativeComponent::class)) {
                continue;
            }

            if ($class->getAttributes(Lazy::class) !== []) {
                continue;
            }

            if (waitsOnAPort($class)) {
                $offenders[] = $name;
            }
        }
    }

    expect($offenders)->toBe([], sprintf(
        "These screens wait on a port and draw nothing while they do:\n  %s\n\n"
        . 'A port answers over a link to a machine that may be asleep, so the frame is '
        . 'held for as long as that takes and the operator is given no reason. #[Lazy] '
        . 'draws the screen chrome and a placeholder immediately and runs mount() behind '
        . 'it. Add the attribute, and override placeholder() where the default indicator '
        . 'is not enough (F4, N1-R25).',
        implode("\n  ", $offenders),
    ));
});

/**
 * Whether a screen is handed something it has to call before it can draw.
 *
 * The constructor is the only place to look: `NativeComponent::mount()` is
 * declared with no parameters, so a component that overrides it cannot be given
 * anything there. An interface is the signal — every port is one, and the
 * values, view models and typed collections a screen legitimately holds are
 * final readonly classes.
 *
 * @param ReflectionClass<NativeComponent> $class
 */
function waitsOnAPort(ReflectionClass $class): bool
{
    foreach ($class->getConstructor()?->getParameters() ?? [] as $parameter) {
        $type = $parameter->getType();

        if ($type instanceof ReflectionNamedType && ! $type->isBuiltin() && interface_exists($type->getName())) {
            return true;
        }
    }

    return false;
}
