<?php

declare(strict_types=1);

namespace Lemonfiber\Companion\PHPStan\Rules;

use function in_array;
use function is_array;

use PhpParser\Node;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Scalar\Float_;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

use function sprintf;
use function str_contains;

/**
 * D6 — a number in a method body has a name.
 *
 * `30` in a timeout, `86400` in a staleness check and `3` in a retry budget are
 * the three shapes this catches, and all three are decisions the operator can
 * feel. A decision that lives as a literal cannot be found by searching for
 * what it means, cannot be changed in one place, and cannot be read at the call
 * site — `$this->retry($finding, 3)` and `$this->retry($finding, 30)` look
 * equally plausible.
 *
 * Only method bodies are read. A class constant and an enum case are
 * declarations rather than bodies, so the cure is exempt by construction: move
 * the number out of the method, give it the name the method was implying, and
 * the rule stops reporting. Tests keep their numbers, because a fixture's `7`
 * is the test saying seven.
 *
 * 0, 1 and 2 are permitted: they mean empty, one, and a pair, which is what the
 * names would say. An array index is permitted for the same reason — `$parts[1]`
 * is position, not quantity.
 *
 * @implements Rule<ClassMethod>
 */
final class NoMagicNumberRule implements Rule
{
    /** Numbers whose name would be the number. */
    private const array PLAIN = [0, 1, 2];

    public function getNodeType(): string
    {
        return ClassMethod::class;
    }

    /** @return list<IdentifierRuleError> */
    public function processNode(Node $node, Scope $scope): array
    {
        if (str_contains($scope->getFile(), '/tests/')) {
            return [];
        }

        $found = [];

        foreach ($this->literalsIn($node->stmts ?? []) as $literal) {
            $found[] = RuleErrorBuilder::message(sprintf(
                'D6 — give %s a name. A number written into a method body is a decision '
                . 'nobody can search for: it cannot be found by what it means, cannot be '
                . 'changed in one place, and says nothing at the call site. Declare it as '
                . 'a class constant or an enum case, which is also where a comment '
                . 'explaining the choice will actually be read (D6).',
                $this->render($literal),
            ))
                ->identifier('lemonfiber.magicNumber')
                ->line($literal->getStartLine())
                ->build();
        }

        return $found;
    }

    /**
     * Numeric literals in these statements that are standing in for a name.
     *
     * @param array<int, Node> $nodes
     *
     * @return list<Int_|Float_>
     */
    private function literalsIn(array $nodes): array
    {
        $found = [];

        foreach ($nodes as $node) {
            if ($this->isPlain($node)) {
                continue;
            }

            if ($node instanceof Int_ || $node instanceof Float_) {
                $found[] = $node;
            }

            foreach ($node->getSubNodeNames() as $name) {
                /** @var mixed $child */
                $child = $node->{$name};

                // A dimension is a position rather than a quantity, and a
                // closure carries its own body along with the method's.
                if ($node instanceof ArrayDimFetch && $name === 'dim') {
                    continue;
                }

                $found = [...$found, ...$this->literalsIn($this->asNodes($child))];
            }
        }

        return $found;
    }

    private function isPlain(Node $node): bool
    {
        if ($node instanceof Int_) {
            return in_array($node->value, self::PLAIN, strict: true);
        }

        return $node instanceof Float_ && in_array((int) $node->value, self::PLAIN, strict: true)
            && (float) (int) $node->value === $node->value;
    }

    /**
     * @return array<int, Node>
     */
    private function asNodes(mixed $child): array
    {
        if ($child instanceof Node) {
            return [$child];
        }

        if (! is_array($child)) {
            return [];
        }

        $found = [];

        foreach ($child as $item) {
            if ($item instanceof Node) {
                $found[] = $item;
            }
        }

        return $found;
    }

    private function render(Int_|Float_ $literal): string
    {
        return $literal instanceof Int_
            ? sprintf('%d', $literal->value)
            : sprintf('%s', $literal->value);
    }
}
