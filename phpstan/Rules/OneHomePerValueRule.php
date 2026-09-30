<?php

declare(strict_types=1);

namespace Lemonfiber\Companion\PHPStan\Rules;

use function array_map;
use function implode;

use Lemonfiber\Companion\PHPStan\Collectors\ConstantValues;
use Lemonfiber\Companion\PHPStan\Rules\OneHome\Clash;
use Lemonfiber\Companion\PHPStan\Rules\OneHome\Coincidences;
use Lemonfiber\Companion\PHPStan\Rules\OneHome\Home;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

use function sprintf;

/**
 * D9 — one value has one home.
 *
 * The same value declared as a constant in one class and again in another is
 * one decision kept in two places, and a change to one leaves the other
 * behind: a heartbeat changed in one class and not in the class that works
 * out when a stream has gone quiet, a status range narrowed in one adapter
 * and not in the three beside it. The value keeps one home and the other
 * class refers to it. An enum case is a home too, so a constant spelling a
 * case's value is reported; two enums sharing a word are left to D4.
 *
 * Where two constants hold the same value and mean different things, one is
 * named in {@see Coincidences} with what it means, and that list only
 * shrinks.
 *
 * @implements Rule<CollectedDataNode>
 */
final readonly class OneHomePerValueRule implements Rule
{
    public function getNodeType(): string
    {
        return CollectedDataNode::class;
    }

    /** @return list<IdentifierRuleError> */
    public function processNode(Node $node, Scope $scope): array
    {
        return array_map(
            static fn(Clash $clash): IdentifierRuleError => RuleErrorBuilder::message(sprintf(
                'D9 — %s holds %s, and so does %s. One value has one home: keep it where it belongs and refer to it from the others. A duration, a limit or a threshold that is a default somebody could tune belongs on a value with a named standard(); where the two only coincide and mean different things, name one in Coincidences with what it means (D9).',
                $clash->home->name,
                $clash->home->value,
                implode(', ', array_map(static fn(Home $other): string => $other->name, $clash->others)),
            ))
                ->identifier('lemonfiber.valueWithTwoHomes')
                ->file($clash->home->file)
                ->line($clash->home->line)
                ->build(),
            Clash::among($node->get(ConstantValues::class), Coincidences::named()),
        );
    }
}
