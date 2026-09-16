<?php

declare(strict_types=1);

namespace Tests\Support;

use function array_key_exists;
use function array_map;
use function array_merge;
use function array_unique;
use function array_values;
use function in_array;
use function is_string;
use function json_encode;

use Modules\Dx\Internal\WhatTheContractDeclares;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Foreach_;

use function sprintf;
use function str_starts_with;
use function strpos;
use function substr;

/**
 * What the local names in one reader are bound to.
 *
 * A reader takes a payload apart by naming the pieces — `$changelog`, `$row`,
 * `$said` — and every path it reads is built from one of those names plus a
 * field. So the following is mostly this: which name holds which part of which
 * envelope, at the point the reader reaches through it.
 *
 * Three answers rather than one, because a name in a reader is one of three
 * things: somewhere in a payload, an envelope not yet opened, or the field a
 * helper was told to look for. {@see WhereAnExpressionPoints} works out each of
 * them from an expression; this holds them for a method and hands them on to
 * the methods it calls.
 *
 * @phpstan-import-type Reader from EveryReaderOfTheWire
 * @phpstan-import-type Readers from EveryReaderOfTheWire
 * @phpstan-type Bindings array{within: string, paths: array<string, list<string>>, holds: array<string, string>, wires: array<string, string>}
 * @phpstan-type Answers array<string, list<string>>
 */
