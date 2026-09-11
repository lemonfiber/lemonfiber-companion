<?php

declare(strict_types=1);

namespace Lemonfiber\Companion\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Q3 — `static::` describes a subclass that cannot exist.
 *
 * Every class here is final, so late static binding resolves to the class it is
 * written in, every time, forever. It is inheritance machinery in a codebase
 * with no chain to serve — and it reads as though one exists, which sends the
 * next person looking for the subclass that changes the behaviour. `self::`
 * says the same thing and says it accurately.
 *
 * @implements Rule<Node>
 */
final class NoLateStaticBindingRule implements Rule
{
    public function getNodeType(): string
    {
        return Node::class;
    }

    /** @return list<IdentifierRuleError> */
    public function processNode(Node $node, Scope $scope): array
    {
        if (! $this->bindsLate($node)) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                'Q3 — `static` resolves to the class it is written in, because every class '
                . 'here is final and there is no subclass for it to find. What it does do '
                . 'is tell the next reader that one exists, and send them looking. Write '
                . '`self` (Q3).',
            )
                ->identifier('lemonfiber.lateStaticBinding')
                ->line($node->getStartLine())
                ->build(),
        ];
    }

    private function bindsLate(Node $node): bool
    {
        $class = match (true) {
            $node instanceof StaticCall, $node instanceof ClassConstFetch,
            $node instanceof StaticPropertyFetch, $node instanceof New_ => $node->class,
            default => null,
        };

        return $class instanceof Name && $class->toLowerString() === 'static';
    }
}
