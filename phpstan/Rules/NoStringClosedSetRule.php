<?php

declare(strict_types=1);

namespace Lemonfiber\Companion\PHPStan\Rules;

use function array_any;
use function array_key_exists;
use function array_keys;
use function count;
use function implode;
use function in_array;

use Lemonfiber\Companion\PHPStan\Rules\ClosedSets\OutsideVocabularies;
use Lemonfiber\Companion\PHPStan\Rules\ClosedSets\StringSets;
use Lemonfiber\Companion\PHPStan\Rules\OneHome\Ours;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\BinaryOp;
use PhpParser\Node\Expr\BinaryOp\Equal;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\BinaryOp\NotEqual;
use PhpParser\Node\Expr\BinaryOp\NotIdentical;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Match_;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

use function sort;
use function sprintf;
use function str_ends_with;

/**
 * D8 — a closed set of strings is an enum.
 *
 * `D4` refuses a string literal compared against, and the cure it offers a
 * sentinel — a `private const` — is also the way round it for a set: four
 * constants a value is compared with are four words every caller has to
 * spell right, which is a vocabulary with no type. So is a list of three or
 * more strings that `in_array` or `array_key_exists` asks about. An enum
 * names the set once and lets the analyser check each use; `tryFrom` is where
 * a word from outside becomes one of its cases.
 *
 * A class that compares against three or more of its own string constants,
 * and a lookup over a constant or a list written in place holding three or
 * more strings, are reported: two are a pair of words, and three begin a set.
 * A file where an outside vocabulary is read in its own words is named in
 * {@see OutsideVocabularies}, since the strings there are the outside
 * world's, and that list only shrinks.
 *
 * @implements Rule<InClassNode>
 */
final readonly class NoStringClosedSetRule implements Rule
{
    /** Two strings are a pair of words; three begin a set. */
    private const int SMALLEST_SET = 3;

    /** The lookups that ask a list whether it holds a string: among its values, or among its keys. */
    private const string AMONG_VALUES = 'in_array';

    private const string AMONG_KEYS = 'array_key_exists';

    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    /** @return list<IdentifierRuleError> */
    public function processNode(Node $node, Scope $scope): array
    {
        if (! Ours::ships($scope->getFile()) || $this->isExempt($scope)) {
            return [];
        }

        $class = $node->getOriginalNode();
        $found = [];
        $compared = $this->stringConstantsComparedIn($class->stmts, $scope);

        if (count($compared) >= self::SMALLEST_SET) {
            $found[] = $this->reported(
                sprintf('%s compares against the string constants %s', $node->getClassReflection()->getName(), implode(', ', $compared)),
                $class->getStartLine(),
            );
        }

        foreach ((new NodeFinder())->findInstanceOf($class->stmts, FuncCall::class) as $call) {
            $asked = $this->closedSetLookedUp($call, $scope);

            if ($asked !== '') {
                $found[] = $this->reported($asked, $call->getStartLine());
            }
        }

        return $found;
    }

    private function isExempt(Scope $scope): bool
    {
        return array_any(
            array_keys(OutsideVocabularies::IN),
            static fn(string $file): bool => str_ends_with($scope->getFile(), sprintf('/%s', $file)),
        );
    }

    private function reported(string $what, int $line): IdentifierRuleError
    {
        return RuleErrorBuilder::message(sprintf(
            'D8 — %s, which treats a closed set of strings as its values. Make the set an enum and compare, match or look up its cases, so every use is checked; where the words come from outside, `tryFrom` at the boundary is where they become cases (D4, D8).',
            $what,
        ))
            ->identifier('lemonfiber.closedSetAsStrings')
            ->line($line)
            ->build();
    }

    /**
     * The string constants a class compares a value against, by identity or as a match arm, each named once.
     *
     * @param array<Node> $statements
     *
     * @return list<string>
     */
    private function stringConstantsComparedIn(array $statements, Scope $scope): array
    {
        $finder = new NodeFinder();
        $sides = [];

        foreach ($finder->find($statements, static fn(Node $node): bool => $node instanceof Identical || $node instanceof NotIdentical || $node instanceof Equal || $node instanceof NotEqual) as $comparison) {
            if ($comparison instanceof BinaryOp) {
                $sides = [...$sides, $comparison->left, $comparison->right];
            }
        }

        foreach ($finder->findInstanceOf($statements, Match_::class) as $match) {
            foreach ($match->arms as $arm) {
                $sides = [...$sides, ...($arm->conds ?? [])];
            }
        }

        $named = [];

        foreach ($sides as $side) {
            if ($this->isAStringItDeclares($side, $scope)) {
                $named[$this->nameOf($side)] = true;
            }
        }

        $names = array_keys($named);
        sort($names);

        return $names;
    }

    /**
     * Whether this is one of the class's own string constants.
     *
     * Its own, because that is the cure `D4` offers a sentinel and so the shape
     * a set takes when it is written round the rule. A constant another
     * package declares is that package's vocabulary, and `::class` is a name
     * rather than a word.
     */
    private function isAStringItDeclares(Node $side, Scope $scope): bool
    {
        return $side instanceof ClassConstFetch
            && $side->class instanceof Name
            && $side->name instanceof Identifier
            && $side->name->toLowerString() !== 'class'
            && $scope->isInClass()
            && $scope->resolveName($side->class) === $scope->getClassReflection()->getName()
            && count($scope->getType($side)->getConstantStrings()) === 1;
    }

    /** The class constant a lookup asks about, where it holds a closed set of strings, or nothing. */
    private function closedSetLookedUp(FuncCall $call, Scope $scope): string
    {
        if (! $call->name instanceof Name || $call->isFirstClassCallable()) {
            return '';
        }

        $arguments = $call->getArgs();
        $function = $call->name->toLowerString();
        $set = array_key_exists(1, $arguments) ? $arguments[1]->value : $call;

        if (! ($set instanceof ClassConstFetch || $set instanceof Array_) || ! in_array($function, [self::AMONG_VALUES, self::AMONG_KEYS], strict: true)) {
            return '';
        }

        $held = $function === self::AMONG_VALUES
            ? StringSets::amongValues($scope->getType($set))
            : StringSets::amongKeys($scope->getType($set));

        return $held >= self::SMALLEST_SET ? sprintf('%s asks %s', $function, $this->nameOf($set)) : '';
    }

    private function nameOf(Expr $fetch): string
    {
        if (! $fetch instanceof ClassConstFetch) {
            return 'a list written in place';
        }

        $class = $fetch->class instanceof Name ? $fetch->class->toString() : 'the class';
        $name = $fetch->name instanceof Identifier ? $fetch->name->toString() : 'a constant';

        return sprintf('%s::%s', $class, $name);
    }
}