final readonly class WhatAReaderNames
{
    /** The namespace whose types are the SDK's rather than this app's. */
    private const string THE_SDK = 'Lemonfiber\\Sdk\\';

    /**
     * How many times a method's local names are rebuilt before they settle.
     *
     * A reader binds each name once, so one pass computes every path and a
     * second confirms it. The third is the margin, and the cap is here so that
     * a shape nobody anticipated is a wrong answer rather than a run that never
     * ends.
     */
    private const int SETTLING = 3;

    /**
     * Bindings holding nothing but what the SDK handed in.
     *
     * @param array<string, string> $handed
     *
     * @return Bindings
     */
    public static function nothingBound(array $handed): array
    {
        return ['within' => '', 'paths' => [], 'holds' => $handed, 'wires' => []];
    }

    /**
     * What one method is handed that belongs to the SDK, by parameter name.
     *
     * Where a payload enters this app. Everything else a reader knows about an
     * envelope it was told by one of these, which is what makes them the places
     * a following starts from.
     *
     * @param Reader $reader
     *
     * @return array<string, string>
     */
    public static function whatTheSdkHandsIn(array $reader): array
    {
        $handed = [];

        foreach ($reader['method']->params as $param) {
            $named = self::nameOf($param->var);
            $type = $param->type;

            if ($named === '' || ! $type instanceof Name) {
                continue;
            }

            $resolved = EveryReaderOfTheWire::resolve($type->toString(), $reader['imports']);

            if (str_starts_with($resolved, self::THE_SDK)) {
                $handed[$named] = $resolved;
            }
        }

        return $handed;
    }

    /**
     * One context, named so that two readings of a method stay apart.
     *
     * The bindings are part of the name rather than merged across call sites.
     * `Reports::saidIn()` is handed a finding, a verdict and a remedy by
     * different callers, each looking for a different field: merged, it would
     * report every field read on any of them as read on all of them, which is
     * the collision this whole reading exists to remove, arriving one level
     * down.
     *
     * @param Bindings $bindings
     */
    public static function signature(string $key, array $bindings): string
    {
        return sprintf('%s|%s', $key, (string) json_encode($bindings));
    }

    /**
     * A method's local names, bound to everywhere they can point.
     *
     * Rebuilt rather than walked in order. What a name holds depends on what
     * the name before it held, and a reader is free to bind one further up than
     * the statement that reads it — so the bindings are applied again until
     * nothing changes, which needs no opinion about the order they were written
     * in.
     *
     * @param Reader   $reader
     * @param Bindings $bindings
     * @param Readers  $readers
     * @param Answers  $answers
     *
     * @return Bindings
     */
    public static function settled(array $reader, array $bindings, array $readers, array $answers): array
    {
        for ($pass = 0; $pass < self::SETTLING; $pass++) {
            foreach ($reader['assigns'] as $assign) {
                $named = self::nameOf($assign->var);

                if ($named !== '') {
                    $bindings = self::bind($named, $assign->expr, $bindings, $bindings, $readers, $answers);
                }
            }

            foreach ($reader['walks'] as $walk) {
                $bindings = self::steppedInto($walk, $bindings, $readers, $answers);
            }
        }

        return $bindings;
    }

    /**
     * The paths a method hands back to whoever called it.
     *
     * @param Reader   $reader
     * @param Bindings $bindings
     * @param Readers  $readers
     * @param Answers  $answers
     *
     * @return list<string>
     */
    public static function handsBack(array $reader, array $bindings, array $readers, array $answers): array
    {
        $answer = [];

        foreach ($reader['hands'] as $return) {
            if ($return->expr instanceof Expr) {
                $answer = array_merge($answer, WhereAnExpressionPoints::paths($return->expr, $bindings, $readers, $answers));
            }
        }

        return array_values(array_unique($answer));
    }

    /**
     * What a call answered, last time the settling worked it out.
     *
     * @param Bindings $bindings
     * @param Readers  $readers
     * @param Answers  $answers
     *
     * @return list<string>
     */
    public static function whatTheCallAnswered(StaticCall $call, array $bindings, array $readers, array $answers): array
    {
        [$key, $given] = self::theCallLeadsTo($call, $bindings, $readers, $answers);

        return $answers[self::signature($key, $given)] ?? [];
    }

    /**
     * Which reader a call goes to, and what it binds to the parameters there.
     *
     * `self::` is most of the calls a reader makes, and the class it stands for
     * is the one the call is written in rather than anything at the call site.
     * Left unresolved it names no reader at all, so every helper a reader hands
     * its payload to would be followed nowhere and the fields those helpers
     * read would look unread.
     *
     * An argument is an expression in the caller and a name in the callee, so
     * it is read against the caller's bindings and written into the callee's.
     * Read against the callee's own, every argument would resolve to nothing —
     * which is a helper that looks as though it was handed no payload.
     *
     * @param Bindings $bindings
     * @param Readers  $readers
     * @param Answers  $answers
     *
     * @return array{0: string, 1: Bindings}
     */
    public static function theCallLeadsTo(StaticCall $call, array $bindings, array $readers, array $answers): array
    {
        $key = self::theCallGoesTo($call, $bindings);
        $given = self::nothingBound([]);

        if (! array_key_exists($key, $readers)) {
            return [$key, $given];
        }

        $given['within'] = self::theClassIn($key);
        $args = $call->getArgs();

        foreach ($readers[$key]['method']->params as $at => $param) {
            $named = self::nameOf($param->var);

            if ($named !== '' && array_key_exists($at, $args)) {
                $given = self::bind($named, $args[$at]->value, $bindings, $given, $readers, $answers);
            }
        }

        return [$key, $given];
    }

    /** The class half of the name a reader is filed under. */
    public static function theClassIn(string $key): string
    {
        $at = strpos($key, '::');

        return $at === false ? $key : substr($key, 0, $at);
    }

    /** The name a variable is written with, or nothing where it is dynamic. */
    public static function nameOf(Node $node): string
    {
        return $node instanceof Variable && is_string($node->name) ? $node->name : '';
    }

    /**
     * One name bound to an entry of whatever is being walked.
     *
     * A list and a map are both walked with `foreach` and nothing at the point
     * of reading tells them apart, which is why the path either produces is
     * written with the one marker {@see WhatTheContractDeclares::EACH} holds.
     *
     * The envelope passes through unchanged: walking a list of envelopes hands
     * over one envelope at a time, which is how a window of log lines reaches
     * the reader that reads them.
     *
     * @param Bindings $bindings
     * @param Readers  $readers
     * @param Answers  $answers
     *
     * @return Bindings
     */
    private static function steppedInto(Foreach_ $walk, array $bindings, array $readers, array $answers): array
    {
        $named = self::nameOf($walk->valueVar);

        if ($named === '') {
            return $bindings;
        }

        $bindings = self::bind($named, $walk->expr, $bindings, $bindings, $readers, $answers);
        $bindings['paths'][$named] = array_map(
            static fn(string $path): string => sprintf('%s%s', $path, WhatTheContractDeclares::EACH),
            $bindings['paths'][$named],
        );

        return $bindings;
    }

    /**
     * A name bound to everything one expression can be read as.
     *
     * @param Bindings $where the bindings the expression is read against
     * @param Bindings $onto  the bindings the name is written into
     * @param Readers  $readers
     * @param Answers  $answers
     *
     * @return Bindings
     */
    private static function bind(string $named, Expr $expr, array $where, array $onto, array $readers, array $answers): array
    {
        $onto['paths'][$named] = WhereAnExpressionPoints::paths($expr, $where, $readers, $answers);
        $holds = WhereAnExpressionPoints::theEnvelopeHeld($expr, $where);
        $wire = TheWireNameAtASubscript::in($expr, $where);

        if ($holds !== null) {
            $onto['holds'][$named] = $holds;
        }

        if ($wire !== null) {
            $onto['wires'][$named] = $wire;
        }

        return $onto;
    }

    /**
     * Which reader a call is filed under.
     *
     * @param Bindings $bindings
     */
    private static function theCallGoesTo(StaticCall $call, array $bindings): string
    {
        if (! $call->class instanceof Name || ! $call->name instanceof Identifier) {
            return '';
        }

        $written = WhereAnExpressionPoints::theLeafOf($call->class->toString());
        $named = in_array($written, ['self', 'static'], strict: true) ? $bindings['within'] : $written;

        return sprintf('%s::%s', $named, $call->name->toString());
    }
}
