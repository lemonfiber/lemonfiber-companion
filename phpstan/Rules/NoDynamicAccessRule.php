<?php

declare(strict_types=1);

namespace Lemonfiber\Companion\PHPStan\Rules;

use function is_string;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

use function sprintf;

/**
 * P2 — a name in this codebase is written down.
 *
 * `new $class`, `$object->$property` and `$$name` put the thing being reached
 * for beyond the analyser and beyond every rule that reads imports. A dynamic
 * class name is the sharp case: the module boundary rules work by looking at
 * what a file names, so a class chosen at runtime crosses any boundary it likes
 * and appears in no graph.
 *
 * @implements Rule<Expr>
 */
final class NoDynamicAccessRule implements Rule
{
    public function getNodeType(): string
    {
        return Expr::class;
    }

    /** @return list<IdentifierRuleError> */
    public function processNode(Node $node, Scope $scope): array
    {
        $what = $this->dynamicPart($node);

        if ($what === null) {
            return [];
        }

        return [
            RuleErrorBuilder::message(sprintf(
                'P2 — %s is chosen at runtime, so nothing here can read it. The module '
                . 'boundary rules work by looking at the names a file writes down: a class '
                . 'or a member reached through a variable crosses whatever boundary it '
                . 'likes and appears in no graph. Where the choice is real, make it a '
                . 'match over an enum, which is a set the analyser can see and D4 already '
                . 'requires (P2, E1, D4).',
                $what,
            ))
                ->identifier('lemonfiber.dynamicAccess')
                ->line($node->getStartLine())
                ->build(),
        ];
    }

    private function dynamicPart(Node $node): ?string
    {
        if ($node instanceof Variable && ! is_string($node->name)) {
            return 'this variable variable';
        }

        if ($node instanceof New_ && ! $node->class instanceof Name && ! $node->class instanceof Node\Stmt\Class_) {
            return 'this class name';
        }

        if ($node instanceof StaticCall && ! $node->class instanceof Name) {
            return 'this class name';
        }

        return $node instanceof PropertyFetch && ! $node->name instanceof Identifier
            ? 'this property name'
            : null;
    }
}
