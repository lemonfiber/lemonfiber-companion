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

use function str_contains;

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
 * **A chain of pure literals is refused too, in the code that ships**, and it
 * is the shape that matters most rather than an edge of the rule. Pint's
 * `no_useless_concat_operator` collapses a join written on one line and leaves
 * a wrapped one alone, so nothing else in this toolchain holds it. Meanwhile
 * every `.` between two literals is three mutants — drop the left, drop the
 * right, swap them — and each survives unless a test asserts the whole sentence
 * word for word. A message asserted by the phrase that carries its meaning,
 * which is the only kind of assertion that survives somebody improving the
 * wording, kills none of them.
 *
 * So a message is one literal. Nothing to drop, nothing to swap, and the
 * sentence is readable in the one place somebody would go to change it. It is
 * also why a `private const` holding the sentence is not the fix: the constant
 * has nothing to mutate, but it puts the message somewhere other than the
 * method that raises it, and the reader who wanted to know what a refusal says
 * now follows a name to find out.
 *
 * **Everywhere but a test.** A test asserts against long explanatory strings
 * constantly and nothing mutates a test, so the same refusal there would be six
 * hundred edits buying nothing — the same line `H8` draws, drawn the same way.
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
            return $this->joinsTwoLiterals($node) && ! $this->isATest($scope->getFile())
                ? [$this->sayItIsOneMessage($node)]
                : [];
        }

        return [
            RuleErrorBuilder::message(
                'H5 — build this with sprintf rather than concatenation. Concatenation spreads the shape of the string across the values going into it; sprintf keeps the shape in one literal, which is what a reader checks and what a translator needs.',
            )
                ->identifier('lemonfiber.preferSprintf')
                ->line($node->getStartLine())
                ->build(),
        ];
    }

    /**
     * Two literals joined, which is a message written in pieces.
     *
     * The innermost pair of a chain and only that, so `'a' . 'b' . 'c'` is one
     * report rather than two: concatenation is left-associative, which makes
     * the node with no `Concat` to its left the single place to speak.
     */
    private function joinsTwoLiterals(Concat $node): bool
    {
        return ! $node->left instanceof Concat
            && $node->left instanceof String_
            && $node->right instanceof String_;
    }

    /**
     * Whether this file is one nothing mutates.
     *
     * A test asserts against long explanatory strings constantly and no mutant
     * is ever made from one, so the same refusal there would be six hundred
     * edits buying nothing. The same line `H8` draws, and drawn the same way.
     */
    private function isATest(string $file): bool
    {
        return str_contains($file, '/tests/');
    }

    /** @return IdentifierRuleError */
    private function sayItIsOneMessage(Concat $node): IdentifierRuleError
    {
        return RuleErrorBuilder::message(
            'H5 — write this as one literal rather than two joined by a dot. Every join between two literals is three mutants — drop the left, drop the right, swap them — and each survives unless a test asserts the whole sentence word for word. A constant holding the sentence is not the fix either: it puts the message somewhere other than the method that raises it.',
        )
            ->identifier('lemonfiber.oneLiteral')
            ->line($node->getStartLine())
            ->build();
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
