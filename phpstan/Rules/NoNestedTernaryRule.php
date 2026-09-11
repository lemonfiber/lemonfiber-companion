<?php

declare(strict_types=1);

namespace Lemonfiber\Companion\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\BinaryOp\Coalesce;
use PhpParser\Node\Expr\Ternary;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

use function str_contains;

/**
 * C9 — one condition per expression, and no `??` standing in for a shape.
 *
 * A ternary inside a ternary has to be read twice: once to find the boundaries
 * and again to work out which branch belongs to which condition. PHP's own
 * associativity for the un-parenthesised form is a documented trap. `match` is
 * the same thing with every branch on its own line.
 *
 * `$payload['key'] ?? null` is the other half. It reads as a default and is
 * really an admission that nobody knows whether the key is there — which is the
 * array D1 is separately removing from the module boundaries. A typed value
 * knows its own fields, and the check disappears rather than moving.
 *
 * @implements Rule<Node>
 */
final class NoNestedTernaryRule implements Rule
{
    public function getNodeType(): string
    {
        return Node::class;
    }

    /** @return list<IdentifierRuleError> */
    public function processNode(Node $node, Scope $scope): array
    {
        if ($node instanceof Ternary && ($node->if instanceof Ternary || $node->else instanceof Ternary)) {
            return [
                RuleErrorBuilder::message(
                    'C9 — a ternary inside a ternary is read twice: once to find where each '
                    . 'one ends and again to pair a branch with its condition, and PHP\'s '
                    . 'associativity for the un-parenthesised form is a documented trap. '
                    . 'Write a match, which puts each branch on its own line and is '
                    . 'exhaustive over an enum (C9, C5).',
                )
                    ->identifier('lemonfiber.nestedTernary')
                    ->line($node->getStartLine())
                    ->build(),
            ];
        }

        if (! $node instanceof Coalesce || ! $node->left instanceof ArrayDimFetch) {
            return [];
        }

        // The test support reads composer manifests, JUnit XML and the
        // analyser's own JSON — foreign shapes that genuinely arrive untrusted,
        // which is the one case this half of the rule points at as correct. The
        // nested-ternary half above applies everywhere.
        if (str_contains($scope->getFile(), '/tests/')) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                'C9 — `??` on an array subscript reads as a default and is really an '
                . 'admission that nobody knows whether the key is there. That is the array '
                . 'D1 is removing from the module boundaries: a value object knows its own '
                . 'fields, so the check does not move, it disappears. Where the data '
                . 'genuinely arrives untrusted — a deep link, a scanned code — parse it '
                . 'into a type at the edge and let the parse refuse it (C9, D1).',
            )
                ->identifier('lemonfiber.coalesceOnArray')
                ->line($node->getStartLine())
                ->build(),
        ];
    }
}
