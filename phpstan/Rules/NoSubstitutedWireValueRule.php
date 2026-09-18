<?php

declare(strict_types=1);

namespace Lemonfiber\Companion\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\BinaryOp\Coalesce;
use PhpParser\Node\Expr\Throw_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

use function str_contains;

/**
 * A reader never substitutes a value the contract did not carry.
 *
 * "Where the contract does not carry something a requirement here asks the app
 * to state, the app MUST NOT substitute a value of its own; the gap MUST be
 * raised against the contract and the requirement MUST be answered there."
 *
 * The shape that breaks it is a coalesce with a value on the right. A field is
 * missing, the reader has to hand something back, and the obvious thing is a
 * sensible default — `?? false` for a flag, `?? 0` for a count, `?? Stage::Monitored`
 * for a state. It compiles, every test passes, and the screen now states a fact
 * this application invented. Nobody decides to do that; it is what writing the
 * reader feels like when the payload is one field short.
 *
 * It is worse than it sounds because the default is always the reassuring one.
 * `stuck.incomplete` defaulted to `false` renders exactly like a complete
 * listing and tells an operator that three stalled titles are all of them.
 *
 * **`?? throw` is the whole of what is allowed here**, which is the same
 * coalesce doing the opposite thing: the field is missing, the reader says so,
 * and the refusal reaches the screen as the obstacle it is. That is what every
 * reader in this module already does, and this is the rule that keeps the next
 * one doing it.
 *
 * Narrow on purpose. The readers are the only place a wire payload is turned
 * into a value, so that is where this looks; a coalesce anywhere else is
 * ordinary and is `C9`'s business rather than this rule's.
 *
 * @implements Rule<Coalesce>
 */
final class NoSubstitutedWireValueRule implements Rule
{
    /** Where a wire payload becomes a value this application shows. */
    private const string THE_READERS = '/app-modules/sdk/src/Api/';

    public function getNodeType(): string
    {
        return Coalesce::class;
    }

    /** @return list<IdentifierRuleError> */
    public function processNode(Node $node, Scope $scope): array
    {
        if (! str_contains($scope->getFile(), self::THE_READERS)) {
            return [];
        }

        if ($node->right instanceof Throw_) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                'N2-R14 — this hands back a value the payload did not carry. A reader one '
                . 'field short must refuse, not substitute: the default is always the '
                . 'reassuring one, and a screen stating a fact this app invented is the '
                . 'failure the requirement names. `?? throw` is the same coalesce doing the '
                . 'opposite thing, and is what every reader here already does (N2-R14).',
            )
                ->identifier('lemonfiber.substitutedWireValue')
                ->line($node->getStartLine())
                ->build(),
        ];
    }
}
