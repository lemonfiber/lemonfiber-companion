<?php

declare(strict_types=1);

namespace Lemonfiber\Companion\PHPStan\Rules;

use function array_filter;
use function array_values;
use function count;

use PhpParser\Node;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

use function sprintf;
use function str_contains;

/**
 * H8 — a method leaves by at most three doors.
 *
 * A method with four returns is usually two methods: one deciding which
 * situation it is in and one answering for it. `WhatTheCodeSaysSoFar::read()`
 * was the example — blank, parsed, expired, unreadable — and splitting it left
 * a method that asks whether anything was typed and a method that says what it
 * turned out to be, each of which reads on its own.
 *
 * **It is here because SonarCloud found it first.** `S1142` reported it on a
 * pull request, which is after the work is finished and in a place the author
 * has already stopped looking — the same argument `D6` makes for having a local
 * rule beside the one SonarCloud runs. A rule that only fails in review is a
 * rule people meet too late to act on cheaply.
 *
 * Counted per function rather than per class, and closures count as their own:
 * a closure with four returns is the same method in a smaller wrapper, and the
 * returns inside one are not the enclosing method's to answer for.
 *
 * Tests are exempt. A test body is a sequence of statements and a dataset
 * closure is a table; neither is a method somebody has to follow, and the cure
 * would be extracting helpers nobody reads.
 *
 * @implements Rule<FunctionLike>
 */
final class NoManyReturnsRule implements Rule
{
    /**
     * How many doors a method may have.
     *
     * Three, which is SonarCloud's own default and is chosen rather than
     * inherited: a guard, a refusal and an answer is a shape that reads, and
     * the fourth is where a reader starts holding state in their head.
     */
    private const int THE_MOST_DOORS = 3;

    public function getNodeType(): string
    {
        return FunctionLike::class;
    }

    /** @return list<IdentifierRuleError> */
    public function processNode(Node $node, Scope $scope): array
    {
        if (str_contains($scope->getFile(), '/tests/')) {
            return [];
        }

        $returns = count($this->returnsDirectlyIn($node));

        if ($returns <= self::THE_MOST_DOORS) {
            return [];
        }

        return [
            RuleErrorBuilder::message(sprintf(
                'H8 — this %s returns from %d places, which is more than %d. A method with '
                . 'that many doors is usually two: one deciding which situation it is in, '
                . 'and one answering for it. Split it where the question changes — the '
                . 'reader then follows one thing at a time, and each half can be named for '
                . 'what it decides (H8, H3).',
                $this->what($node),
                $returns,
                self::THE_MOST_DOORS,
            ))
                ->identifier('lemonfiber.manyReturns')
                ->build(),
        ];
    }

    /**
     * The returns this function owns, without the ones belonging to a closure
     * inside it.
     *
     * A closure is visited as a node in its own right, so counting through one
     * would charge the enclosing method for returns it does not have and charge
     * the closure for them a second time.
     *
     * @return list<Return_>
     */
    private function returnsDirectlyIn(FunctionLike $node): array
    {
        $found = [];

        foreach (new NodeFinder()->findInstanceOf((array) $node->getStmts(), Return_::class) as $return) {
            $found[] = $return;
        }

        foreach (new NodeFinder()->findInstanceOf((array) $node->getStmts(), FunctionLike::class) as $nested) {
            foreach (new NodeFinder()->findInstanceOf((array) $nested->getStmts(), Return_::class) as $theirs) {
                $found = array_filter($found, static fn(Return_ $ours): bool => $ours !== $theirs);
            }
        }

        return array_values($found);
    }

    /** What to call this in the message, so a reader knows where to look. */
    private function what(FunctionLike $node): string
    {
        return match (true) {
            $node instanceof ClassMethod => 'method',
            $node instanceof Function_ => 'function',
            $node instanceof Closure, $node instanceof ArrowFunction => 'closure',
            default => 'function',
        };
    }
}
