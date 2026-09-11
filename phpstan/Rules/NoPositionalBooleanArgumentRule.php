<?php

declare(strict_types=1);

namespace Lemonfiber\Companion\PHPStan\Rules;

use function in_array;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\ConstFetch;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

use function strtolower;

/**
 * D5 — a bare `true` at a call site says nothing.
 *
 * `$this->render($stack, true)` tells a reader nothing about what is true, and
 * finding out means opening another file. A boolean parameter is also, almost
 * always, two behaviours sharing one name — the argument is not data, it is a
 * branch, and the branch belongs in the signature.
 *
 * So there are two cures and the rule accepts either. Name the argument, and
 * the call site says what it means: `render($stack, compact: true)`. Or split
 * the method, and the branch stops being a runtime decision at all.
 *
 * The rule looks at the call rather than the declaration on purpose. A `bool`
 * parameter is not always ours to remove — it arrives from a framework
 * interface, or from a PHP function whose signature predates named arguments
 * by a decade — but naming it at the call site is always available, and the
 * call site is the only place the confusion actually happens.
 *
 * @implements Rule<Arg>
 */
final class NoPositionalBooleanArgumentRule implements Rule
{
    public function getNodeType(): string
    {
        return Arg::class;
    }

    /** @return list<IdentifierRuleError> */
    public function processNode(Node $node, Scope $scope): array
    {
        if ($node->name instanceof Node\Identifier) {
            return [];  // named, and therefore readable
        }

        if (! $node->value instanceof ConstFetch) {
            return [];
        }

        if (! in_array(strtolower($node->value->name->toString()), ['true', 'false'], true)) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                'D5 — name this argument or split the method. A bare true or false at a '
                . 'call site says nothing about what is true, so a reader has to open '
                . 'another file to find out, and a boolean parameter is usually two '
                . 'behaviours sharing one name. Write it as a named argument — '
                . 'strict: true — or give each behaviour its own method (D5).',
            )
                ->identifier('lemonfiber.noPositionalBoolean')
                ->line($node->getStartLine())
                ->build(),
        ];
    }
}
