<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function count;
use function implode;
use function sprintf;

/**
 * What the app can tell somebody who is trying to help.
 *
 * `N4-R13` says a diagnostic report is assembled for the operator to send, and
 * must not be transmitted by the app. Both halves are kept here by what this
 * class *is* rather than by what it promises.
 *
 * **It cannot transmit**, because it holds nothing that could: no client, no
 * port, no `send()`. It answers with an {@see Assembled}, which is text and a
 * filename, and the operator hands that to the platform's share sheet. The
 * difference between this and a crash reporter is entirely who pressed the
 * button, and `N4-R12` refuses the other one — so the two must not be one line
 * apart.
 *
 * **It cannot carry a secret**, because its signature will not accept one. A
 * report is useful in proportion to what it contains, which is exactly the
 * pressure that puts a session token in a support bundle — so the refusal is in
 * the parameter list, where it is checked by the engine rather than remembered
 * by whoever adds the next field. There is no parameter here that a `Session`,
 * a `Fingerprint`, a `Nonce` or an `Address` fits through, and
 * {@see \Tests\Arch\ADiagnosticReportSaysNothingSecretTest} fails if one is
 * added.
 *
 * `Session`'s own docblock names this as the thing that closes the hole it
 * cannot close itself: `print_r` and `var_export` read private properties
 * directly, and no method intercepts them. What seals it is a report assembler
 * that never walks a `Session` at all, and this is that assembler.
 */
final readonly class Diagnostics
{
    /**
     * What a report is called when the operator shares it.
     *
     * Fixed rather than timestamped, and that is deliberate: a name carrying the
     * moment it was made is a name that differs between two reports about the
     * same fault, which is how a support thread ends up with four attachments
     * and no idea which is current.
     */
    private const string NAMED = 'lemonfiber-diagnostics.txt';

    /**
     * Assemble what can be said without saying anything private.
     *
     * Variadic `StackId` rather than a list of `Stack`, and the distinction is
     * the point: a `Stack` carries an {@see Address}, which is where on somebody's
     * network a machine lives. An id is a name the operator chose and the server
     * echoes, and it is what somebody helping actually needs — "which of your
     * three" rather than "at which address".
     */
    public static function assemble(Shape $shape, WireVersion $wire, StackId ...$stacks): Assembled
    {
        $said = [
            sprintf('lemonfiber companion, state shape %d', $shape->value),
            sprintf('api version %s', $wire->value),
            sprintf('stacks configured: %d', count($stacks)),
        ];

        foreach ($stacks as $stack) {
            $said[] = sprintf('  %s', $stack->stored());
        }

        $said[] = '';
        $said[] = 'This report holds no credential, no address and no reading from a stack.';

        return Assembled::as(self::NAMED, implode("\n", $said));
    }
}
