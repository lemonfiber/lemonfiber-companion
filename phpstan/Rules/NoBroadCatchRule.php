<?php

declare(strict_types=1);

namespace Lemonfiber\Companion\PHPStan\Rules;

use function in_array;

use PhpParser\Node;
use PhpParser\Node\Expr\Throw_;
use PhpParser\Node\Stmt\Catch_;
use PhpParser\Node\Stmt\Expression;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

use function sprintf;
use function strtolower;

/**
 * C6 — a catch says what it caught and does something about it.
 *
 * Two shapes are refused. An empty body swallows the thing that was about to
 * explain a failure, and the next symptom appears somewhere unrelated. A catch
 * of `Throwable` or `Exception` that does not rethrow claims to handle
 * everything, which includes the failures nobody anticipated — a typo'd method
 * name arrives as an Error and is quietly absorbed along with the timeout the
 * author had in mind.
 *
 * It usually also means C1 is being worked around. A refusal crossing a module
 * boundary is an `Outcome` the caller has to open, and a broad catch is what
 * appears when something threw instead.
 *
 * @implements Rule<Catch_>
 */
final class NoBroadCatchRule implements Rule
{
    private const array EVERYTHING = ['throwable', 'exception', '\\throwable', '\\exception'];

    public function getNodeType(): string
    {
        return Catch_::class;
    }

    /** @return list<IdentifierRuleError> */
    public function processNode(Node $node, Scope $scope): array
    {
        if ($node->stmts === []) {
            return [$this->error($node, 'swallows what it caught')];
        }

        foreach ($node->types as $type) {
            if (in_array(strtolower($type->toString()), self::EVERYTHING, strict: true) && ! $this->rethrows($node)) {
                return [$this->error($node, sprintf('catches %s without rethrowing', $type->toString()))];
            }
        }

        return [];
    }

    /**
     * Whether the body throws again.
     *
     * `throw` is an expression in this parser, so it arrives wrapped in an
     * expression statement rather than as a statement of its own.
     */
    private function rethrows(Catch_ $node): bool
    {
        foreach ($node->stmts as $statement) {
            if ($statement instanceof Expression && $statement->expr instanceof Throw_) {
                return true;
            }
        }

        return false;
    }

    private function error(Catch_ $node, string $what): IdentifierRuleError
    {
        return RuleErrorBuilder::message(sprintf(
            'C6 — this catch %s. A body that does nothing discards the one thing that was '
            . 'about to explain the failure, and the next symptom turns up somewhere '
            . 'unrelated; catching Throwable absorbs the failures nobody anticipated '
            . 'alongside the one that was expected, so a misspelled method name is handled '
            . 'as though it were a timeout. Name the exception this code knows how to '
            . 'answer. Across a module boundary the answer is usually an Outcome rather '
            . 'than a catch at all (C6, C1, C3).',
            $what,
        ))
            ->identifier('lemonfiber.broadCatch')
            ->line($node->getStartLine())
            ->build();
    }
}
