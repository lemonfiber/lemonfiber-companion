<?php

declare(strict_types=1);

namespace Lemonfiber\Companion\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * H5 — a string with a value in it is built with sprintf, not with dots.
 *
 * Concatenation interleaves the shape of the string with the values going into
 * it, so a reader assembles the result in their head to find out what it looks
 * like. `sprintf` puts the shape in one literal and the values after it, which
 * is also where a format specifier can say `%d` or `%s` and mean it.
 *
 * It matters more here than in most codebases because these strings are
 * refusal messages an operator reads on a phone, and a message whose shape is
 * spread across four operands is one nobody rereads before shipping.
 *
 * A chain of pure literals is left alone: `'a' . 'b'` is one string written
 * across two lines for width, there is no value being formatted, and Pint's
 * `no_useless_concat_operator` already has an opinion about it.
 *
 * @implements Rule<Concat>
 */
final class PreferSprintfOverConcatRule implements Rule
{
    public function getNodeType(): string
    {
        return Concat::class;
    }

    /** @return list<IdentifierRuleError> */
    public function processNode(Node $node, Scope $scope): array
    {
        if (! $this->carriesTheReport($node)) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                'H5 — build this with sprintf rather than concatenation. '
                . 'Concatenation spreads the shape of the string across the values '
                . 'going into it; sprintf keeps the shape in one literal, which is '
                . 'what a reader checks and what a translator needs.',
            )
                ->identifier('lemonfiber.preferSprintf')
                ->line($node->getStartLine())
                ->build(),
        ];
    }

    /**
     * Whether this node is the one node in its chain that should report.
     *
     * `'a' . 'b' . $c` parses as Concat(Concat('a','b'), $c), so a rule that
     * both reports at the innermost node and skips two adjacent literals
     * reports that chain nowhere — the two exemptions cancel out and the most
     * common shape of all escapes. So the report lands on the first operand
     * that is not literal text: exactly one node per chain, and only for a
     * chain that actually interleaves a value.
     */
    private function carriesTheReport(Concat $node): bool
    {
        if (! $node->left instanceof Concat) {
            // The innermost pair. Two literals are not a value being formatted.
            return ! ($node->left instanceof String_ && $node->right instanceof String_);
        }

        // Something to the left is already a value, so an inner node reported.
        if (! $this->isLiteralText($node->left)) {
            return false;
        }

        // Everything so far is literal text; this node reports only if it is
        // where the first value joins. If not, a node further right will.
        return ! $node->right instanceof String_;
    }

    /** Whether an expression is literal text all the way down. */
    private function isLiteralText(Expr $expr): bool
    {
        if ($expr instanceof String_) {
            return true;
        }

        return $expr instanceof Concat
            && $this->isLiteralText($expr->left)
            && $this->isLiteralText($expr->right);
    }
}
