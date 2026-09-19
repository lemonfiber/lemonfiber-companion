<?php

declare(strict_types=1);

namespace Tests\Support;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use RuntimeException;

use function sprintf;

/**
 * The edge of what the reading can follow, stated in one place.
 *
 * {@see WhatAReaderNames}, {@see TheWireNameAtASubscript} and
 * {@see WhereAnExpressionPoints} are one small analysis between them: they bind
 * a call's arguments to a reader's parameters and follow those bindings to the
 * subscript that takes a wire field. What they are asked for is a **negative**
 * claim — that nothing in this app reads a field — and a negative claim is only
 * worth what the analysis behind it can see.
 *
 * So the edge is named here rather than guessed at three call sites. An
 * analysis that meets something it cannot follow has two honest answers:
 * assume the field might be read, or refuse and say so. It has no third answer,
 * and *read it as nothing* is the one that quietly turns **I cannot see** into
 * **there is nothing there** — which is the whole failure the register exists
 * to prevent, arriving by way of the thing meant to prevent it.
 *
 * **Refusing is not a smaller answer than following.** It is the floor under
 * one: teaching the analysis to follow a construct narrows what is refused
 * here, and what is refused here is the list of what following would have to
 * cover. Without it there is no list, only a silence nobody can audit.
 */
final readonly class WhereTheReadingStops
{
    /**
     * A call's arguments, or a refusal where the node only looks like a call.
     *
     * `Foo::bar(...)` parses as a call and is not one. It builds a closure and
     * defers the call to somewhere this analysis does not go, so its arguments
     * are not absent — they are elsewhere. `getArgs()` says as much by
     * asserting, which reaches a reader as an assertion from inside the parser
     * rather than as anything they can act on.
     *
     * @param  string      $reading what the analysis was doing, for the refusal to name
     * @return array<Arg>
     */
    public static function theArgumentsOf(CallLike $call, string $reading): array
    {
        if ($call->isFirstClassCallable()) {
            throw new RuntimeException(sprintf(
                "%s:%d: %s is handed as a first-class callable, and %s cannot follow one.\n\n"
                . "The arguments are not absent — they are given wherever the closure is "
                . "called, which this analysis does not reach. Read as a call with none, a "
                . "wire field taken through it would be reported as read by nothing.\n\n"
                . 'Call it directly, or teach %s to follow a deferred call.',
                'a reader',
                $call->getStartLine(),
                self::named($call),
                $reading,
                self::class,
            ));
        }

        return $call->getArgs();
    }

    /** What the node calls, as well as it can be told from the node alone. */
    private static function named(CallLike $call): string
    {
        $name = match (true) {
            $call instanceof StaticCall, $call instanceof MethodCall => $call->name,
            $call instanceof FuncCall => $call->name,
            default => null,
        };

        return match (true) {
            $name instanceof Identifier => sprintf('`%s`', $name->toString()),
            $name instanceof Name => sprintf('`%s`', $name->toString()),
            default => 'something this cannot name',
        };
    }
}
