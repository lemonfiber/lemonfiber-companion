<?php

declare(strict_types=1);

namespace Lemonfiber\Companion\PHPStan\Rules;

use function array_values;
use function in_array;

use Modules\Kernel\Api\ComparedInConstantTime;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\BinaryOp\NotIdentical;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

use function sprintf;

/**
 * S1 — a value marked {@see ComparedInConstantTime} is compared with `hash_equals`.
 *
 * `===` on a string stops at the first byte that differs, so how long it takes
 * says how much of a secret a guess got right. A session token, and every
 * identity compared beside one, is marked on the property that holds it, and
 * an `===` or `!==` with that property on either side, in the class that
 * declares it, is refused.
 *
 * @implements Rule<BinaryOp>
 */
final class CompareInConstantTimeRule implements Rule
{
    public function getNodeType(): string
    {
        return BinaryOp::class;
    }

    /**
     * The marked properties a comparison reads on either side, where it is an `===` or a `!==`.
     *
     * @param list<string> $marked the names of the properties marked in the class the comparison is in
     *
     * @return list<string>
     */
    public static function comparedByIdentity(BinaryOp $comparison, array $marked): array
    {
        if (! $comparison instanceof Identical && ! $comparison instanceof NotIdentical) {
            return [];
        }

        $read = [];

        foreach ([$comparison->left, $comparison->right] as $side) {
            $name = self::propertyRead($side);
            $read = $name !== null && in_array($name, $marked, strict: true) ? [...$read, $name => $name] : $read;
        }

        return array_values($read);
    }

    /** @return list<IdentifierRuleError> */
    public function processNode(Node $node, Scope $scope): array
    {
        $class = $scope->getClassReflection();

        if (! $class instanceof ClassReflection) {
            return [];
        }

        $errors = [];

        foreach (self::comparedByIdentity($node, self::markedIn($class)) as $property) {
            $errors[] = RuleErrorBuilder::message(sprintf(
                'S1 — `$%s` is compared in constant time. `===` and `!==` stop at the first byte that differs, which tells a guesser how much of it they had right. Compare it with hash_equals() (S1).',
                $property,
            ))
                ->identifier('lemonfiber.comparedInConstantTime')
                ->line($node->getStartLine())
                ->build();
        }

        return $errors;
    }

    /** @return list<string> */
    private static function markedIn(ClassReflection $class): array
    {
        $marked = [];

        foreach ($class->getNativeReflection()->getProperties() as $property) {
            $marked = $property->getAttributes(ComparedInConstantTime::class) === [] ? $marked : [...$marked, $property->getName()];
        }

        return $marked;
    }

    private static function propertyRead(Expr $side): ?string
    {
        return $side instanceof PropertyFetch && $side->name instanceof Identifier ? $side->name->toString() : null;
    }
}
