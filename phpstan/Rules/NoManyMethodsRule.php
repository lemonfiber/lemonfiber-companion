<?php

declare(strict_types=1);

namespace Lemonfiber\Companion\PHPStan\Rules;

use function count;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Enum_;
use PhpParser\Node\Stmt\Interface_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

use function sprintf;
use function str_contains;

/**
 * H3 — a class answers at most twenty questions.
 *
 * A class past the cap is usually two, and on this codebase it is reliably a
 * screen that grew an accessor per field. `WhatWouldBePutRight` reached the
 * twenty-first by gaining a `#[Poll]` method, and the cure was not a split: two
 * accessors onto one fact — a cadence's key and its count — became one that
 * hands out the case. `WhatThisStackRuns` was built to that shape from the
 * start and hands out the whole answer, so the next field its template needs
 * costs no method at all.
 *
 * **It is here because SonarCloud found it first**, which is exactly the
 * argument {@see NoManyReturnsRule} makes: `S1448` reported it on a pull
 * request, after the work was finished and in a place the author had stopped
 * looking. CI refuses any open issue, so the ceiling was already
 * enforced — a push away, by a service that has to finish analysing first.
 *
 * **`H3` claimed this before anything counted.** Its row names four caps —
 * methods per class, lines per method, constructor parameters, cognitive
 * complexity — and only the last had a mechanism here. A documented rule whose
 * mechanism is narrower than its sentence is worse than a narrow rule honestly
 * described: the artefact reports green and a reader stops checking, which is
 * strictly worse than no check at all, because an unchecked area gets reviewed
 * by a person.
 *
 * Counted as the class declares them, constructor included, which is what
 * SonarCloud counts — a cap this side could pass while the gate refused would
 * be the drift this rule exists to close.
 *
 * Tests are exempt, for {@see NoManyReturnsRule}'s reason: a test support class
 * is a table of fixtures rather than a thing somebody has to follow.
 *
 * @implements Rule<ClassLike>
 */
final class NoManyMethodsRule implements Rule
{
    /**
     * How many questions a class may answer.
     *
     * Twenty, which is SonarCloud's own default for `S1448` and is taken rather
     * than chosen: the point of this rule is that the two numbers agree, so a
     * class that passes here cannot fail there.
     *
     * Published so the fixture that proves this rule can build a class of
     * exactly one more. A number written twice is a number that drifts, and the
     * copy that drifts is the one in the test — which then proves the rule
     * refuses a count nothing is near.
     */
    public const int THE_MOST_METHODS = 20;

    public function getNodeType(): string
    {
        return ClassLike::class;
    }

    /** @return list<IdentifierRuleError> */
    public function processNode(Node $node, Scope $scope): array
    {
        if (str_contains($scope->getFile(), '/tests/')) {
            return [];
        }

        $methods = count($node->getMethods());

        if ($methods <= self::THE_MOST_METHODS) {
            return [];
        }

        return [
            RuleErrorBuilder::message(sprintf(
                'H3 — this %s declares %d methods, which is more than %d. On a screen the '
                . 'cure is usually not a split: an accessor per field is what gets a class '
                . 'here, and one accessor handing out the value it folded costs one method '
                . 'however many fields the template reads. Elsewhere it is two classes, and '
                . 'the seam is where the questions change subject (H3, Q-R64).',
                $this->what($node),
                $methods,
                self::THE_MOST_METHODS,
            ))
                ->identifier('lemonfiber.manyMethods')
                ->line($node->getStartLine())
                ->build(),
        ];
    }

    /** What to call this in the message, so a reader knows what they are looking at. */
    private function what(ClassLike $node): string
    {
        return match (true) {
            $node instanceof Interface_ => 'interface',
            $node instanceof Enum_ => 'enum',
            $node instanceof Class_ => 'class',
            default => 'type',
        };
    }
}
