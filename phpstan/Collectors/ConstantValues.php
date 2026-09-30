<?php

declare(strict_types=1);

namespace Lemonfiber\Companion\PHPStan\Collectors;

use Lemonfiber\Companion\PHPStan\Rules\OneHome\Ours;
use Lemonfiber\Companion\PHPStan\Rules\OneHome\Written;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\EnumCase;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;

use function sprintf;

/**
 * D9 — the value of every class constant and backed enum case in the trees
 * {@see Ours} names, with what declares it and its line, for
 * `OneHomePerValueRule` to group.
 *
 * A value too plain to have a home is left out ({@see Written::isPlain()}), and
 * so is a constant declared as another constant or as an enum case's value,
 * which refers to that one's home.
 *
 * @implements Collector<Node, list<array{string, string, int, bool}>>
 */
final readonly class ConstantValues implements Collector
{
    public function getNodeType(): string
    {
        return Node::class;
    }

    /** @return list<array{string, string, int, bool}> the written value, the declaring constant or case, its line, and whether it is an enum case */
    public function processNode(Node $node, Scope $scope): array
    {
        if (! Ours::holds($scope->getFile()) || ! $scope->isInClass()) {
            return [];
        }

        // A trait's constant is the trait's, whichever class is using it.
        $class = $scope->isInTrait() ? $scope->getTraitReflection()->getName() : $scope->getClassReflection()->getName();
        $found = [];

        foreach ($this->valuesDeclaredBy($node) as $name => [$value, $line]) {
            $type = $scope->getType($value);

            if (! $this->refersToAHome($value) && $type->isConstantValue()->yes() && ! Written::isPlain(Written::of($type))) {
                $found[] = [Written::of($type), sprintf('%s::%s', $class, $name), $line, $node instanceof EnumCase];
            }
        }

        return $found;
    }

    /**
     * Whether a value is written as another constant, or as an enum case's
     * value, which is that constant's or that case's home referred to.
     */
    private function refersToAHome(Expr $value): bool
    {
        return $value instanceof ClassConstFetch
            || ($value instanceof PropertyFetch && $value->var instanceof ClassConstFetch);
    }

    /**
     * The values a constant declaration or an enum case declares, by name.
     *
     * @return array<string, array{Expr, int}>
     */
    private function valuesDeclaredBy(Node $node): array
    {
        $declared = [];

        if ($node instanceof ClassConst) {
            foreach ($node->consts as $constant) {
                $declared[$constant->name->toString()] = [$constant->value, $constant->getStartLine()];
            }
        }

        if ($node instanceof EnumCase && $node->expr instanceof Expr) {
            $declared[$node->name->toString()] = [$node->expr, $node->getStartLine()];
        }

        return $declared;
    }
}
