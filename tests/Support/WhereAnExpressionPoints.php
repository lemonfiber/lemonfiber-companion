<?php

declare(strict_types=1);

namespace Tests\Support;

use function array_key_exists;
use function array_map;
use function array_merge;
use function array_unique;
use function array_values;

use Modules\Dx\Internal\WhatTheContractDeclares;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\BinaryOp\Coalesce;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Match_;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Ternary;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\MatchArm;
use PhpParser\Node\Name;

use function sprintf;
use function strrpos;
use function substr;

/**
 * Where in an envelope one expression in a reader is pointing.
 *
 * The other half of a path: {@see TheWireNameAtASubscript} says which field is
 * being reached for and this says what it is being reached for *off*. Together
 * they make `UpdateEnvelope.changelog.state` out of `$changelog[State->value]`,
 * which is a place a contract path can be compared against and a name never
 * was.
 *
 * Nothing here follows a call. A call is looked up in the answers the settling
 * has worked out so far, which is what lets the whole following be a set of
 * values arriving at a fixed point rather than a recursion carrying state
 * through fifteen frames — and what makes a reader that calls itself end the
 * walk rather than end the run.
 *
 * @phpstan-import-type Readers from EveryReaderOfTheWire
 * @phpstan-import-type Answers from WhatAReaderNames
 * @phpstan-import-type Bindings from WhatAReaderNames
 */
