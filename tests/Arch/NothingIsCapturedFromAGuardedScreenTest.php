<?php

declare(strict_types=1);

use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Session;
use Native\Mobile\Edge\NativeComponent;
use Tests\Support\Module;

// A screen showing material worth stealing says so.
//
// Two captures happen to a screen rather than being performed on it: the
// snapshot the platform takes when the app goes to the background, which then
// sits in the task switcher for anyone holding the phone, and a screen
// recording, which may have been started by something else entirely. Neither
// asks, and neither is visible from inside the app.
//
// The protection is native — `FLAG_SECURE` on Android, a privacy overlay on
// iOS — and reaches PHP only through a plugin. What cannot be delegated is
// *which screens need it*, because that is a fact about this application. So
// the declaration lives here, in a form a lifecycle hook can read with one
// reflection pass, and the rule is that the declaration is never forgotten.
//
// The list is written out rather than inferred, for the reason S3 and N4-R12
// are: a rule that guesses which types are sensitive is a rule that silently
// stops covering a type somebody adds next month. These three are named in the
// requirement — a session token, and the material a pairing carried.
//
// `IdempotencyKey` and `Code` are deliberately absent. A key makes a retry safe
// and reveals nothing about the operator; a code is a stable identifier the
// server publishes. Adding them would make the rule fire on screens it has no
// business firing on, and a rule that cries wolf is turned off.

it('N4-R18 — a screen holding a secret is excluded from capture', function (): void {
    $offenders = [];

    foreach (Module::all() as $module) {
        foreach ($module->classNames() as $name) {
            $class = new ReflectionClass($name);

            if (! $class->isSubclassOf(NativeComponent::class)) {
                continue;
            }

            if (! holdsSomethingWorthStealing($class) || $class->getAttributes(Concealed::class) !== []) {
                continue;
            }

            $offenders[] = $name;
        }
    }

    sort($offenders);

    expect($offenders)->toBe([], sprintf(
        "These screens hold a secret and do not say they must not be captured:\n  %s\n\n"
        . 'The platform snapshots the app when it goes to the background and leaves that '
        . 'frame in the task switcher, and a screen recording may already be running. '
        . "Neither asks the app first, so neither can be prevented by this screen.\n"
        . 'Add #[Concealed] above the class. The attribute is what the native layer reads '
        . 'to block capture while the screen is up — a screen that genuinely shows none '
        . 'of a session, a fingerprint or a nonce holds none of those types and is never '
        . 'asked (N4-R18).',
        implode("\n  ", $offenders),
    ));
});

/**
 * Whether a screen is handed one of the three things `N4-R18` names.
 *
 * Named rather than inferred, and read off the constructor for the same reason
 * `N1-R39` is: a screen is given what it shows, and what it does with the value
 * afterwards is beyond what any rule over a signature can see.
 *
 * @param ReflectionClass<NativeComponent> $class
 */
function holdsSomethingWorthStealing(ReflectionClass $class): bool
{
    $worthStealing = [
        Fingerprint::class,
        Nonce::class,
        Session::class,
    ];

    foreach ($class->getConstructor()?->getParameters() ?? [] as $parameter) {
        $type = $parameter->getType();

        if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
            continue;
        }

        if (in_array($type->getName(), $worthStealing, strict: true)) {
            return true;
        }
    }

    return false;
}
