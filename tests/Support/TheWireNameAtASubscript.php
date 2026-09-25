<?php

declare(strict_types=1);

namespace Tests\Support;

use function file_get_contents;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;

use function preg_match;
use function preg_match_all;
use function sprintf;

/**
 * Which field of the wire a piece of a reader is reaching for.
 *
 * Half of a path, and the half that is written down. `D4` has every name on the
 * wire spelled once, in {@see \Modules\Sdk\Api\WireField}, so a reader reaching
 * for a field writes `WireField::X->value` — and a helper that was told which
 * field to look for writes `$field->value`, where which one it is belongs to
 * whoever called it.
 *
 * Both are the same statement made in the two places a reader makes it, and
 * both are answered here so that the rest of the following never has to know
 * the difference. The other half — *of what* — is {@see WhereAnExpressionPoints}.
 *
 * @phpstan-import-type Bindings from WhatAReaderNames
 */
final readonly class TheWireNameAtASubscript
{
    /**
     * The wire name an expression names, where it names one.
     *
     * @param Bindings $bindings
     */
    public static function in(Expr $expr, array $bindings): ?string
    {
        if ($expr instanceof String_) {
            return $expr->value;
        }

        if ($expr instanceof PropertyFetch && self::isTheValueOfACase($expr)) {
            return self::theCaseNamed($expr->var, $bindings);
        }

        return self::theCaseNamed($expr, $bindings);
    }

    /**
     * Which field a reach names, where it names one at all.
     *
     * Asked with nothing bound, so a helper reaching through `$field->value`
     * answers with the parameter rather than with a name — which is what makes
     * the answer a list of places rather than a second, weaker reading of the
     * walk. A place is still a place whether or not this side can say which
     * field the call site meant by it.
     */
    public static function reachedForBy(Expr $reach): ?string
    {
        $nothing = WhatAReaderNames::nothingBound([]);

        if ($reach instanceof ArrayDimFetch && $reach->dim instanceof Expr) {
            return self::in($reach->dim, $nothing) ?? self::whatAHelperWasHanded($reach->dim);
        }

        if (! $reach instanceof FuncCall || ! self::isAPresenceCheck($reach)) {
            return null;
        }

        $args = WhereTheReadingStops::theArgumentsOf($reach, 'reading a presence check');

        return $args === []
            ? null
            : self::in($args[0]->value, $nothing) ?? self::whatAHelperWasHanded($args[0]->value);
    }

    /** Whether an expression is the `->value` of something. */
    public static function isTheValueOfACase(PropertyFetch $fetch): bool
    {
        return $fetch->name instanceof Identifier && $fetch->name->toString() === 'value';
    }

    /**
     * Whether a call is the one every reader asks a field's presence with.
     *
     * A field asked about is a field read: the answer decides what the screen
     * says. Counting only the subscript would leave the optional fields — the
     * ones a decision is most often owed about — looking as though nothing had
     * ever looked at them.
     */
    public static function isAPresenceCheck(FuncCall $call): bool
    {
        return $call->name instanceof Name && $call->name->toString() === 'array_key_exists';
    }

    /** The case a helper reads through, named by the parameter it came in on. */
    private static function whatAHelperWasHanded(Expr $dim): ?string
    {
        if (! $dim instanceof PropertyFetch || ! self::isTheValueOfACase($dim)) {
            return null;
        }

        $named = WhatAReaderNames::nameOf($dim->var);

        return $named === '' ? null : sprintf('$%s', $named);
    }

    /**
     * Which case of the wire vocabulary an expression is.
     *
     * @param Bindings $bindings
     */
    private static function theCaseNamed(Expr $expr, array $bindings): ?string
    {
        if ($expr instanceof Variable) {
            return $bindings['wires'][WhatAReaderNames::nameOf($expr)] ?? null;
        }

        if (! $expr instanceof ClassConstFetch || ! $expr->class instanceof Name) {
            return null;
        }

        $name = $expr->name;

        $class = $expr->class->getLast();

        return $name instanceof Identifier
            ? self::theVocabulary()[sprintf('%s::%s', $class, $name->toString())] ?? null
            : null;
    }

    /**
     * Every case of every wire vocabulary, by the name a reader writes for it.
     *
     * Read off the enums' own source rather than off the enums, because what
     * is wanted is the mapping from the case written at a subscript to the name
     * that goes out — and `cases()` answers with values this would then have no
     * way to tie back to the identifier in the source. Keyed by the enum's short
     * name and the case, `SpaceField::Volumes`, because that is what a reader
     * writes.
     *
     * @return array<string, string>
     */
    private static function theVocabulary(): array
    {
        $files = [
            Tree::at(sprintf('%s/Api/WireField.php', EveryReaderOfTheWire::WHERE)),
            ...Tree::filesUnder(Tree::at(sprintf('%s/Api/Fields', EveryReaderOfTheWire::WHERE)), '.php'),
        ];
        $vocabulary = [];

        foreach ($files as $file) {
            $said = (string) file_get_contents($file);

            if (preg_match('/^enum (\\w+): string/m', $said, $enum) !== 1) {
                continue;
            }

            preg_match_all("/case (\\w+) = '([^']+)';/", $said, $found, PREG_SET_ORDER);

            foreach ($found as $case) {
                $vocabulary[sprintf('%s::%s', $enum[1], $case[1])] = $case[2];
            }
        }

        return $vocabulary;
    }
}
