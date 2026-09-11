<?php

declare(strict_types=1);

namespace Lemonfiber\Companion\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Stmt\If_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

use function sprintf;

/**
 * C5 — a method has one exit shape: guard, return, carry on.
 *
 * `else` is where two branches start drifting apart. The happy path gets
 * indented one level for every condition ahead of it, and the reader has to
 * hold each condition in their head to know which body they are in. Returning
 * early from the refusal leaves the rest of the method at one indent, reading
 * top to bottom, with each guard stating the one thing it rules out.
 *
 * It also interacts with C1: an `else` is usually where a refusal gets handled
 * inline instead of being returned as an Outcome the caller has to open.
 *
 * Rector's early-return set rewrites most of this already, so what this mainly
 * does is stop it coming back — a rule the formatter enforces silently is one
 * nobody learns.
 *
 * @implements Rule<If_>
 */
final class NoElseRule implements Rule
{
    public function getNodeType(): string
    {
        return If_::class;
    }

    /** @return list<IdentifierRuleError> */
    public function processNode(Node $node, Scope $scope): array
    {
        $found = [];

        foreach ($node->elseifs as $elseif) {
            $found[] = RuleErrorBuilder::message($this->message('elseif'))
                ->identifier('lemonfiber.noElse')
                ->line($elseif->getStartLine())
                ->build();
        }

        if ($node->else instanceof Node\Stmt\Else_) {
            $found[] = RuleErrorBuilder::message($this->message('else'))
                ->identifier('lemonfiber.noElse')
                ->line($node->else->getStartLine())
                ->build();
        }

        return $found;
    }

    private function message(string $keyword): string
    {
        return sprintf(
            'C5 — no `%s`. Return from the condition that refuses and let the rest of '
            . 'the method carry on at one indent, so each guard states the one thing it '
            . 'rules out and the happy path reads top to bottom. Where the branches are '
            . 'both answers rather than a refusal and a path, that is a `match`, which is '
            . 'exhaustive and returns a value.',
            $keyword,
        );
    }
}
