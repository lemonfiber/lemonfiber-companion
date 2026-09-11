<?php

declare(strict_types=1);

namespace Lemonfiber\Companion\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\Print_;
use PhpParser\Node\Stmt\Echo_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

use function str_contains;

/**
 * Q4 — nothing writes to the output stream.
 *
 * The runtime publishes a binary element tree. A stray `echo` lands in the
 * middle of it, and what the operator gets is not a stack trace or a blank
 * screen but a malformed frame — diagnosed on a device, where there is no
 * console to read and the only evidence is that the screen is wrong.
 *
 * Reporting something is what the Log port is for, and showing something is
 * what a view model is for. Neither of them writes to standard output.
 *
 * @implements Rule<Node>
 */
final class NoOutputRule implements Rule
{
    public function getNodeType(): string
    {
        return Node::class;
    }

    /** @return list<IdentifierRuleError> */
    public function processNode(Node $node, Scope $scope): array
    {
        if (! $node instanceof Echo_ && ! $node instanceof Print_) {
            return [];
        }

        // The rule tables print a count of their own to standard output, which
        // is the one place a stream is the right answer: nobody is rendering.
        if (str_contains($scope->getFile(), '/tests/')) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                'Q4 — the runtime publishes a binary element tree, and this writes into the '
                . 'middle of it. The result is a malformed frame rather than an error: '
                . 'there is no console on a phone, and the only evidence is that the screen '
                . 'is wrong. Report it through the Log port, or put it in the view model '
                . 'the screen already reads (Q4).',
            )
                ->identifier('lemonfiber.output')
                ->line($node->getStartLine())
                ->build(),
        ];
    }
}
