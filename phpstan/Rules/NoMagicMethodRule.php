<?php

declare(strict_types=1);

namespace Lemonfiber\Companion\PHPStan\Rules;

use function in_array;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

use function sprintf;
use function strtolower;

/**
 * P1 — a member exists or it does not.
 *
 * `__get` and `__call` create members at the moment they are asked for, so the
 * analyser sees a class with none of them and every rule in this repository
 * stops applying to whatever they expose: no type, no boundary check, no
 * refusal for a name that is simply misspelled. One of these on a class is a
 * hole the size of that class's whole surface.
 *
 * `__invoke` is not on the list. It is a declared method with a signature the
 * analyser reads like any other, and the single-action commands and queries are
 * built on it (M2).
 *
 * @implements Rule<ClassMethod>
 */
final class NoMagicMethodRule implements Rule
{
    private const array HIDDEN = ['__get', '__set', '__isset', '__unset', '__call', '__callstatic'];

    public function getNodeType(): string
    {
        return ClassMethod::class;
    }

    /** @return list<IdentifierRuleError> */
    public function processNode(Node $node, Scope $scope): array
    {
        $name = strtolower($node->name->toString());

        if (! in_array($name, self::HIDDEN, strict: true)) {
            return [];
        }

        return [
            RuleErrorBuilder::message(sprintf(
                'P1 — %s() makes a member that only exists at runtime, and the analyser '
                . 'sees a class without it. Every rule here works by reading what a class '
                . 'declares, so this one method switches all of them off for everything it '
                . 'exposes — including the boundary rules, and including a misspelled name '
                . 'that would otherwise be an error. Declare the members. __invoke is '
                . 'fine: it has a signature (P1).',
                $node->name->toString(),
            ))
                ->identifier('lemonfiber.magicMethod')
                ->line($node->getStartLine())
                ->build(),
        ];
    }
}
