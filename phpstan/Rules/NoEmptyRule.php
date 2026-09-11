<?php

declare(strict_types=1);

namespace Lemonfiber\Companion\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\Empty_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * C7 — `empty()` cannot tell absence from zero.
 *
 * It is true for `null`, `false`, `0`, `'0'`, `''` and `[]`, and this
 * application turns on exactly the distinctions it erases. A stack with zero
 * findings is healthy. A stack that has never been read is unknown. A backup
 * count of zero is a warning and a backup count that has not arrived is a
 * spinner. `empty()` renders all of those as the same screen.
 *
 * The cure is a comparison that says which one is meant: `=== []`, `=== 0`, or
 * an absence type, which is what C2 is for.
 *
 * @implements Rule<Empty_>
 */
final class NoEmptyRule implements Rule
{
    public function getNodeType(): string
    {
        return Empty_::class;
    }

    /** @return list<IdentifierRuleError> */
    public function processNode(Node $node, Scope $scope): array
    {
        return [
            RuleErrorBuilder::message(
                'C7 — empty() is true for null, false, 0, "0", "" and [], and this '
                . 'application depends on telling those apart. A stack with no findings is '
                . 'healthy; a stack that has never answered is unknown; and empty() draws '
                . 'them as the same screen. Compare for the one you mean — === [], === 0 — '
                . 'or answer with a type that says which, which is what C2 asks for '
                . '(C7, C2).',
            )
                ->identifier('lemonfiber.empty')
                ->line($node->getStartLine())
                ->build(),
        ];
    }
}