final readonly class WhereAnExpressionPoints
{
    /** The property an SDK envelope carries its payload on. */
    private const string PAYLOAD = 'data';

    /**
     * The type whose static call is a pass-through rather than a construction.
     *
     * Named rather than compared inline for `D4`'s reason, and because there is
     * exactly one of these: a second would mean the following has to model a
     * set of them, and a set is a thing to keep correct.
     */
    private const string THE_WIRE_GATE = 'Wire';

    /**
     * Everywhere in an envelope one expression can be read as.
     *
     * @param Bindings $bindings
     * @param Readers  $readers
     * @param Answers  $answers
     *
     * @return list<string>
     */
    public static function paths(Expr $expr, array $bindings, array $readers, array $answers): array
    {
        if ($expr instanceof Variable) {
            return $bindings['paths'][WhatAReaderNames::nameOf($expr)] ?? [];
        }

        if ($expr instanceof ArrayDimFetch) {
            return self::subscript($expr, $bindings, $readers, $answers);
        }

        if ($expr instanceof PropertyFetch) {
            return self::opened($expr, $bindings);
        }

        if ($expr instanceof StaticCall) {
            return WhatAReaderNames::whatTheCallAnswered($expr, $bindings, $readers, $answers);
        }

        if ($expr instanceof FuncCall) {
            return self::asked($expr, $bindings, $readers, $answers);
        }

        return self::eitherWay($expr, $bindings, $readers, $answers);
    }

    /**
     * Which envelope an expression is one of, where it is one.
     *
     * Three ways an envelope reaches a reader: a name already bound to one,
     * `XEnvelope::in()` naming its own kind, and something of the SDK's handing
     * envelopes over — a log window is the third, and what it holds is read off
     * its own docblock by {@see WhatTheSdkHandsBack}.
     *
     * A kind is only a kind if the contract declares an envelope by that name,
     * which is what stops a call that merely happens to be spelled `::in` from
     * seating a payload on a shape nobody has.
     *
     * @param Bindings $bindings
     */
    public static function theEnvelopeHeld(Expr $expr, array $bindings): ?string
    {
        if ($expr instanceof Variable) {
            return $bindings['holds'][WhatAReaderNames::nameOf($expr)] ?? null;
        }

        if ($expr instanceof StaticCall && $expr->class instanceof Name) {
            $named = self::theLeafOf($expr->class->toString());

            // `Wire::checked()` hands back the envelope it was given — it
            // asserts the wire version and returns the same object — so a
            // reading that stopped here would lose every field read through the
            // gate `N1-R13` asks for, and report them as fields nothing reads.
            // Seeing through it is what keeps passing an envelope through the
            // gate from making its payload invisible to this register.
            if ($named === self::THE_WIRE_GATE) {
                return self::whatWasHandedTo($expr, $bindings);
            }

            return WhatTheContractDeclares::shapeOf($named) === '' ? null : $named;
        }

        return $expr instanceof MethodCall && $expr->name instanceof Identifier
            ? WhatTheSdkHandsBack::from(self::theEnvelopeHeld($expr->var, $bindings), $expr->name->toString())
            : null;
    }

    /** The last segment of a name written with separators. */
    public static function theLeafOf(string $written): string
    {
        $at = strrpos($written, '\\');

        return $at === false ? $written : substr($written, $at + 1);
    }

    /**
     * Where the envelope a pass-through was given points.
     *
     * Its own method rather than a branch inside {@see theEnvelopeHeld()},
     * which the analyser holds to a complexity the extra guards crossed.
     *
     * `array_key_exists` rather than `??` on the subscript, which `C9`
     * refuses, and the `Arg` guard because a call's arguments may hold a
     * variadic placeholder with no expression in it.
     *
     * @param Bindings $bindings
     */
    private static function whatWasHandedTo(StaticCall $expr, array $bindings): ?string
    {
        if (! array_key_exists(0, $expr->args)) {
            return null;
        }

        $handed = $expr->args[0];

        return $handed instanceof Arg
            ? self::theEnvelopeHeld($handed->value, $bindings)
            : null;
    }

    /**
     * A subscript, which is the whole of what reading a field looks like.
     *
     * @param Bindings $bindings
     * @param Readers  $readers
     * @param Answers  $answers
     *
     * @return list<string>
     */
    private static function subscript(ArrayDimFetch $fetch, array $bindings, array $readers, array $answers): array
    {
        $name = $fetch->dim instanceof Expr ? TheWireNameAtASubscript::in($fetch->dim, $bindings) : null;

        return $name === null
            ? []
            : self::under(self::paths($fetch->var, $bindings, $readers, $answers), $name);
    }

    /**
     * A presence check, which reads a field without taking it.
     *
     * `array_key_exists(WireField::X->value, $said)` is how every reader here
     * asks whether a field arrived, and {@see TheWireNameAtASubscript} says why
     * that counts as reading it.
     *
     * @param Bindings $bindings
     * @param Readers  $readers
     * @param Answers  $answers
     *
     * @return list<string>
     */
    private static function asked(FuncCall $call, array $bindings, array $readers, array $answers): array
    {
        $args = $call->getArgs();

        if (! TheWireNameAtASubscript::isAPresenceCheck($call) || ! array_key_exists(1, $args)) {
            return [];
        }

        $name = TheWireNameAtASubscript::in($args[0]->value, $bindings);

        return $name === null
            ? []
            : self::under(self::paths($args[1]->value, $bindings, $readers, $answers), $name);
    }

    /**
     * One field, taken off everywhere its holder could be.
     *
     * @param list<string> $holders
     *
     * @return list<string>
     */
    private static function under(array $holders, string $name): array
    {
        return array_map(static fn(string $holder): string => sprintf('%s.%s', $holder, $name), $holders);
    }

    /**
     * The payload of an envelope that has been opened.
     *
     * @param Bindings $bindings
     *
     * @return list<string>
     */
    private static function opened(PropertyFetch $fetch, array $bindings): array
    {
        $name = $fetch->name;

        if (! $name instanceof Identifier || $name->toString() !== self::PAYLOAD) {
            return [];
        }

        $envelope = self::theEnvelopeHeld($fetch->var, $bindings);

        return $envelope === null ? [] : [$envelope];
    }

    /**
     * Both arms of an expression that is one thing or another.
     *
     * A reader answers an absent field with an arm rather than a default, so
     * the shapes that choose — `?:`, `??`, `match` — are where a path most
     * often leaves a method.
     *
     * @param Bindings $bindings
     * @param Readers  $readers
     * @param Answers  $answers
     *
     * @return list<string>
     */
    private static function eitherWay(Expr $expr, array $bindings, array $readers, array $answers): array
    {
        $answer = [];

        foreach (self::theArmsOf($expr) as $arm) {
            $answer = array_merge($answer, self::paths($arm, $bindings, $readers, $answers));
        }

        return array_values(array_unique($answer));
    }

    /**
     * The arms of an expression that chooses, and nothing for one that does not.
     *
     * @return list<Expr>
     */
    private static function theArmsOf(Expr $expr): array
    {
        if ($expr instanceof Ternary) {
            return $expr->if instanceof Expr ? [$expr->if, $expr->else] : [$expr->else];
        }

        if ($expr instanceof Coalesce) {
            return [$expr->left, $expr->right];
        }

        return $expr instanceof Match_
            ? array_values(array_map(static fn(MatchArm $arm): Expr => $arm->body, $expr->arms))
            : [];
    }
}
