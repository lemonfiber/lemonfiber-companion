<?php

declare(strict_types=1);

namespace Lemonfiber\Companion\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Scalar\InterpolatedString;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * H5, the other half — interpolation is concatenation with nicer syntax.
 *
 * `"Stack {$name} is asleep"` has exactly the problem PreferSprintfOverConcat
 * exists to solve: the shape of the string is interleaved with the values going
 * into it, so there is no single literal a reader can check or a translator can
 * be handed. `sprintf('Stack %s is asleep', $name)` has one, and the translator
 * with a replacement array has one for the strings a person actually reads.
 *
 * This is the same rule as the concatenation one and carries the same
 * identifier. It is a second class only because a PHPStan rule declares exactly
 * one node type, and concatenation and interpolation are different nodes.
 *
 * @implements Rule<InterpolatedString>
 */
final class PreferSprintfOverInterpolationRule implements Rule
{
    public function getNodeType(): string
    {
        return InterpolatedString::class;
    }

    /** @return list<IdentifierRuleError> */
    public function processNode(Node $node, Scope $scope): array
    {
        return [
            RuleErrorBuilder::message(
                'H5 — build this with sprintf rather than interpolation. An interpolated '
                . 'string spreads its shape across the values going into it, exactly as '
                . 'concatenation does, and there is no single literal to check or to hand '
                . 'to a translator. Use sprintf() for text a developer reads and __() with '
                . 'a replacement array for text a person reads (H5).',
            )
                ->identifier('lemonfiber.preferSprintf')
                ->line($node->getStartLine())
                ->build(),
        ];
    }
}
