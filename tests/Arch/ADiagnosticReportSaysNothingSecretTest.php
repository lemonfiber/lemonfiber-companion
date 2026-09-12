<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Assembled;
use Modules\Kernel\Api\Diagnostics;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Reading;
use Modules\Kernel\Api\Session;

// N4-R13 — a report the operator sends, and the app does not.
//
// Two clauses, and each is kept by a different absence.
//
// The app must not transmit it: `Diagnostics` holds nothing that could, and
// `Assembled` is text and a filename. This asserts the absence, because the
// pressure to add a `send()` is real and reasonable — somebody will want the
// report to go straight to support, and the difference between that and a crash
// reporter is only who pressed the button. `N4-R12` refuses the other one.
//
// The report must not carry a secret. A report is useful in proportion to what
// it holds, which is exactly the pressure that puts a session token in a support
// bundle — so the refusal lives in the parameter list, where the engine checks
// it rather than a reviewer remembering to.

/** What a diagnostic report must never be handed. */
const NEVER_IN_A_REPORT = [
    Session::class,
    Fingerprint::class,
    Nonce::class,
    Address::class,
    Reading::class,
];

it('N4-R13 — the assembler will not accept anything private', function (): void {
    $accepted = [];

    foreach (new ReflectionClass(Diagnostics::class)->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
        foreach ($method->getParameters() as $parameter) {
            $type = $parameter->getType();

            if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
                continue;
            }

            if (in_array($type->getName(), NEVER_IN_A_REPORT, strict: true)) {
                $accepted[] = sprintf('%s($%s: %s)', $method->getName(), $parameter->getName(), $type->getName());
            }
        }
    }

    sort($accepted);

    expect($accepted)->toBe([], sprintf(
        "A diagnostic report is being handed something private:\n  %s\n\n"
        . 'A report is useful in proportion to what it holds, which is exactly the '
        . 'pressure that puts a session token in a support bundle. A credential, a '
        . "pairing fingerprint, an address on somebody's network and a reading from "
        . "their machine are none of them a thing a stranger helping needs.\n"
        . 'Pass the identity instead — a `StackId` is the name the operator chose, and '
        . 'answers "which of your three" without answering "at which address" (N4-R13).',
        implode("\n  ", $accepted),
    ));
});

it('N4-R13 — nothing about a report can send it', function (): void {
    // The absence that keeps the second clause. Asserted rather than trusted:
    // a `send()` here would be a reasonable-looking convenience, and it is the
    // line that turns an operator-sent report into telemetry.
    expect(get_class_methods(Diagnostics::class))->toBe(['assemble'])
        ->and(get_class_methods(Assembled::class))->toBe(['as', 'named', 'text']);
});

it('N4-R13 — an assembled report holds no constructor a caller could fill', function (): void {
    // `Assembled::as()` is the only maker, and `Diagnostics` is the only thing
    // that knows what belongs in a report. PHP has no package visibility, so
    // this is convention — but a private constructor is what stops a caller
    // assembling one out of whatever it happens to be holding.
    expect(new ReflectionClass(Assembled::class)->getConstructor()?->isPrivate())->toBeTrue();
});
