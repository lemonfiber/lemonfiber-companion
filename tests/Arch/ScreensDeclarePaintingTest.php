<?php

declare(strict_types=1);

use Modules\Kernel\Api\Capabilities;
use Modules\Kernel\Api\Reading;
use Modules\Kernel\Api\Report;
use Modules\Kernel\Api\Showing;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;
use Tests\Support\Module;

// F4 — a screen says how it paints (`ADR-0019`).
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
//
// **The second clause rests on review too, and nothing here says it.**
// The requirement has two halves — a screen publishes its first frame before
// issuing the read, *and that frame is built from what the app already holds* —
// and the rule below reads the first. `#[Lazy]` says a frame is drawn early; it
// says nothing about where the frame's content came from, and no screen in this
// repository overrides `placeholder()`, so there is no declaration to read. A
// placeholder built from a second read would satisfy every gate here.
//
// It is written down rather than left implied because the two halves read as
// one rule in the spec, and a reader who has seen `F4` enforced would take the
// whole requirement as held.

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

// A screen says which stack it is showing.
//
// The app holds more than one stack and must never attribute a reading
// from one to another. Every other guard on that is in the kernel: `StackId` is
// a type, `Capabilities` carries the stack that declared it, `Stack::is()`
// compares identity rather than address. A screen is where all of that can still
// be undone in one line, by reading the current stack from somewhere shared
// instead of from what the screen was given.
//
// Shared state is the failure mode by name in the requirement, and it is the
// natural thing to write: the runtime here is persistent (I1), so a static or a
// singleton holding "the stack we are looking at" survives between screens and
// works perfectly until two screens are open on two stacks. Then one of them is
// showing the other's readings, with no error anywhere.
//
// So the rule is about the constructor: a screen that shows anything belonging
// to a stack takes a `StackId`. Checked over the signature because that is the
// only part of "where did this come from" that is visible to a rule at all —
// what a screen does with the id afterwards is F2 and review.

it('N1-R39 — a screen that shows a stack\'s data is told which stack', function (): void {
    $offenders = [];

    foreach (Module::all() as $module) {
        foreach ($module->classNames() as $name) {
            $class = new ReflectionClass($name);

            if (! $class->isSubclassOf(NativeComponent::class)) {
                continue;
            }

            if (! showsSomethingOfAStacks($class) || isToldWhichStack($class)) {
                continue;
            }

            $offenders[] = $name;
        }
    }

    sort($offenders);

    expect($offenders)->toBe([], sprintf(
        "These screens show a stack's data without being told which stack:\n  %s\n\n"
        . 'The app holds more than one (N1-R11), and a screen that reads the current '
        . 'stack from shared state works until two are open at once — then one shows the '
        . "other's readings, with no error anywhere. The runtime is persistent (I1), so "
        . "that shared value survives between screens rather than being rebuilt.\n"
        . 'Take a `StackId` in the constructor and carry it. A screen that genuinely '
        . 'belongs to no stack — pairing, a settings page — holds none of these types '
        . 'and is not asked (N1-R39).',
        implode("\n  ", $offenders),
    ));
});

/**
 * Whether a screen is handed anything that belongs to one stack in particular.
 *
 * Named rather than inferred: these are the kernel types whose value is a fact
 * about one machine, so holding one without saying which machine is the mistake
 * the rule is about. A screen holding none of them has nothing to misattribute.
 *
 * @param ReflectionClass<NativeComponent> $class
 */
function showsSomethingOfAStacks(ReflectionClass $class): bool
{
    $ofOneStack = [
        Capabilities::class,
        Reading::class,
        Report::class,
        Showing::class,
        Stack::class,
    ];

    foreach ($class->getConstructor()?->getParameters() ?? [] as $parameter) {
        $type = $parameter->getType();

        if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
            continue;
        }

        if (in_array($type->getName(), $ofOneStack, strict: true)) {
            return true;
        }
    }

    return false;
}

/**
 * Whether the screen was told which stack, by being given the identity itself.
 *
 * A `Stack` counts: it carries its own `StackId`, so a screen holding one has
 * been told. What does not count is anything the screen could have fetched for
 * itself, which is the whole of what the requirement refuses.
 *
 * @param ReflectionClass<NativeComponent> $class
 */
function isToldWhichStack(ReflectionClass $class): bool
{
    foreach ($class->getConstructor()?->getParameters() ?? [] as $parameter) {
        $type = $parameter->getType();

        if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
            continue;
        }

        if (in_array($type->getName(), [StackId::class, Stack::class], strict: true)) {
            return true;
        }
    }

    return false;
}
