<?php

declare(strict_types=1);

use Lemonfiber\Native\Handover;
use Lemonfiber\Native\Offered;
use Lemonfiber\Native\WhyNothingWasHandedOver;
use Modules\Device\Api\PlatformShare;
use Modules\Kernel\Api\ABundleFile;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AnInvitationToPassOn;
use Modules\Kernel\Api\AskingForHelp;
use Modules\Kernel\Api\Assembled;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Sharing;
use Modules\Kernel\Api\Stack;
use Tests\Support\Manifests;

// The app sends no analytics, telemetry or crash reports to a third
// party.
//
// **This is about transmission, not about knowing what happened.** Logging,
// debugging and diagnostics are untouched and wanted: a log written to the
// device, `Log::debug`, a stack trace in a console, a report assembled for an
// operator to read — all of that is how a fault gets understood, and none of it
// is what this rule is about. What the rule refuses is a payload leaving the
// device for somebody who is not the operator.
//
// It is a requirement kept by *not* doing something, which is the kind that
// erodes: nobody adds telemetry on purpose here, and somebody adds a package
// that carries it. Sentry, Bugsnag and Flare all arrive as a dependency plus a
// service provider, and each is an ordinary thing to install in a Laravel
// application. The first anybody would know is a crash report on a third
// party's dashboard.
//
// Read over the manifests rather than over imports, because that is where it
// enters: a package in `require` ships whether or not any line here names it,
// and Laravel's discovery boots its provider without an import anywhere.
//
// The address rule is the other half of the reason. A crash reporter's payload is a
// stack trace with local variables in it, and the local variables in this
// application are session tokens and stack addresses.

it('no package that reports to a third party is installed', function (): void {
    $found = Manifests::reportingToAThirdParty();

    sort($found);

    expect($found)->toBe([], sprintf(
        "These packages send something off the device:\n  %s\n\n"
        . 'N4-R12 refuses analytics, telemetry and crash reporting outright — not '
        . "configured off, not sampled at zero, absent.\n"
        . 'The reason is N1-R15: a crash reporter sends a stack trace with local '
        . 'variables in it, and the local variables here are session tokens and stack '
        . "addresses. A sampling rate is not a guard against that.\n"
        . 'This does not touch logging. Write to the device, log as loudly as the '
        . 'problem deserves, and assemble a diagnostic report for the operator to send '
        . 'themselves (N4-R13) — that is the supported way to get a fault off a device, '
        . 'and it is the operator who sends it.',
        implode("\n  ", $found),
    ));
});

// The other road off the device is the operator's own: the device's sharing,
// handed a report, an invitation or a support bundle, with the choice of where
// it goes left to them. What keeps that road from becoming the app's is what
// the port is handed. It takes one value per method and nothing that says
// where — no address, no stack, no session, no text or bytes a caller could
// have picked up anywhere — and its adapter holds the sheet and nothing else.

/**
 * Every parameter one interface or class declares, as `method($name: Type)`, sorted.
 *
 * @param class-string $of
 * @return list<string>
 */
function everyParameterOf(string $of, int $visibility = ReflectionMethod::IS_PUBLIC): array
{
    $taken = [];

    foreach (new ReflectionClass($of)->getMethods($visibility) as $method) {
        foreach ($method->getParameters() as $parameter) {
            $taken[] = sprintf('%s($%s: %s)', $method->getName(), $parameter->getName(), (string) $parameter->getType());
        }
    }

    sort($taken);

    return $taken;
}

it('hands the device\'s sharing a value to put in front of the operator, and never somewhere to send it', function (): void {
    expect(everyParameterOf(Sharing::class))->toBe([
        sprintf('hand($assembled: %s)', Assembled::class),
        sprintf('handOver($bundle: %s)', ABundleFile::class),
        sprintf('passOn($invitation: %s)', AnInvitationToPassOn::class),
    ], 'The device\'s sharing takes one value made for handing over. A string, an address, a stack or a '
        . 'session beside it is somewhere to send something, and that is the app sending it rather than '
        . 'the operator.');
});

it('holds nothing that says where, in anything the device\'s sharing is handed', function (): void {
    $where = [Address::class, Stack::class, Session::class];
    $holding = [];

    foreach ([Assembled::class, AnInvitationToPassOn::class, ABundleFile::class] as $handed) {
        foreach (new ReflectionClass($handed)->getProperties() as $property) {
            $held = $property->getType();

            if ($held instanceof ReflectionNamedType && in_array($held->getName(), $where, strict: true)) {
                $holding[] = sprintf('%s::$%s', $handed, $property->getName());
            }
        }
    }

    expect($holding)->toBe([]);
});

it('builds the device\'s sharing over the sheet and over nothing that could send', function (): void {
    expect(everyParameterOf(PlatformShare::class, ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PRIVATE))->toBe([
        sprintf('__construct($sheet: %s)', Handover::class),
        sprintf('hand($assembled: %s)', Assembled::class),
        sprintf('handOver($bundle: %s)', ABundleFile::class),
        sprintf('handed($offered: %s)', Offered::class),
        sprintf('meaning($why: %s)', WhyNothingWasHandedOver::class),
        sprintf('passOn($invitation: %s)', AnInvitationToPassOn::class),
    ]);
});

it('fetches a support bundle without taking one to send', function (): void {
    // The bundle comes to this device over the one port that asks a stack for
    // it, and that port is handed nothing a bundle is made of: what leaves the
    // device goes through the device's sharing above, or not at all.
    $taking = array_values(array_filter(
        everyParameterOf(AskingForHelp::class),
        static fn(string $parameter): bool => str_contains($parameter, ABundleFile::class),
    ));

    expect($taking)->toBe([]);
});
